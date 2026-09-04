<?php

namespace Tests\Feature;

use App\Filament\Resources\Cvs\Pages\EditCv;
use App\Filament\Resources\Cvs\Pages\ListCvs;
use App\Models\Cv;
use App\Models\Portfolio;
use App\Models\User;
use App\Services\CvGeneratorService;
use Database\Seeders\CvContentFromPortfolioSeeder;
use Filament\Actions\Testing\TestAction;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Tests\TestCase;

class CvTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // Saving through the form wakes the regeneration observer, which writes a
        // real PDF; keep that off the development storage directory.
        Storage::fake('public');

        // DatabaseSeeder seeds with model events muted, so no CV PDF is built here.
        $this->seed();
    }

    public function test_the_seeder_gives_every_cv_its_own_copy_of_the_portfolio_content(): void
    {
        $cv = Portfolio::default('hu')->cv;
        $portfolio = $cv->portfolio;

        $this->assertSame($portfolio->full_name, $cv->full_name);
        $this->assertSame($portfolio->email, $cv->email);
        $this->assertSame($portfolio->tagline, $cv->summary);
        $this->assertSame($portfolio->avatar_path, $cv->avatar_path);
        $this->assertSame($portfolio->languages, $cv->languages);

        $this->assertSame($portfolio->skills()->count(), $cv->skills()->count());
        $this->assertGreaterThan(0, $cv->projects()->count());
        $this->assertNotEmpty($cv->stack_highlights);
    }

    public function test_the_seeder_never_overwrites_content_the_cv_already_has(): void
    {
        $cv = Portfolio::default('hu')->cv;

        $cv->forceFill(['full_name' => 'Edited By Hand', 'summary' => 'Own summary'])->saveQuietly();
        $cv->skills()->delete();
        $cv->skills()->create(['group' => 'Backend', 'name' => 'Only This One', 'sort_order' => 0]);

        (new CvContentFromPortfolioSeeder)->run();

        $cv->refresh();

        $this->assertSame('Edited By Hand', $cv->full_name);
        $this->assertSame('Own summary', $cv->summary);
        $this->assertSame(1, $cv->skills()->count());
    }

    public function test_editing_the_portfolio_leaves_the_cv_content_untouched(): void
    {
        $portfolio = Portfolio::default('hu');
        $cv = $portfolio->cv;

        $portfolio->update(['full_name' => 'Site Only Name', 'tagline' => 'Site only tagline']);
        $portfolio->skills()->delete();

        $cv->refresh();

        $this->assertNotSame('Site Only Name', $cv->full_name);
        $this->assertNotSame('Site only tagline', $cv->summary);
        $this->assertGreaterThan(0, $cv->skills()->count());
    }

    public function test_the_rendered_cv_uses_its_own_content(): void
    {
        $cv = Portfolio::default('hu')->cv;

        $cv->forceFill([
            'full_name' => 'Preview Person',
            'stack_highlights' => ['Laravel', 'Livewire'],
        ])->saveQuietly();

        $html = app(CvGeneratorService::class)->renderHtml($cv->refresh());

        $this->assertStringContainsString('Preview Person', $html);
        $this->assertStringContainsString('Laravel', $html);
        // Chips, not the old comma-separated lists.
        $this->assertStringContainsString('class="chip"', $html);
    }

    public function test_the_download_link_carries_the_cv_version(): void
    {
        $portfolio = Portfolio::default('hu');

        $before = $portfolio->cvDownloadUrl();
        $this->assertStringContainsString('v='.$portfolio->cv->downloadVersion(), $before);

        // A rebuilt PDF must produce a different URL, otherwise phones keep
        // serving the copy they already downloaded.
        $portfolio->cv->forceFill(['cv_path' => 'cv/something-else.pdf'])->saveQuietly();

        $this->assertNotSame($before, $portfolio->fresh()->cvDownloadUrl());
    }

    public function test_the_live_preview_renders_unsaved_form_state(): void
    {
        $this->actingAs(User::first());

        $cv = Cv::first();

        $component = Livewire::test(EditCv::class, ['record' => $cv->getRouteKey()])
            ->set('data.full_name', 'Unsaved Name');

        $html = Cache::get($component->instance()->getCvPreviewCacheKey());

        $this->assertNotNull($html);
        $this->assertStringContainsString('Unsaved Name', $html);

        // Only the preview changed — nothing was written to the record.
        $this->assertNotSame('Unsaved Name', $cv->fresh()->full_name);
    }

    public function test_the_preview_endpoint_is_not_public(): void
    {
        $this->get('/admin/cv-preview/anything')->assertRedirect();
    }

    public function test_saving_the_cv_form_keeps_its_related_records(): void
    {
        $this->actingAs(User::first());

        $cv = Cv::first();

        $counts = fn (): array => [
            'skills' => $cv->skills()->count(),
            'projects' => $cv->projects()->count(),
            'work' => $cv->workExperiences()->count(),
            'education' => $cv->education()->count(),
        ];

        $before = $counts();
        $this->assertGreaterThan(0, $before['skills']);

        Livewire::test(EditCv::class, ['record' => $cv->getRouteKey()])
            ->call('save')
            ->assertHasNoFormErrors();

        $this->assertSame($before, $counts());
    }

    public function test_duplicating_a_cv_copies_all_of_its_own_content(): void
    {
        $cv = Portfolio::default('hu')->cv;

        $copy = $cv->duplicate('Java Junior CV');

        $this->assertNotSame($cv->getKey(), $copy->getKey());
        $this->assertSame('Java Junior CV', $copy->label);
        $this->assertSame($cv->full_name, $copy->full_name);
        $this->assertSame($cv->stack_highlights, $copy->stack_highlights);
        $this->assertSame($cv->portfolio_id, $copy->portfolio_id);

        // The copy renders its own PDF rather than pointing at the original's.
        $this->assertNull($copy->cv_path);

        foreach (['skills', 'projects', 'workExperiences', 'education'] as $relation) {
            $this->assertSame(
                $cv->{$relation}()->count(),
                $copy->{$relation}()->count(),
                "{$relation} were not copied",
            );
        }

        $this->assertSame(
            $cv->skills()->orderBy('sort_order')->pluck('name')->all(),
            $copy->skills()->orderBy('sort_order')->pluck('name')->all(),
        );
    }

    public function test_editing_a_duplicate_leaves_the_original_alone(): void
    {
        $cv = Portfolio::default('hu')->cv;
        $copy = $cv->duplicate('Retargeted CV');

        $copy->forceFill(['full_name' => 'Changed On The Copy'])->saveQuietly();
        $copy->skills()->delete();

        $this->assertNotSame('Changed On The Copy', $cv->fresh()->full_name);
        $this->assertGreaterThan(0, $cv->skills()->count());
    }

    public function test_the_duplicate_action_creates_a_copy_from_the_table(): void
    {
        $this->actingAs(User::first());

        $cv = Cv::first();
        $before = Cv::count();

        Livewire::test(ListCvs::class)
            ->callAction(TestAction::make('duplicateCv')->table($cv), ['label' => 'From The Table']);

        $this->assertSame($before + 1, Cv::count());
        $this->assertDatabaseHas('cvs', ['label' => 'From The Table']);
    }

    public function test_the_download_action_serves_the_pdf_and_builds_it_when_missing(): void
    {
        $this->actingAs(User::first());

        $cv = Cv::first();
        $cv->forceFill(['cv_path' => null])->saveQuietly();

        Livewire::test(EditCv::class, ['record' => $cv->getRouteKey()])
            ->callAction('downloadCv')
            ->assertFileDownloaded($cv->fresh()->downloadFilename());

        $this->assertNotNull($cv->fresh()->cv_path);
    }

    public function test_the_download_filename_is_built_from_the_cv_name(): void
    {
        $cv = Cv::first();

        $cv->forceFill(['full_name' => 'Tormá Levente'])->saveQuietly();
        $this->assertSame('Torma_Levente_CV.pdf', $cv->fresh()->downloadFilename());

        $cv->forceFill(['full_name' => null])->saveQuietly();
        $this->assertSame('cv_CV.pdf', $cv->fresh()->downloadFilename());
    }

    public function test_deleting_a_cv_cascades_to_its_own_content(): void
    {
        $cv = Cv::first();
        $cvId = $cv->id;

        $cv->delete();

        $this->assertDatabaseMissing('cv_skills', ['cv_id' => $cvId]);
        $this->assertDatabaseMissing('cv_projects', ['cv_id' => $cvId]);
        $this->assertDatabaseMissing('work_experiences', ['cv_id' => $cvId]);
        $this->assertDatabaseMissing('education', ['cv_id' => $cvId]);
    }
}
