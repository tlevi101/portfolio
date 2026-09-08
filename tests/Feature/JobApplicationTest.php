<?php

namespace Tests\Feature;

use App\Enums\ApplicationMethod;
use App\Enums\ApplicationStatus;
use App\Filament\Resources\Cvs\Pages\EditCv;
use App\Filament\Resources\Cvs\RelationManagers\JobApplicationsRelationManager;
use App\Filament\Resources\JobApplications\Pages\CreateJobApplication;
use App\Filament\Resources\JobApplications\Pages\EditJobApplication;
use App\Filament\Resources\JobApplications\Pages\ListJobApplications;
use App\Models\JobApplication;
use App\Models\Portfolio;
use App\Models\User;
use App\Models\VisitSession;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Tests\TestCase;

class JobApplicationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('public');
        $this->seed();
    }

    /**
     * Signed in as the admin — which the analytics deliberately ignore, so the
     * tests that record a visit must stay signed out.
     */
    protected function asAdmin(): static
    {
        $this->actingAs(User::first());

        return $this;
    }

    protected function application(array $attributes = []): JobApplication
    {
        return JobApplication::query()->create([
            'cv_id' => Portfolio::default('hu')->cv->getKey(),
            'company' => 'Acme',
            'status' => ApplicationStatus::Pending,
            'applied_at' => now(),
            ...$attributes,
        ]);
    }

    public function test_the_list_page_renders(): void
    {
        $this->asAdmin();

        $application = $this->application();

        Livewire::test(ListJobApplications::class)
            ->assertOk()
            ->assertCanSeeTableRecords([$application]);
    }

    public function test_the_edit_page_renders(): void
    {
        $this->asAdmin();

        Livewire::test(EditJobApplication::class, ['record' => $this->application()->getKey()])->assertOk();
    }

    public function test_the_create_page_renders(): void
    {
        $this->asAdmin();

        Livewire::test(CreateJobApplication::class)->assertOk();
    }

    public function test_a_cv_lists_the_jobs_it_was_sent_for(): void
    {
        $this->asAdmin();

        $cv = Portfolio::default('hu')->cv;
        $mine = $this->application();
        $elsewhere = JobApplication::query()->create([
            'cv_id' => $cv->createChild('Globex variant')->getKey(),
            'company' => 'Globex',
            'status' => ApplicationStatus::Pending,
        ]);

        Livewire::test(JobApplicationsRelationManager::class, [
            'ownerRecord' => $cv,
            'pageClass' => EditCv::class,
        ])
            ->assertCanSeeTableRecords([$mine])
            ->assertCanNotSeeTableRecords([$elsewhere]);
    }

    public function test_closed_applications_leave_the_open_tab(): void
    {
        $this->asAdmin();

        $open = $this->application(['company' => 'Acme']);
        $closed = $this->application(['company' => 'Globex', 'status' => ApplicationStatus::Rejected]);

        Livewire::test(ListJobApplications::class)
            ->assertCanSeeTableRecords([$open])
            ->assertCanNotSeeTableRecords([$closed]);
    }

    public function test_an_application_knows_whether_its_cv_link_was_opened(): void
    {
        $cv = Portfolio::default('hu')->cv;
        $application = $this->application();

        $this->assertFalse($application->wasOpened());

        $this->postJson('/beacon', [
            'event' => 'page_view',
            'path' => '/fullstack?r='.$cv->trackingToken(),
        ])->assertNoContent();

        $this->assertTrue($application->wasOpened());
        $this->assertFalse($application->cvWasDownloaded());
    }

    public function test_a_download_in_an_attributed_session_reaches_the_application(): void
    {
        $cv = Portfolio::default('hu')->cv;
        $application = $this->application();

        $this->postJson('/beacon', ['event' => 'page_view', 'path' => '/fullstack?r='.$cv->trackingToken()])->assertNoContent();
        // The download link on the site carries no token of its own; the visit
        // belongs to the application because it is part of the same session.
        $this->get('/cv/fullstack')->assertOk();

        $this->assertTrue($application->cvWasDownloaded());
        $this->assertSame(1, VisitSession::count());
    }

    public function test_a_visit_through_another_cv_is_not_credited_to_this_application(): void
    {
        $cv = Portfolio::default('hu')->cv;
        $variant = $cv->createChild('Globex variant');
        $onTheMaster = $this->application();

        $this->postJson('/beacon', [
            'event' => 'page_view',
            'path' => '/fullstack?r='.$variant->trackingToken(),
        ])->assertNoContent();

        $this->assertFalse($onTheMaster->wasOpened());
    }

    public function test_bots_do_not_count_as_having_opened_the_portfolio(): void
    {
        $cv = Portfolio::default('hu')->cv;
        $application = $this->application();

        $this->postJson('/beacon', [
            'event' => 'page_view',
            'path' => '/fullstack?r='.$cv->trackingToken(),
        ], ['User-Agent' => 'Mozilla/5.0 (compatible; AhrefsBot/7.0)'])->assertNoContent();

        // A crawler following a link out of an indexed PDF is not a recruiter.
        $this->assertFalse($application->wasOpened());
    }

    public function test_the_channel_is_named_when_the_enum_only_says_there_was_one(): void
    {
        $ats = $this->application(['method' => ApplicationMethod::Ats, 'method_detail' => 'Greenhouse']);
        $linkedin = $this->application(['method' => ApplicationMethod::LinkedIn, 'method_detail' => 'ignored']);

        $this->assertSame('Greenhouse', $ats->methodLabel());
        $this->assertSame('LinkedIn', $linkedin->methodLabel());
        $this->assertNull($this->application()->methodLabel());
    }

    public function test_required_skills_print_with_their_years_where_the_ad_gave_one(): void
    {
        $application = $this->application(['required_skills' => [
            ['name' => 'Laravel', 'years' => 3],
            ['name' => 'React', 'years' => null],
            ['name' => '', 'years' => 5],
        ]]);

        $this->assertSame(['Laravel (3 y)', 'React'], $application->requiredSkillLines());
    }

    public function test_deleting_the_cv_deletes_the_applications_sent_with_it(): void
    {
        $cv = Portfolio::default('hu')->cv;
        $variant = $cv->createChild('Globex variant');

        JobApplication::query()->create([
            'cv_id' => $variant->getKey(),
            'company' => 'Globex',
            'status' => ApplicationStatus::Pending,
        ]);

        $variant->delete();

        // An application records which CV was sent; without it there is nothing
        // left for the record to be about.
        $this->assertSame(0, JobApplication::query()->count());
    }
}
