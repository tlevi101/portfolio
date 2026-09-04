<?php

namespace App\Filament\Resources\Cvs\Actions;

use App\Models\Cv;
use App\Services\CvGeneratorService;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Throwable;

class DownloadCvAction
{
    /**
     * Download a CV straight from the admin.
     *
     * The public route only serves the CV a portfolio points at, so a CV kept
     * for a particular job ad — or a fresh duplicate — is otherwise unreachable.
     * The PDF is built on demand when it is missing.
     */
    public static function make(): Action
    {
        return Action::make('downloadCv')
            ->label(__('Download'))
            ->icon(Heroicon::OutlinedArrowDownTray)
            ->color('gray')
            ->action(function (Cv $record): ?StreamedResponse {
                try {
                    if (blank($record->cv_path) || ! Storage::disk('public')->exists($record->cv_path)) {
                        app(CvGeneratorService::class)->generateFor($record);
                        $record->refresh();
                    }
                } catch (Throwable $e) {
                    Notification::make()
                        ->title(__('Could not build the PDF'))
                        ->body($e->getMessage())
                        ->danger()
                        ->persistent()
                        ->send();

                    return null;
                }

                return Storage::disk('public')->download(
                    (string) $record->cv_path,
                    $record->downloadFilename(),
                );
            });
    }
}
