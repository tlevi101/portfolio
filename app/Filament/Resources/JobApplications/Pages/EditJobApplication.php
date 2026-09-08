<?php

namespace App\Filament\Resources\JobApplications\Pages;

use App\Filament\Resources\Cvs\CvResource;
use App\Filament\Resources\JobApplications\JobApplicationResource;
use App\Models\JobApplication;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;
use Filament\Support\Icons\Heroicon;

class EditJobApplication extends EditRecord
{
    protected static string $resource = JobApplicationResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('openCv')
                ->label(__('Open the CV'))
                ->icon(Heroicon::OutlinedDocumentText)
                ->color('gray')
                ->url(fn (JobApplication $record): string => CvResource::getUrl('edit', ['record' => $record->cv_id])),
            DeleteAction::make(),
        ];
    }
}
