<?php

namespace App\Filament\Resources\Cvs\Pages;

use App\Filament\Concerns\InteractsWithCvPreview;
use App\Filament\Resources\Cvs\Actions\DuplicateCvAction;
use App\Filament\Resources\Cvs\CvResource;
use App\Models\Cv;
use App\Services\CvGeneratorService;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;
use Filament\Schemas\Schema;
use Filament\Support\Enums\Width;
use Filament\Support\Icons\Heroicon;
use Illuminate\Contracts\View\View;

/**
 * @extends EditRecord<Cv>
 */
class EditCv extends EditRecord
{
    use InteractsWithCvPreview;

    protected static string $resource = CvResource::class;

    public function content(Schema $schema): Schema
    {
        return $this->cvPreviewContent($schema, $this->getFormContentComponent());
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('previewCv')
                ->label(__('Preview'))
                ->icon(Heroicon::OutlinedEye)
                ->color('gray')
                ->modalHeading(__('Live preview'))
                ->modalWidth(Width::SixExtraLarge)
                ->modalSubmitAction(false)
                ->modalCancelActionLabel(__('Close'))
                ->modalContent(fn (): View => view('filament.cv.preview-modal', [
                    'url' => $this->getCvPreviewUrl(),
                ])),
            Action::make('regenerateCv')
                ->label(__('Regenerate CV'))
                ->icon(Heroicon::OutlinedArrowPath)
                ->action(function (): void {
                    app(CvGeneratorService::class)->generateFor($this->getRecord());

                    Notification::make()
                        ->title(__('CV regenerated'))
                        ->success()
                        ->send();
                }),
            DuplicateCvAction::make(),
            DeleteAction::make(),
        ];
    }
}
