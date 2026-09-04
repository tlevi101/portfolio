<?php

namespace App\Filament\Resources\Cvs\Actions;

use App\Filament\Resources\Cvs\CvResource;
use App\Filament\Resources\Cvs\Schemas\CvForm;
use App\Models\Cv;
use Filament\Actions\Action;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Support\Icons\Heroicon;

class DuplicateCvAction
{
    /**
     * Copy a CV together with its skills, projects, work experience and
     * education, then open the copy for editing — the reason to duplicate one is
     * to immediately retarget it at another job ad.
     */
    public static function make(): Action
    {
        return Action::make('duplicateCv')
            ->label(__('Duplicate'))
            ->icon(Heroicon::OutlinedDocumentDuplicate)
            ->color('gray')
            ->modalHeading(__('Duplicate CV'))
            ->modalDescription(__('The copy gets its own content, so editing it never touches the original.'))
            ->modalSubmitActionLabel(__('Duplicate'))
            ->schema([
                TextInput::make('label')
                    ->label(__('Label'))
                    ->required()
                    ->maxLength(CvForm::TEXT_LIMIT),
            ])
            ->fillForm(fn (Cv $record): array => [
                'label' => trim(($record->label ?? '').' '.__('(copy)')),
            ])
            ->action(function (Cv $record, array $data, Action $action): void {
                $copy = $record->duplicate($data['label']);

                Notification::make()
                    ->title(__('CV duplicated'))
                    ->success()
                    ->send();

                $action->redirect(CvResource::getUrl('edit', ['record' => $copy]));
            });
    }
}
