<?php

namespace App\Services;

use App\Enums\SkillGroup;
use App\Models\Cv;
use App\Models\CvProject;
use App\Models\CvSkill;
use App\Models\Education;
use App\Models\WorkExperience;
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
 *
 * Reading the state is `CvFormState`'s job; this only maps what comes back onto
 * models.
 */
class CvPreviewBuilder
{
    public function __construct(private readonly CvFormState $formState) {}

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
            'summary' => $this->formState->richTextToHtml($state['summary'] ?? null),
            'stack_highlights' => array_values((array) ($state['stack_highlights'] ?? [])),
            'email' => $state['email'] ?? null,
            'phone' => $state['phone'] ?? null,
            'location' => $state['location'] ?? null,
            'linkedin_url' => $state['linkedin_url'] ?? null,
            'github_url' => $state['github_url'] ?? null,
            'portfolio_url' => $state['portfolio_url'] ?? null,
            'avatar_path' => $this->formState->firstStoredPath($state['avatar_path'] ?? null),
            'languages' => array_values(array_filter(
                (array) ($state['languages'] ?? []),
                fn ($language): bool => is_array($language) && filled($language['name'] ?? null),
            )),
        ]);

        $cv->setRelation('workExperiences', $this->hydrateRelation(
            $state['workExperiences'] ?? [],
            WorkExperience::class,
            fn (array $item): array => [...$item, 'bullets' => $this->formState->simpleRepeaterValues($item['bullets'] ?? [])],
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
     * Turn a repeater's rows into unsaved models ordered the way the repeater
     * shows them.
     *
     * @template TModel of Model
     *
     * @param  class-string<TModel>  $model
     * @param  (callable(array<array-key, mixed>): array<array-key, mixed>)|null  $prepare
     * @return Collection<int, TModel>
     */
    protected function hydrateRelation(mixed $items, string $model, ?callable $prepare = null): Collection
    {
        return $this->formState->repeaterRows($items)
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
}
