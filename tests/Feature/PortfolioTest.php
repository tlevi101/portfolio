<?php

namespace Tests\Feature;

use App\Enums\ProjectType;
use App\Filament\Resources\Portfolios\Pages\EditPortfolio;
use App\Models\Cv;
use App\Models\Portfolio;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Tests\TestCase;

class PortfolioTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // DatabaseSeeder seeds with model events muted, so no CV PDF is built here.
        $this->seed();
    }

    public function test_homepage_renders_the_default_portfolio(): void
    {
        $this->get('/')
            ->assertStatus(200)
            ->assertSee('Torma Levente', false);

        $this->assertSame('hu', app()->getLocale());
    }

    public function test_portfolio_is_reachable_by_slug(): void
    {
        $this->get('/fullstack')->assertStatus(200);
    }

    public function test_unknown_language_falls_back_to_an_existing_row(): void
    {
        // No English row exists yet; the request should fall back, not 404.
        $this->get('/fullstack?lang=en')->assertStatus(200);
    }

    public function test_unknown_slug_returns_404(): void
    {
        $this->get('/does-not-exist')->assertNotFound();
    }

    public function test_cv_download_generates_and_streams_a_pdf(): void
    {
        Storage::fake('public');

        $response = $this->get('/cv/fullstack');

        $response->assertStatus(200);
        $this->assertSame('application/pdf', $response->headers->get('content-type'));

        $cv = Portfolio::default('hu')->cv;
        $this->assertNotNull($cv->cv_path);
        Storage::disk('public')->assertExists($cv->cv_path);
    }

    public function test_admin_portfolio_pages_render(): void
    {
        $this->actingAs(User::first());

        $portfolio = Portfolio::default('hu');

        $this->get('/admin/portfolios')->assertStatus(200);
        $this->get('/admin/portfolios/create')->assertStatus(200);
        $this->get("/admin/portfolios/{$portfolio->id}/edit")->assertStatus(200);
    }

    public function test_admin_cv_pages_render(): void
    {
        $this->actingAs(User::first());

        $cv = Cv::first();

        $this->get('/admin/cvs')->assertStatus(200);
        $this->get('/admin/cvs/create')->assertStatus(200);
        $this->get("/admin/cvs/{$cv->id}/edit")->assertStatus(200);
    }

    public function test_saving_the_portfolio_form_keeps_both_project_groups(): void
    {
        $this->actingAs(User::first());

        $portfolio = Portfolio::default('hu');
        $selectedBefore = $portfolio->projects()->where('type', ProjectType::Selected)->count();
        $sideBefore = $portfolio->projects()->where('type', ProjectType::SideProject)->count();

        $this->assertGreaterThan(0, $selectedBefore);
        $this->assertGreaterThan(0, $sideBefore);

        // The Selected and Side repeaters both target the `projects` relation;
        // saving must not delete the other group's records.
        Livewire::test(EditPortfolio::class, ['record' => $portfolio->getRouteKey()])
            ->call('save')
            ->assertHasNoFormErrors();

        $this->assertSame($selectedBefore, $portfolio->projects()->where('type', ProjectType::Selected)->count());
        $this->assertSame($sideBefore, $portfolio->projects()->where('type', ProjectType::SideProject)->count());
    }

    public function test_deleting_a_portfolio_cascades_to_its_dependents(): void
    {
        $portfolio = Portfolio::default('hu');
        $cv = $portfolio->cv;

        $portfolio->delete();

        $this->assertDatabaseMissing('cvs', ['id' => $cv->id]);
        $this->assertSame(0, \App\Models\Project::where('portfolio_id', $portfolio->id)->count());
        $this->assertSame(0, \App\Models\Skill::where('portfolio_id', $portfolio->id)->count());
        $this->assertSame(0, \App\Models\WorkExperience::where('cv_id', $cv->id)->count());
        $this->assertSame(0, \App\Models\Education::where('cv_id', $cv->id)->count());
    }

    public function test_single_default_per_locale_is_enforced(): void
    {
        $first = Portfolio::default('hu');

        $second = Portfolio::create([
            'label' => 'Java Junior',
            'slug' => 'java-junior',
            'locale' => 'hu',
            'is_default' => true,
            'full_name' => 'Torma Levente',
            'role' => 'Java Junior Developer',
            'tagline' => 'Tagline',
            'location' => 'Budapest',
            'about' => 'About',
            'email' => 'test@example.com',
        ]);

        $this->assertTrue($second->fresh()->is_default);
        $this->assertFalse($first->fresh()->is_default);
    }
}
