<?php

namespace App\Filament\Concerns;

use App\Models\Cv;
use App\Services\CvPreviewBuilder;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Group;
use Filament\Schemas\Components\View;
use Filament\Schemas\Schema;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;

/**
 * Publishes the CV form's current — including unsaved — state so the preview
 * endpoint can render it through the same Blade template the PDF is printed
 * from, and shows the result in an iframe beside the form.
 *
 * Only the state is handed over, and only through the cache: rendering the
 * document takes long enough that doing it here would put it between every
 * keystroke and the form's response. The iframe fetches it on its own request
 * instead, in parallel with the typing.
 */
trait InteractsWithCvPreview
{
    /**
     * Fingerprint of the published state. The pane watches this property and
     * reloads the iframe whenever it changes — a plain Livewire property, so
     * the reload does not depend on the page re-rendering any HTML.
     */
    public string $cvPreviewHash = '';

    /**
     * Livewire lifecycle hook: runs at the end of every request to this
     * component, which is the one place that catches typing, repeater
     * add/remove/reorder and file uploads alike.
     */
    public function dehydrate(): void
    {
        $state = app(CvPreviewBuilder::class)->sanitize($this->data ?? []);

        $hash = md5((string) json_encode($state));

        if ($hash === $this->cvPreviewHash) {
            return;
        }

        $this->cvPreviewHash = $hash;

        Cache::put($this->getCvPreviewCacheKey(), [
            'record_id' => $this->getCvPreviewRecord()?->getKey(),
            'state' => $state,
        ], now()->addHours(2));
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
