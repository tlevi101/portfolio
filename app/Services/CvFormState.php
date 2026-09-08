<?php

namespace App\Services;

use Filament\Forms\Components\RichEditor\RichContentRenderer;
use Illuminate\Support\Collection;

/**
 * Reads the CV form's raw Livewire state.
 *
 * Filament stores a form as whatever is convenient for the components editing
 * it: repeater rows keyed by uuid, a `simple()` repeater's values nested under
 * an `item` key, a rich editor holding a TipTap document until it is saved. Two
 * things need to read through all of that — the live preview and the JSON
 * export — so the knowledge of how the state is shaped lives here rather than in
 * either of them.
 */
class CvFormState
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
     * A repeater's rows, in the order the repeater shows them. Its state is
     * keyed by each row's uuid, which carries no meaning outside the form.
     *
     * @return Collection<int, array<array-key, mixed>>
     */
    public function repeaterRows(mixed $state): Collection
    {
        return collect(is_array($state) ? $state : [])
            ->values()
            ->filter(fn ($row): bool => is_array($row))
            ->values();
    }

    /**
     * A `simple()` repeater nests each value under an `item` key in its raw
     * state, keyed by the row's uuid.
     *
     * @return array<int, string>
     */
    public function simpleRepeaterValues(mixed $state): array
    {
        return collect(is_array($state) ? $state : [])
            ->map(fn ($row): mixed => is_array($row) ? ($row['item'] ?? null) : $row)
            ->filter(fn ($value): bool => is_string($value) && filled($value))
            ->values()
            ->all();
    }

    /**
     * A rich editor holds its state as a TipTap document while it is being
     * edited, and only dehydrates to HTML on save — so anything reading the
     * state has to run the same conversion the save would.
     */
    public function richTextToHtml(mixed $state): ?string
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
     * FileUpload keeps its state as a keyed array which holds either a stored
     * path or a still-uploading temporary file; only the former survives
     * `sanitize()` and can be read back off the disk.
     */
    public function firstStoredPath(mixed $state): ?string
    {
        foreach ((array) $state as $value) {
            if (is_string($value) && filled($value)) {
                return $value;
            }
        }

        return is_string($state) && filled($state) ? $state : null;
    }
}
