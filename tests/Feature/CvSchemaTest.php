<?php

namespace Tests\Feature;

use App\Console\Commands\GenerateCvSchema;
use App\Enums\ApplicationMethod;
use App\Enums\ExperienceLevel;
use App\Enums\SkillGroup;
use App\Filament\Resources\Cvs\Schemas\CvForm;
use App\Models\JobApplication;
use App\Services\CvSchema;
use Symfony\Component\Yaml\Yaml;
use Tests\TestCase;

class CvSchemaTest extends TestCase
{
    protected function schemaPath(): string
    {
        return base_path(GenerateCvSchema::DEFAULT_PATH);
    }

    public function test_the_committed_schema_is_valid_yaml_and_matches_the_generator(): void
    {
        $this->assertFileExists($this->schemaPath(), 'run `php artisan cv:schema`');

        // Parsing it proves the hand-rolled dump is real YAML; comparing it
        // proves the skill is not describing a CV the application has moved on
        // from. Regenerate with `php artisan cv:schema` when this fails.
        $this->assertSame(
            app(CvSchema::class)->toArray(),
            Yaml::parseFile($this->schemaPath()),
        );
    }

    public function test_regenerating_the_schema_reproduces_the_committed_file_byte_for_byte(): void
    {
        $path = tempnam(sys_get_temp_dir(), 'cv-schema');

        try {
            $this->artisan('cv:schema', ['--path' => $path])->assertSuccessful();

            $this->assertSame(file_get_contents($this->schemaPath()), file_get_contents($path));
        } finally {
            @unlink($path);
        }
    }

    public function test_the_schema_carries_the_limits_the_form_enforces(): void
    {
        $properties = app(CvSchema::class)->toArray()['components']['schemas']['Cv']['properties'];

        // The point of generating the document: a limit stated to an AI that the
        // form does not actually enforce is worse than none at all.
        $this->assertSame(CvForm::TEXT_LIMIT, $properties['summary']['maxLength']);
        $this->assertSame(CvForm::TEXT_LIMIT, $properties['role']['maxLength']);
        $this->assertSame('text/html', $properties['summary']['contentMediaType']);

        $this->assertSame(
            array_column(SkillGroup::cases(), 'value'),
            array_keys($properties['skills']['properties']),
        );
    }

    public function test_the_skill_describes_every_field_the_schema_declares(): void
    {
        $skill = base_path('.claude/skills/cv-tuning/SKILL.md');

        $this->assertFileExists($skill);

        $prose = (string) file_get_contents($skill);
        $schemas = app(CvSchema::class)->toArray()['components']['schemas'];

        // The skill is what an AI actually reads; the schema beside it is the
        // fine print. A field renamed in one and not the other leaves the skill
        // teaching a CV that no longer exists.
        foreach (['Cv', 'JobApplication'] as $name) {
            foreach (array_keys($schemas[$name]['properties']) as $field) {
                $this->assertStringContainsString($field, $prose, "the skill never mentions {$name}.{$field}");
            }
        }

        $this->assertStringContainsString((string) CvForm::TEXT_LIMIT, $prose);
    }

    public function test_the_root_carries_both_halves_of_a_tuning_session(): void
    {
        $schemas = app(CvSchema::class)->toArray()['components']['schemas'];
        $root = $schemas['CvExport'];

        $this->assertSame(['cv'], $root['required']);
        $this->assertSame('#/components/schemas/Cv', $root['properties']['cv']['$ref']);
        $this->assertSame('#/components/schemas/JobApplication', $root['properties']['job_application']['$ref']);
    }

    public function test_the_job_half_offers_only_the_channels_the_admin_knows(): void
    {
        $properties = app(CvSchema::class)->toArray()['components']['schemas']['JobApplication']['properties'];

        // An enum value the application cannot store would be filled in by the
        // AI and then silently dropped on the way back in.
        $this->assertSame(array_column(ApplicationMethod::cases(), 'value'), $properties['method']['enum']);
        $this->assertSame(array_column(ExperienceLevel::cases(), 'value'), $properties['experience_level']['enum']);
        $this->assertSame(JobApplication::TEXT_LIMIT, $properties['company']['maxLength']);

        // Only the company must be filled in; everything else is nullable
        // because job ads leave it out.
        $this->assertSame(['company'], app(CvSchema::class)->toArray()['components']['schemas']['JobApplication']['required']);

        // The status is the admin's to move along, not the document's to report.
        $this->assertArrayNotHasKey('status', $properties);
    }

    public function test_the_identity_block_and_the_guard_are_read_only(): void
    {
        $properties = app(CvSchema::class)->toArray()['components']['schemas']['Cv']['properties'];

        $fixed = [
            'id', 'locale', 'full_name', 'avatar_path', 'email', 'phone',
            'location', 'linkedin_url', 'github_url', 'portfolio_url', 'languages',
        ];

        foreach ($fixed as $field) {
            $this->assertTrue($properties[$field]['readOnly'] ?? false, "{$field} must be readOnly");
        }

        // And the content the whole exercise exists to rewrite must not be.
        foreach (['label', 'role', 'summary', 'stack_highlights', 'skills', 'experience'] as $field) {
            $this->assertArrayNotHasKey('readOnly', $properties[$field], "{$field} must be writable");
        }
    }
}
