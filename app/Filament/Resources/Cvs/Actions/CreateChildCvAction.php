<?php

namespace App\Filament\Resources\Cvs\Actions;

use App\Filament\Resources\Cvs\CvResource;
use App\Filament\Resources\Cvs\Schemas\CvForm;
use App\Models\Cv;
use Filament\Actions\Action;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Support\Icons\Heroicon;

class CreateChildCvAction
{
    /**
     * Copy a CV into a variant of itself, then open the variant for editing.
     *
     * This is the first step of retuning a CV for a job ad: the variant is
     * created up front so it already knows which CV it belongs to, and the
     * export/import round trip that follows only ever has to fill in content.
     */
    public static function make(): Action
    {
        return Action::make('createChildCv')
            ->label(__('Create variant'))
            ->icon(Heroicon::OutlinedDocumentPlus)
            ->color('gray')
            // Variants stay one level deep, so there is nothing to hang off one.
            ->visible(fn (Cv $record): bool => $record->parent_id === null)
            ->modalHeading(__('Create variant'))
            ->modalDescription(__('A copy of this CV, kept off the index and listed here. Retune it for a job ad without touching the original.'))
            ->modalSubmitActionLabel(__('Create'))
            ->schema([
                TextInput::make('label')
                    ->label(__('Label'))
                    ->placeholder(__('Acme — Senior Backend'))
                    ->helperText(__('Name it after the job ad you are targeting.'))
                    ->required()
                    ->maxLength(CvForm::TEXT_LIMIT),
            ])
            ->action(function (Cv $record, array $data, Action $action): void {
                $child = $record->createChild($data['label']);

                Notification::make()
                    ->title(__('Variant created'))
                    ->success()
                    ->send();

                $action->redirect(CvResource::getUrl('edit', ['record' => $child]));
            });
    }
}
