<?php

namespace Tests\Feature;

use App\Enums\ApplicationStatus;
use App\Filament\Resources\Cvs\Pages\EditCv;
use App\Models\Cv;
use App\Models\JobApplication;
use App\Models\Portfolio;
use App\Models\User;
use App\Services\CvFormState;
use App\Services\CvImportResult;
use App\Services\CvJsonExporter;
use App\Services\CvJsonImporter;
use App\Services\JobApplicationImporter;
use Filament\Forms\Components\Repeater;
use Filament\Schemas\Components\Component;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Tests\TestCase;

class CvJsonImportTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('public');
        $this->seed();
        $this->actingAs(User::first());
    }

    /**
     * @return array<string, mixed>
     */
    protected function stateOf(Cv $cv): array
    {
        return app(CvFormState::class)->sanitize(
            Livewire::test(EditCv::class, ['record' => $cv->getRouteKey()])->get('data'),
        );
    }

    /**
     * @return array<string, mixed>
     */
    protected function exportOf(Cv $cv): array
    {
        return app(CvJsonExporter::class)->fromFormState($this->stateOf($cv), $cv->getKey());
    }

    /**
     * @param  array<string, mixed>  $document
     */
    protected function import(array $document, Cv $target, ?array $current = null): CvImportResult
    {
        return app(CvJsonImporter::class)->merge(
            (string) json_encode($document),
            $target,
            $current ?? $this->stateOf($target),
        );
    }

    public function test_a_document_survives_the_round_trip_unchanged(): void
    {
        $cv = Portfolio::default('hu')->cv;
        $original = $this->exportOf($cv);

        $result = $this->import($original, $cv);
        $this->assertFalse($result->failed(), implode(' ', $result->errors));

        // Exporting the state the import produced must give the document back.
        // Anything the two directions disagree about shows up here.
        $this->assertSame(
            $original,
            app(CvJsonExporter::class)->fromFormState($result->state, $cv->getKey()),
        );
    }

    public function test_the_retuned_content_lands_in_the_form(): void
    {
        $cv = Portfolio::default('hu')->cv;
        $document = $this->exportOf($cv);

        $document['role'] = 'Senior Backend Engineer';
        $document['summary'] = '<p>Rewritten for the ad.</p>';
        $document['stack_highlights'] = ['PHP', 'Laravel'];
        $document['skills']['Backend'] = ['PHP', 'Laravel', 'MariaDB'];
        $document['experience'][0]['bullets'] = ['Rewritten bullet.'];

        $state = $this->import($document, $cv)->state;

        $this->assertSame('Senior Backend Engineer', $state['role']);
        $this->assertSame(['PHP', 'Laravel'], $state['stack_highlights']);
        $this->assertSame(['PHP', 'Laravel', 'MariaDB'], array_column($state['skillsBackend'], 'name'));

        $bullets = array_values(reset($state['workExperiences'])['bullets']);
        $this->assertSame([['item' => 'Rewritten bullet.']], $bullets);
    }

    public function test_read_only_fields_are_discarded(): void
    {
        $cv = Portfolio::default('hu')->cv;
        $document = $this->exportOf($cv);

        $document['full_name'] = 'Someone Else';
        $document['avatar_path'] = 'profile/not-mine.jpg';
        $document['email'] = 'attacker@example.com';

        $state = $this->import($document, $cv)->state;

        $this->assertSame($cv->full_name, $state['full_name']);
        $this->assertNotSame('profile/not-mine.jpg', $state['avatar_path']);
        $this->assertSame($cv->email, $state['email']);
    }

    public function test_fields_the_document_leaves_out_are_left_alone(): void
    {
        $cv = Portfolio::default('hu')->cv;
        // Compared against the very state handed to the import: Filament keys
        // repeater rows afresh on every fill, so two mounts of the same form
        // never produce identical arrays.
        $before = $this->stateOf($cv);

        // Retuning one section should not mean sending the whole CV back.
        $state = $this->import(['id' => $cv->getKey(), 'role' => 'Only This Changed'], $cv, $before)->state;

        $this->assertSame('Only This Changed', $state['role']);
        $this->assertSame($before['summary'], $state['summary']);
        $this->assertSame($before['workExperiences'], $state['workExperiences']);
        $this->assertSame($before['skillsBackend'], $state['skillsBackend']);
    }

    public function test_a_document_from_another_cv_is_refused(): void
    {
        $cv = Portfolio::default('hu')->cv;
        $other = $cv->duplicate('Unrelated CV');

        $result = $this->import($this->exportOf($other), $cv);

        $this->assertTrue($result->failed());
        $this->assertStringContainsString('Unrelated CV', implode(' ', $result->errors));
    }

    public function test_a_document_from_the_master_is_accepted_by_its_variant(): void
    {
        $cv = Portfolio::default('hu')->cv;
        $document = $this->exportOf($cv);

        // Exporting first and creating the variant afterwards is a natural order
        // to work in, and the variant began as a copy of this very document.
        $variant = $cv->createChild('Acme — Senior Backend');

        $this->assertFalse($this->import($document, $variant)->failed());
    }

    public function test_a_document_without_an_id_is_refused(): void
    {
        $cv = Portfolio::default('hu')->cv;
        $document = $this->exportOf($cv);
        unset($document['id']);

        $result = $this->import($document, $cv);

        $this->assertTrue($result->failed());
        $this->assertStringContainsString('id', implode(' ', $result->errors));
    }

    public function test_a_document_in_another_language_is_refused(): void
    {
        $cv = Portfolio::default('hu')->cv;
        $document = $this->exportOf($cv);
        $document['locale'] = 'en';

        $this->assertTrue($this->import($document, $cv)->failed());
    }

    public function test_an_over_long_field_is_refused_and_nothing_is_filled(): void
    {
        $cv = Portfolio::default('hu')->cv;
        $document = $this->exportOf($cv);
        $document['summary'] = str_repeat('a', 501);

        $result = $this->import($document, $cv);

        $this->assertTrue($result->failed());
        $this->assertNull($result->state);
        $this->assertStringContainsString('501', implode(' ', $result->errors));
    }

    public function test_a_renamed_field_is_refused_rather_than_ignored(): void
    {
        $cv = Portfolio::default('hu')->cv;

        // Dropping it in silence would leave the section it was meant to
        // replace untouched, and look like a successful import.
        $result = $this->import([
            'id' => $cv->getKey(),
            'experiences' => [],
        ], $cv);

        $this->assertTrue($result->failed());
        $this->assertStringContainsString('experiences', implode(' ', $result->errors));
    }

    public function test_a_fenced_document_with_prose_around_it_is_read(): void
    {
        $cv = Portfolio::default('hu')->cv;

        $pasted = "Sure! Here's the retuned CV:\n\n```json\n"
            .json_encode(['id' => $cv->getKey(), 'role' => 'Fenced Role'], JSON_PRETTY_PRINT)
            ."\n```\n\nLet me know if you'd like changes.";

        $result = app(CvJsonImporter::class)->merge($pasted, $cv, $this->stateOf($cv));

        $this->assertFalse($result->failed(), implode(' ', $result->errors));
        $this->assertSame('Fenced Role', $result->state['role']);
    }

    public function test_a_trailing_comma_is_forgiven(): void
    {
        $cv = Portfolio::default('hu')->cv;

        $result = app(CvJsonImporter::class)->merge(
            '{"id": '.$cv->getKey().', "role": "Sloppy Role",}',
            $cv,
            $this->stateOf($cv),
        );

        $this->assertFalse($result->failed(), implode(' ', $result->errors));
        $this->assertSame('Sloppy Role', $result->state['role']);
    }

    public function test_a_row_missing_an_optional_field_still_saves(): void
    {
        $cv = Portfolio::default('hu')->cv;

        $result = $this->import([
            'id' => $cv->getKey(),
            'experience' => [['company' => 'Acme', 'title' => 'Engineer', 'bullets' => ['Did the work.']]],
        ], $cv);

        $this->assertFalse($result->failed(), implode(' ', $result->errors));

        // An AI will happily leave out a period; that must not surface as a
        // database error long after the import reported success.
        Livewire::test(EditCv::class, ['record' => $cv->getRouteKey()])
            ->set('data', $result->state)
            ->call('save')
            ->assertHasNoFormErrors();

        $this->assertSame('Acme', $cv->fresh()->workExperiences()->sole()->company);
    }

    public function test_the_import_action_saves_the_cv(): void
    {
        $cv = Portfolio::default('hu')->cv;
        $document = $this->exportOf($cv);
        $document['role'] = 'Imported Role';

        Livewire::test(EditCv::class, ['record' => $cv->getRouteKey()])
            ->callAction('importCvJson', ['json' => (string) json_encode($document)])
            ->assertHasNoActionErrors()
            ->assertSet('data.role', 'Imported Role');

        // Importing is the last step of the round trip, not the middle of one:
        // a CV left filled in but unsaved is a CV that looks finished and is not.
        $this->assertSame('Imported Role', $cv->fresh()->role);
    }

    public function test_an_envelope_carries_the_cv_and_the_job_it_was_tuned_for(): void
    {
        $cv = Portfolio::default('hu')->cv;

        $result = $this->import([
            'cv' => ['id' => $cv->getKey(), 'role' => 'Senior Backend Engineer'],
            'job_application' => [
                'company' => 'Acme',
                'title' => 'Senior Backend Engineer',
                'method' => 'linkedin',
                'experience_level' => 'senior',
                'required_years' => 5,
                'required_skills' => [
                    ['name' => 'Laravel', 'years' => 3],
                    ['name' => 'React', 'years' => null],
                ],
                'job_ad' => 'We are looking for a senior backend engineer.',
            ],
        ], $cv);

        $this->assertFalse($result->failed(), implode(' ', $result->errors));
        $this->assertSame('Senior Backend Engineer', $result->state['role']);
        $this->assertSame('Acme', $result->jobApplication['company']);
    }

    public function test_an_imported_job_application_is_recorded_as_pending(): void
    {
        $cv = Portfolio::default('hu')->cv;

        $application = app(JobApplicationImporter::class)->apply([
            'company' => 'Acme',
            'method' => 'ats',
            'method_detail' => 'Greenhouse',
            'required_skills' => [['name' => 'Laravel', 'years' => 3]],
        ], $cv);

        $this->assertSame(ApplicationStatus::Pending, $application->status);
        $this->assertSame($cv->getKey(), $application->cv_id);
        $this->assertSame('Greenhouse', $application->methodLabel());
        $this->assertSame([['name' => 'Laravel', 'years' => 3]], $application->required_skills);
        // The ad rarely says when it was answered, so the import supplies it.
        $this->assertNotNull($application->applied_at);
    }

    public function test_an_application_carrying_its_own_id_is_updated_rather_than_duplicated(): void
    {
        $cv = Portfolio::default('hu')->cv;
        $existing = app(JobApplicationImporter::class)->apply(['company' => 'Acme', 'notes' => 'Referred by a friend.'], $cv);

        $updated = app(JobApplicationImporter::class)->apply([
            'id' => $existing->getKey(),
            'company' => 'Acme Ltd',
        ], $cv);

        $this->assertSame($existing->getKey(), $updated->getKey());
        $this->assertSame('Acme Ltd', $updated->company);
        // A second pass that says nothing about the notes must not erase them.
        $this->assertSame('Referred by a friend.', $updated->notes);
        $this->assertSame(1, JobApplication::query()->count());
    }

    public function test_an_application_belonging_to_another_cv_starts_a_new_record(): void
    {
        $cv = Portfolio::default('hu')->cv;
        $variant = $cv->createChild('Acme variant');
        $onTheMaster = app(JobApplicationImporter::class)->apply(['company' => 'Acme'], $cv);

        // Exporting the master, then cutting the variant, carries the master's
        // application id along. Moving the record would leave the master's own
        // history wrong; the variant gets one of its own instead.
        $onTheVariant = app(JobApplicationImporter::class)->apply([
            'id' => $onTheMaster->getKey(),
            'company' => 'Acme',
        ], $variant);

        $this->assertNotSame($onTheMaster->getKey(), $onTheVariant->getKey());
        $this->assertSame($cv->getKey(), $onTheMaster->fresh()->cv_id);
        $this->assertSame($variant->getKey(), $onTheVariant->cv_id);
    }

    public function test_an_unknown_envelope_key_is_refused(): void
    {
        $cv = Portfolio::default('hu')->cv;

        $result = $this->import([
            'cv' => ['id' => $cv->getKey()],
            'jobApplication' => ['company' => 'Acme'],
        ], $cv);

        $this->assertTrue($result->failed());
        $this->assertStringContainsString('jobApplication', implode(' ', $result->errors));
    }

    public function test_a_job_field_of_the_wrong_type_is_refused(): void
    {
        $cv = Portfolio::default('hu')->cv;

        $result = $this->import([
            'cv' => ['id' => $cv->getKey()],
            'job_application' => ['company' => 'Acme', 'required_years' => 'about five'],
        ], $cv);

        $this->assertTrue($result->failed());
        $this->assertStringContainsString('required_years', implode(' ', $result->errors));
    }

    public function test_an_unknown_job_field_is_refused(): void
    {
        $cv = Portfolio::default('hu')->cv;

        $result = $this->import([
            'cv' => ['id' => $cv->getKey()],
            'job_application' => ['company' => 'Acme', 'salary' => '2000 EUR'],
        ], $cv);

        $this->assertTrue($result->failed());
        $this->assertStringContainsString('salary', implode(' ', $result->errors));
    }

    public function test_the_import_action_records_the_job_application(): void
    {
        $cv = Portfolio::default('hu')->cv;

        Livewire::test(EditCv::class, ['record' => $cv->getRouteKey()])
            ->callAction('importCvJson', ['json' => (string) json_encode([
                'cv' => ['id' => $cv->getKey(), 'role' => 'Senior Backend Engineer'],
                'job_application' => ['company' => 'Acme', 'method' => 'email'],
            ])])
            ->assertHasNoActionErrors();

        $application = JobApplication::query()->sole();

        $this->assertSame('Acme', $application->company);
        $this->assertSame($cv->getKey(), $application->cv_id);
        $this->assertSame(ApplicationStatus::Pending, $application->status);
        $this->assertSame('Senior Backend Engineer', $cv->fresh()->role);
    }

    public function test_the_export_carries_the_application_already_on_record(): void
    {
        $cv = Portfolio::default('hu')->cv;
        app(JobApplicationImporter::class)->apply(['company' => 'Acme', 'title' => 'Backend Engineer'], $cv);

        $document = app(CvJsonExporter::class)->document(
            $this->stateOf($cv),
            $cv->getKey(),
            $cv->jobApplications()->first(),
        );

        $this->assertSame($cv->getKey(), $document['cv']['id']);
        $this->assertSame('Acme', $document['job_application']['company']);
        // Its id travels so a second pass updates it instead of duplicating it.
        $this->assertNotNull($document['job_application']['id']);
    }

    public function test_the_form_still_renders_after_an_import(): void
    {
        $cv = Portfolio::default('hu')->cv;
        $document = $this->exportOf($cv);
        $document['experience'] = [[
            'company' => 'Acme Rendering',
            'title' => 'Engineer',
            'period' => '2024 – Present',
            'location' => 'Budapest',
            'bullets' => ['Did the work.'],
        ]];

        // Imported rows get keys the repeater has never seen. Writing the state
        // straight onto the component leaves it without a child schema for them,
        // and a collapsible repeater asking for its item labels then renders
        // against nothing.
        $component = Livewire::test(EditCv::class, ['record' => $cv->getRouteKey()])
            ->callAction('importCvJson', ['json' => (string) json_encode($document)])
            ->assertHasNoActionErrors()
            ->assertSee('Acme Rendering');

        // A smoke test, not a regression guard. The crash this follows needs a
        // repeater that was resolved before the import and re-rendered after it
        // in the same request; the test harness builds the schema afresh here,
        // so it passes either way. Verify that path in a browser.
        $repeater = $component->instance()
            ->getSchema('form')
            ->getComponent(fn (Component $item): bool => $item instanceof Repeater && $item->getName() === 'workExperiences');

        $this->assertInstanceOf(Repeater::class, $repeater);

        foreach (array_keys($repeater->getRawState()) as $key) {
            $this->assertNotNull(
                $repeater->getChildSchema($key),
                "the repeater has no child schema for imported row {$key}",
            );
        }
    }

    public function test_the_import_action_refuses_a_foreign_document(): void
    {
        $cv = Portfolio::default('hu')->cv;
        $other = $cv->duplicate('Someone Elses CV');

        Livewire::test(EditCv::class, ['record' => $cv->getRouteKey()])
            ->callAction('importCvJson', ['json' => (string) json_encode($this->exportOf($other))])
            ->assertActionHalted('importCvJson')
            ->assertSet('data.role', $cv->role);
    }
}
