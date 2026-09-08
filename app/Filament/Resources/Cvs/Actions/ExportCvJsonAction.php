<?php

namespace App\Filament\Resources\Cvs\Actions;

use App\Console\Commands\GenerateCvSchema;
use App\Models\Cv;
use App\Services\CvFormState;
use App\Services\CvJsonExporter;
use Filament\Actions\Action;
use Filament\Resources\Pages\EditRecord;
use Filament\Support\Enums\Width;
use Filament\Support\Icons\Heroicon;
use Illuminate\Contracts\View\View;

class ExportCvJsonAction
{
    /**
     * Hand the CV over as JSON for an AI to retune against a job ad.
     *
     * The document is built from the form's current state rather than the saved
     * record, so it matches what the preview beside it is showing.
     */
    public static function make(): Action
    {
        return Action::make('exportCvJson')
            ->label(__('Export for AI'))
            ->icon(Heroicon::OutlinedCodeBracket)
            ->color('gray')
            ->modalHeading(__('Export for AI'))
            ->modalWidth(Width::FourExtraLarge)
            ->modalSubmitAction(false)
            ->modalCancelActionLabel(__('Close'))
            ->modalContent(fn (Cv $record, EditRecord $livewire): View => view('filament.cv.export-modal', [
                'data' => self::document($livewire, $record),
                'schema' => self::schema(),
            ]));
    }

    /**
     * The CV document, pretty-printed so it stays readable in the textarea and
     * survives being pasted into a chat.
     */
    protected static function document(EditRecord $livewire, Cv $record): string
    {
        $state = app(CvFormState::class)->sanitize($livewire->data ?? []);

        return (string) json_encode(
            app(CvJsonExporter::class)->fromFormState($state, $record->getKey()),
            JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES,
        );
    }

    /**
     * The generated contract, read straight off disk. It is committed alongside
     * the tuning skill, so this pane is only a convenience for pasting into a
     * chat that has no skill loaded.
     */
    protected static function schema(): string
    {
        $path = base_path(GenerateCvSchema::DEFAULT_PATH);

        return is_file($path)
            ? (string) file_get_contents($path)
            : __('Not generated yet — run `php artisan cv:schema`.');
    }
}
