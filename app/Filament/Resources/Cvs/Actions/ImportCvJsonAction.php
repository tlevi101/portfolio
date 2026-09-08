<?php

namespace App\Filament\Resources\Cvs\Actions;

use App\Models\Cv;
use App\Services\CvFormState;
use App\Services\CvJsonImporter;
use Filament\Actions\Action;
use Filament\Forms\Components\Textarea;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;
use Filament\Support\Enums\Width;
use Filament\Support\Icons\Heroicon;

class ImportCvJsonAction
{
    /**
     * Read a retuned CV document back into the form.
     *
     * Nothing is saved: the state is filled in and the preview beside it
     * re-renders, so the result can be read before it becomes the CV. Discard it
     * by leaving the page.
     */
    public static function make(): Action
    {
        return Action::make('importCvJson')
            ->label(__('Import from AI'))
            ->icon(Heroicon::OutlinedArrowDownOnSquare)
            ->color('gray')
            ->modalHeading(__('Import from AI'))
            ->modalDescription(__('Paste the document you got back. Nothing is saved until you press Save.'))
            ->modalSubmitActionLabel(__('Import'))
            ->modalWidth(Width::TwoExtraLarge)
            ->schema([
                Textarea::make('json')
                    ->hiddenLabel()
                    ->placeholder('{ "id": 1, ... }')
                    ->required()
                    ->rows(18)
                    ->autofocus(),
            ])
            ->action(function (Cv $record, array $data, EditRecord $livewire, Action $action): void {
                $result = app(CvJsonImporter::class)->merge(
                    (string) ($data['json'] ?? ''),
                    $record,
                    app(CvFormState::class)->sanitize($livewire->data ?? []),
                );

                if ($result->failed()) {
                    Notification::make()
                        ->title(__('Nothing was imported'))
                        ->body(implode(' ', $result->errors))
                        ->danger()
                        ->persistent()
                        ->send();

                    // Held open so the document is still there to correct.
                    $action->halt();

                    return;
                }

                $livewire->data = $result->state;

                // Imported rows carry keys the repeaters have never seen, and a
                // repeater caches the child schema it renders each row from,
                // keyed by exactly those. Left alone it renders the new rows
                // against nothing. Filling through the schema would rebuild them
                // but also reload the relationship repeaters from the database,
                // discarding the very import that was just accepted — so the
                // state is written directly and only the cache is dropped.
                $livewire->getSchema('form')?->clearCachedDefaultChildSchemas();

                Notification::make()
                    ->title(__('Imported'))
                    ->body(__('Check the preview, then save.'))
                    ->success()
                    ->send();
            });
    }
}
