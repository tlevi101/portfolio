<?php

namespace App\Filament\Concerns;

use App\Enums\SkillGroup;
use App\Models\Cv;
use App\Models\CvProject;
use App\Models\CvSkill;
use App\Models\Education;
use App\Models\WorkExperience;
use App\Services\CvGeneratorService;
use Filament\Forms\Components\RichEditor\RichContentRenderer;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Group;
use Filament\Schemas\Components\View;
use Filament\Schemas\Schema;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;

/**
 * Renders the CV form's current — including unsaved — state through the same
 * Blade template the PDF is printed from, and serves it to an iframe beside the
 * form.
 *
 * The HTML is handed over through the cache rather than through the Livewire
 * payload: the document embeds the avatar and the QR code as data URIs, so
 * pushing it down the wire on every edit would be far heavier than a page load.
 */
trait InteractsWithCvPreview
{
    /**
     * Changes whenever the rendered preview changes; the pane watches it and
     * reloads the iframe.
     */
    public string $cvPreviewHash = '';

    /**
     * Guards against re-rendering the document on round trips that did not
     * actually change the form (validation, tab switches, modal opens).
     */
    public string $cvPreviewStateHash = '';

    /**
     * Livewire lifecycle hook: runs at the end of every request to this
     * component, which is the one place that catches typing, repeater
     * add/remove/reorder and file uploads alike.
     */
    public function dehydrate(): void
    {
        $stateHash = md5((string) json_encode($this->data, JSON_PARTIAL_OUTPUT_ON_ERROR));

        if ($stateHash === $this->cvPreviewStateHash) {
            return;
        }

        $this->cvPreviewStateHash = $stateHash;

        $html = app(CvGeneratorService::class)->renderHtml($this->buildCvPreviewModel());

        Cache::put($this->getCvPreviewCacheKey(), $html, now()->addHours(2));

        $this->cvPreviewHash = md5($html);
    }

    public function getCvPreviewCacheKey(): string
    {
        return 'cv-preview:'.Auth::id().':'.$this->getId();
    }

    public function getCvPreviewUrl(): string
    {
        return route('filament.admin.cv-preview', ['token' => $this->getId()]);
    }

    /**
     * An unsaved Cv carrying the form's current state. Relations are set
     * in-memory so nothing touches the database and nothing is persisted.
     */
    public function buildCvPreviewModel(): Cv
    {
        $state = $this->data ?? [];

        $cv = new Cv;

        if ($this->getCvPreviewRecord() !== null) {
            $cv->setAttribute('id', $this->getCvPreviewRecord()->getKey());
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

        $cv->setRelation('workExperiences', $this->hydratePreviewRelation(
            $state['workExperiences'] ?? [],
            WorkExperience::class,
            fn (array $item): array => [...$item, 'bullets' => $this->simpleRepeaterValues($item['bullets'] ?? [])],
        ));

        $cv->setRelation('education', $this->hydratePreviewRelation($state['education'] ?? [], Education::class));
        $cv->setRelation('skills', $this->hydratePreviewSkills($state));
        $cv->setRelation('projects', $this->hydratePreviewRelation($state['projects'] ?? [], CvProject::class));

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
    protected function hydratePreviewSkills(array $state): Collection
    {
        return collect(SkillGroup::cases())
            ->flatMap(fn (SkillGroup $group): Collection => $this
                ->hydratePreviewRelation($state['skills'.$group->value] ?? [], CvSkill::class)
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
    protected function hydratePreviewRelation(mixed $items, string $model, ?callable $prepare = null): Collection
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
     * path or a still-uploading temporary file; only the former can be read
     * back off the disk for the preview.
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

    /**
     * The record being edited, or null on the create page.
     */
    protected function getCvPreviewRecord(): ?Cv
    {
        return $this->record instanceof Cv ? $this->record : null;
    }

    /**
     * Put the form and the preview side by side on wide screens. Below `xl` the
     * pane is hidden and the header's eye action opens the same document in a
     * modal instead, so the form keeps the full width on a laptop.
     */
    protected function cvPreviewContent(Schema $schema, mixed $formComponent): Schema
    {
        return $schema->components([
            Grid::make()
                ->columns(['default' => 1, 'xl' => 12])
                ->schema([
                    Group::make([$formComponent])->columnSpan(['default' => 1, 'xl' => 7]),
                    View::make('filament.cv.preview-pane')
                        ->columnSpan(['default' => 1, 'xl' => 5]),
                ]),
        ]);
    }
}
