<?php

namespace App\Filament\Resources\Portfolios\Pages;

use App\Filament\Resources\Portfolios\PortfolioResource;
use App\Services\CvGeneratorService;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;
use Filament\Support\Icons\Heroicon;

class EditPortfolio extends EditRecord
{
    protected static string $resource = PortfolioResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('regenerateCv')
                ->label(__('Regenerate CV'))
                ->icon(Heroicon::OutlinedArrowPath)
                ->action(function (): void {
                    $cv = $this->record->cv ?? $this->record->cvs()->first();

                    if ($cv === null) {
                        Notification::make()
                            ->title(__('No CV is linked to this portfolio yet.'))
                            ->warning()
                            ->send();

                        return;
                    }

                    app(CvGeneratorService::class)->generateFor($cv);

                    Notification::make()
                        ->title(__('CV regenerated'))
                        ->success()
                        ->send();
                }),
            DeleteAction::make(),
        ];
    }
}
