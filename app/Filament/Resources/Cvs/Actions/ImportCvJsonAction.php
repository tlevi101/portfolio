<?php

namespace App\Filament\Resources\Cvs\Actions;

use App\Models\Cv;
use App\Models\JobApplication;
use App\Services\CvFormState;
use App\Services\CvJsonImporter;
use App\Services\JobApplicationImporter;
use Filament\Actions\Action;
use Filament\Forms\Components\Textarea;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;
use Filament\Support\Enums\Width;
use Filament\Support\Icons\Heroicon;
use Illuminate\Validation\ValidationException;

class ImportCvJsonAction
{
    /**
     * Read a retuned tuning document back in: the CV into the form and then
     * straight to the database, and the job it was tuned for into its own
     * record.
     *
     * The import saves. An earlier version left the CV filled in but unsaved so
     * the preview could be read first, which sounds careful and in practice
     * means walking away from a page that looks finished and losing the work —
     * the round trip has already been reviewed by the time it is pasted back.
     */
    public static function make(): Action
    {
        return Action::make('importCvJson')
            ->label(__('Import from AI'))
            ->icon(Heroicon::OutlinedArrowDownOnSquare)
            ->color('gray')
            ->modalHeading(__('Import from AI'))
            ->modalDescription(__('Paste the document you got back. The CV is saved, and any job application it carries is recorded as pending.'))
            ->modalSubmitActionLabel(__('Import'))
            ->modalWidth(Width::TwoExtraLarge)
            ->schema([
                Textarea::make('json')
                    ->hiddenLabel()
                    ->placeholder('{ "cv": { "id": 1, ... }, "job_application": { ... } }')
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

                // Recorded before the CV is saved, not after: the application is
                // a fact about something already sent, and it should survive the
                // CV needing a manual fix before it will save.
                $application = $result->jobApplication !== null
                    ? app(JobApplicationImporter::class)->apply($result->jobApplication, $record)
                    : null;

                $livewire->data = $result->state;

                // Imported rows carry keys the repeaters have never seen, and a
                // repeater caches the child schema it renders each row from,
                // keyed by exactly those. Left alone it renders the new rows
                // against nothing. Filling through the schema would rebuild them
                // but also reload the relationship repeaters from the database,
                // discarding the very import that was just accepted — so the
                // state is written directly and only the cache is dropped.
                $livewire->getSchema('form')?->clearCachedDefaultChildSchemas();

                try {
                    $livewire->save(shouldRedirect: false, shouldSendSavedNotification: false);
                } catch (ValidationException $e) {
                    // The document filled in something the form will not accept —
                    // a missing company on a work experience, most likely. The
                    // state is in the form either way, so say what is wrong and
                    // leave it to be fixed and saved by hand.
                    Notification::make()
                        ->title(__('Imported, but not saved'))
                        ->body(__('Fix the highlighted fields and press Save. :errors', [
                            'errors' => implode(' ', array_merge(...array_values($e->errors()))),
                        ]))
                        ->warning()
                        ->persistent()
                        ->send();

                    return;
                }

                Notification::make()
                    ->title(__('Imported and saved'))
                    ->body(self::describe($application))
                    ->success()
                    ->send();
            });
    }

    /**
     * What happened to the job half, so a new record and an updated one are not
     * reported the same way.
     */
    protected static function describe(?JobApplication $application): string
    {
        if ($application === null) {
            return __('The CV has been updated. No job application was included.');
        }

        return $application->wasRecentlyCreated
            ? __('Application to :company recorded as pending.', ['company' => $application->company])
            : __('Application to :company updated.', ['company' => $application->company]);
    }
}
