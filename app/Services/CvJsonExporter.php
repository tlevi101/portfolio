<?php

namespace App\Services;

use App\Enums\SkillGroup;
use Illuminate\Support\Collection;

/**
 * Turns the CV form's current state into the JSON document described by
 * `CvSchema`.
 *
 * Read from the form rather than the record so what is exported is what the
 * preview is showing, unsaved edits included. Everything the form keeps for its
 * own bookkeeping — repeater uuids, `sort_order`, foreign keys — is left behind:
 * position in the array is the order, and the only id that travels is the CV's
 * own, which the import uses to check the document came from the right place.
 */
class CvJsonExporter
{
    public function __construct(private readonly CvFormState $formState) {}

    /**
     * @param  array<string, mixed>  $state
     * @return array<string, mixed>
     */
    public function fromFormState(array $state, int|string|null $id = null): array
    {
        return [
            'id' => $id === null ? null : (int) $id,
            'locale' => $state['locale'] ?? config('app.locale'),
            'full_name' => $this->text($state['full_name'] ?? null),
            'avatar_path' => $this->formState->firstStoredPath($state['avatar_path'] ?? null),
            'email' => $this->text($state['email'] ?? null),
            'phone' => $this->text($state['phone'] ?? null),
            'location' => $this->text($state['location'] ?? null),
            'linkedin_url' => $this->text($state['linkedin_url'] ?? null),
            'github_url' => $this->text($state['github_url'] ?? null),
            'portfolio_url' => $this->text($state['portfolio_url'] ?? null),
            'languages' => $this->rows($state['languages'] ?? [], ['name', 'level']),
            'label' => $this->text($state['label'] ?? null),
            'role' => $this->text($state['role'] ?? null),
            'summary' => $this->formState->richTextToHtml($state['summary'] ?? null),
            'stack_highlights' => $this->tags($state['stack_highlights'] ?? []),
            'skills' => $this->skills($state),
            'experience' => $this->experience($state),
            'projects' => $this->rows($state['projects'] ?? [], ['title'], ['stack']),
            'education' => $this->rows(
                $state['education'] ?? [],
                ['school', 'degree', 'start_year', 'graduation_year', 'location'],
            ),
        ];
    }

    /**
     * Skills are edited as one repeater per group, so they are gathered from
     * each group's own state and travel keyed by the group they print under.
     *
     * @param  array<string, mixed>  $state
     * @return array<string, array<int, string>>
     */
    protected function skills(array $state): array
    {
        $skills = [];

        foreach (SkillGroup::cases() as $group) {
            $skills[$group->value] = $this->formState
                ->repeaterRows($state['skills'.$group->value] ?? [])
                ->map(fn (array $row): mixed => $row['name'] ?? null)
                ->filter(fn ($name): bool => is_string($name) && filled($name))
                ->values()
                ->all();
        }

        return $skills;
    }

    /**
     * @param  array<string, mixed>  $state
     * @return array<int, array<string, mixed>>
     */
    protected function experience(array $state): array
    {
        return $this->formState->repeaterRows($state['workExperiences'] ?? [])
            ->map(fn (array $row): array => [
                ...$this->only($row, ['company', 'title', 'period', 'location']),
                'bullets' => $this->formState->simpleRepeaterValues($row['bullets'] ?? []),
            ])
            ->all();
    }

    /**
     * Repeater rows reduced to the fields the schema declares.
     *
     * @param  array<int, string>  $fields
     * @param  array<int, string>  $tagFields
     * @return array<int, array<string, mixed>>
     */
    protected function rows(mixed $state, array $fields, array $tagFields = []): array
    {
        return $this->formState->repeaterRows($state)
            ->map(function (array $row) use ($fields, $tagFields): array {
                $picked = $this->only($row, $fields);

                foreach ($tagFields as $field) {
                    $picked[$field] = $this->tags($row[$field] ?? []);
                }

                return $picked;
            })
            ->all();
    }

    /**
     * Every declared field, present whether or not it is filled in, so the
     * document always has the same shape to write into.
     *
     * @param  array<array-key, mixed>  $row
     * @param  array<int, string>  $fields
     * @return array<string, mixed>
     */
    protected function only(array $row, array $fields): array
    {
        $picked = [];

        foreach ($fields as $field) {
            $picked[$field] = $this->text($row[$field] ?? null);
        }

        return $picked;
    }

    /**
     * A TagsInput's values.
     *
     * @return array<int, string>
     */
    protected function tags(mixed $state): array
    {
        return Collection::wrap(is_array($state) ? $state : [])
            ->filter(fn ($tag): bool => is_string($tag) && filled($tag))
            ->values()
            ->all();
    }

    /**
     * An empty field reads better as null than as an empty string, and means
     * the same thing to whatever is filling it in.
     */
    protected function text(mixed $value): mixed
    {
        if (is_string($value)) {
            return filled($value) ? $value : null;
        }

        return $value;
    }
}
