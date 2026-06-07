<?php

namespace App\Filament\Resources\Cvs\Pages;

use App\Filament\Resources\Cvs\CvResource;
use App\Services\CvGeneratorService;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;
use Filament\Support\Icons\Heroicon;

class EditCv extends EditRecord
{
    protected static string $resource = CvResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('regenerateCv')
                ->label(__('Regenerate CV'))
                ->icon(Heroicon::OutlinedArrowPath)
                ->action(function (): void {
                    app(CvGeneratorService::class)->generateFor($this->record);

                    Notification::make()
                        ->title(__('CV regenerated'))
                        ->success()
                        ->send();
                }),
            DeleteAction::make(),
        ];
    }
}
