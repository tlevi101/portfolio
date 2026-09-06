<?php

namespace App\Services;

use App\Enums\SkillGroup;
use App\Models\Cv;
use App\Models\CvProject;
use App\Models\CvSkill;
use App\Models\Education;
use App\Models\WorkExperience;
use Filament\Forms\Components\RichEditor\RichContentRenderer;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;

/**
 * Turns the CV form's raw Livewire state into an unsaved `Cv` the template can
 * be rendered from.
 *
 * This deliberately lives outside the Filament page: the page only hands the
 * state to the cache, and the preview endpoint does the building and rendering
 * on its own request, so none of that work sits between a keystroke and the
 * form becoming responsive again.
 */
class CvPreviewBuilder
{
    /**
     * Reduce the form state to what can be cached and rendered — scalars and
     * arrays. Anything else (a file still being uploaded, most notably) is
     * dropped rather than serialized.
     *
     * @param  array<string, mixed>  $state
     * @return array<string, mixed>
     */
    public function sanitize(array $state): array
    {
        $sanitized = [];

        foreach ($state as $key => $value) {
            if (is_array($value)) {
                $sanitized[$key] = $this->sanitize($value);

                continue;
            }

            if ($value === null || is_scalar($value)) {
                $sanitized[$key] = $value;
            }
        }

        return $sanitized;
    }

    /**
     * An unsaved Cv carrying the form's current state. Relations are set
     * in-memory so nothing touches the database and nothing is persisted.
     *
     * @param  array<string, mixed>  $state
     */
    public function build(array $state, int|string|null $recordId = null): Cv
    {
        $cv = new Cv;

        if ($recordId !== null) {
            $cv->setAttribute('id', $recordId);
        }

        $cv->forceFill([
            'portfolio_id' => $state['portfolio_id'] ?? null,
            'locale' => $state['locale'] ?? config('app.locale'),
            'full_name' => $state['full_name'] ?? null,
            'role' => $state['role'] ?? null,
            'summary' => $this->richTextToHtml($state['summary'] ?? null),
            'stack_highlights' => array_values((array) ($state['stack_highlights'] ?? [])),
            'email' => $state['email'] ?? null,
            'phone' => $state['phone'] ?? null,
            'location' => $state['location'] ?? null,
            'linkedin_url' => $state['linkedin_url'] ?? null,
            'github_url' => $state['github_url'] ?? null,
            'portfolio_url' => $state['portfolio_url'] ?? null,
            'avatar_path' => $this->firstStoredPath($state['avatar_path'] ?? null),
            'languages' => array_values(array_filter(
                (array) ($state['languages'] ?? []),
                fn ($language): bool => is_array($language) && filled($language['name'] ?? null),
            )),
        ]);

        $cv->setRelation('workExperiences', $this->hydrateRelation(
            $state['workExperiences'] ?? [],
            WorkExperience::class,
            fn (array $item): array => [...$item, 'bullets' => $this->simpleRepeaterValues($item['bullets'] ?? [])],
        ));

        $cv->setRelation('education', $this->hydrateRelation($state['education'] ?? [], Education::class));
        $cv->setRelation('skills', $this->hydrateSkills($state));
        $cv->setRelation('projects', $this->hydrateRelation($state['projects'] ?? [], CvProject::class));

        return $cv;
    }

    /**
     * Skills are edited as one repeater per group, so the form has no single
     * `skills` key to read: the rows are collected from each group's own state
     * and stamped with the group they came from.
     *
     * @param  array<string, mixed>  $state
     * @return Collection<int, CvSkill>
     */
    protected function hydrateSkills(array $state): Collection
    {
        return collect(SkillGroup::cases())
            ->flatMap(fn (SkillGroup $group): Collection => $this
                ->hydrateRelation($state['skills'.$group->value] ?? [], CvSkill::class)
                ->each(fn (CvSkill $skill) => $skill->setAttribute('group', $group->value)))
            ->values();
    }

    /**
     * Turn a repeater's state — an array keyed by item uuid — into unsaved
     * models ordered the way the repeater shows them.
     *
     * @template TModel of Model
     *
     * @param  class-string<TModel>  $model
     * @param  (callable(array<string, mixed>): array<string, mixed>)|null  $prepare
     * @return Collection<int, TModel>
     */
    protected function hydrateRelation(mixed $items, string $model, ?callable $prepare = null): Collection
    {
        return collect(is_array($items) ? $items : [])
            ->values()
            ->filter(fn ($item): bool => is_array($item))
            ->map(function (array $item, int $index) use ($model, $prepare): Model {
                $record = new $model;
                $record->forceFill([
                    ...($prepare !== null ? $prepare($item) : $item),
                    'sort_order' => $index,
                ]);

                return $record;
            })
            ->values();
    }

    /**
     * A rich editor holds its state as a TipTap document while it is being
     * edited, and only dehydrates to HTML on save — so the preview has to run
     * the same conversion the save would.
     */
    protected function richTextToHtml(mixed $state): ?string
    {
        if (blank($state)) {
            return null;
        }

        if (! is_string($state) && ! is_array($state)) {
            return null;
        }

        return RichContentRenderer::make($state)->toHtml();
    }

    /**
     * A `simple()` repeater nests each value under an `item` key in its raw
     * state, keyed by the row's uuid.
     *
     * @return array<int, string>
     */
    protected function simpleRepeaterValues(mixed $state): array
    {
        return collect(is_array($state) ? $state : [])
            ->map(fn ($row): mixed => is_array($row) ? ($row['item'] ?? null) : $row)
            ->filter(fn ($value): bool => is_string($value) && filled($value))
            ->values()
            ->all();
    }

    /**
     * FileUpload keeps its state as a keyed array which holds either a stored
     * path or a still-uploading temporary file; only the former survives
     * `sanitize()` and can be read back off the disk for the preview.
     */
    protected function firstStoredPath(mixed $state): ?string
    {
        foreach ((array) $state as $value) {
            if (is_string($value) && filled($value)) {
                return $value;
            }
        }

        return is_string($state) && filled($state) ? $state : null;
    }
}
