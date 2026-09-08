<?php

namespace Tests\Feature;

use App\Filament\Resources\Cvs\Pages\EditCv;
use App\Models\Cv;
use App\Models\Portfolio;
use App\Models\User;
use App\Services\CvFormState;
use App\Services\CvJsonExporter;
use App\Services\CvSchema;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Tests\TestCase;

class CvJsonExportTest extends TestCase
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
     * The document built from a CV's real form state — the same array the
     * export action reads, so the mapping is tested against what Filament
     * actually puts there rather than a hand-written approximation.
     *
     * @param  array<string, mixed>  $edits
     * @return array<string, mixed>
     */
    protected function export(Cv $cv, array $edits = []): array
    {
        $component = Livewire::test(EditCv::class, ['record' => $cv->getRouteKey()]);

        foreach ($edits as $key => $value) {
            $component->set("data.{$key}", $value);
        }

        return app(CvJsonExporter::class)->fromFormState(
            app(CvFormState::class)->sanitize($component->get('data')),
            $cv->getKey(),
        );
    }

    /**
     * @return array<string, mixed>
     */
    protected function schema(string $name): array
    {
        return app(CvSchema::class)->toArray()['components']['schemas'][$name];
    }

    public function test_the_document_carries_exactly_the_fields_the_schema_declares(): void
    {
        $payload = $this->export(Portfolio::default('hu')->cv);

        $expected = array_keys($this->schema('Cv')['properties']);
        $actual = array_keys($payload);

        sort($expected);
        sort($actual);

        // A field in one and not the other means the skill is describing a CV
        // that does not exist, or the AI is being handed one it cannot read.
        $this->assertSame($expected, $actual);
    }

    public function test_the_nested_entries_carry_exactly_their_declared_fields(): void
    {
        $payload = $this->export(Portfolio::default('hu')->cv);

        foreach ([['experience', 'WorkExperience'], ['projects', 'Project'], ['education', 'Education']] as [$key, $schema]) {
            $this->assertNotEmpty($payload[$key], "{$key} needs a row to be meaningful");

            $expected = array_keys($this->schema($schema)['properties']);
            $actual = array_keys($payload[$key][0]);

            sort($expected);
            sort($actual);

            $this->assertSame($expected, $actual, "{$key} rows do not match the schema");
        }
    }

    public function test_the_document_identifies_the_cv_it_came_from(): void
    {
        $cv = Portfolio::default('hu')->cv;

        $payload = $this->export($cv);

        $this->assertSame($cv->getKey(), $payload['id']);
        $this->assertSame($cv->full_name, $payload['full_name']);
        $this->assertSame($cv->locale, $payload['locale']);
        $this->assertSame($cv->avatar_path, $payload['avatar_path']);
    }

    public function test_the_document_reflects_unsaved_edits(): void
    {
        $cv = Portfolio::default('hu')->cv;

        $payload = $this->export($cv, ['role' => 'Unsaved Role']);

        // Exporting the record instead of the form would hand the AI something
        // other than what the preview beside it is showing.
        $this->assertSame('Unsaved Role', $payload['role']);
        $this->assertNotSame('Unsaved Role', $cv->fresh()->role);
    }

    public function test_repeaters_travel_as_ordered_lists_without_the_form_bookkeeping(): void
    {
        $cv = Portfolio::default('hu')->cv;
        $cv->workExperiences()->delete();
        $cv->workExperiences()->create(['company' => 'Second', 'title' => 'B', 'period' => '2022 – 2023', 'location' => 'Budapest', 'sort_order' => 1, 'bullets' => ['b1']]);
        $cv->workExperiences()->create(['company' => 'First', 'title' => 'A', 'period' => '2024 – Present', 'location' => 'Budapest', 'sort_order' => 0, 'bullets' => ['a1', 'a2']]);

        $payload = $this->export($cv->refresh());

        $this->assertSame(['First', 'Second'], array_column($payload['experience'], 'company'));
        $this->assertSame([0, 1], array_keys($payload['experience']));

        // A `simple()` repeater nests its values under an `item` key; the
        // document carries the strings themselves.
        $this->assertSame(['a1', 'a2'], $payload['experience'][0]['bullets']);

        foreach (['sort_order', 'id', 'cv_id'] as $bookkeeping) {
            $this->assertArrayNotHasKey($bookkeeping, $payload['experience'][0]);
        }
    }

    public function test_skills_travel_grouped_the_way_they_are_printed(): void
    {
        $cv = Portfolio::default('hu')->cv;
        $cv->skills()->delete();
        $cv->skills()->create(['group' => 'Backend', 'name' => 'PHP', 'sort_order' => 0]);
        $cv->skills()->create(['group' => 'Backend', 'name' => 'Laravel', 'sort_order' => 1]);
        $cv->skills()->create(['group' => 'Frontend', 'name' => 'Livewire', 'sort_order' => 0]);

        $payload = $this->export($cv->refresh());

        $this->assertSame(['PHP', 'Laravel'], $payload['skills']['Backend']);
        $this->assertSame(['Livewire'], $payload['skills']['Frontend']);
        // Every group is present so there is somewhere to write into.
        $this->assertSame([], $payload['skills']['Tools']);
    }

    public function test_the_summary_travels_as_html(): void
    {
        $cv = Portfolio::default('hu')->cv;
        $cv->forceFill(['summary' => '<p>A <strong>bold</strong> claim.</p>'])->saveQuietly();

        $payload = $this->export($cv->refresh());

        $this->assertStringContainsString('<strong>bold</strong>', (string) $payload['summary']);
    }

    public function test_the_export_action_offers_the_document_and_the_schema(): void
    {
        $cv = Portfolio::default('hu')->cv;
        $cv->forceFill(['role' => 'Recognisable Role'])->saveQuietly();

        Livewire::test(EditCv::class, ['record' => $cv->fresh()->getRouteKey()])
            ->mountAction('exportCvJson')
            ->assertMountedActionModalSee('Recognisable Role')
            // The schema pane carries the generated contract verbatim.
            ->assertMountedActionModalSee('#/components/schemas/WorkExperience');
    }
}
