<?php

namespace App\Filament\Resources\Cvs\Pages;

use App\Filament\Concerns\InteractsWithCvPreview;
use App\Filament\Resources\Cvs\CvResource;
use App\Models\Cv;
use Filament\Actions\Action;
use Filament\Resources\Pages\CreateRecord;
use Filament\Schemas\Schema;
use Filament\Support\Enums\Width;
use Filament\Support\Icons\Heroicon;
use Illuminate\Contracts\View\View;

/**
 * @extends CreateRecord<Cv>
 */
class CreateCv extends CreateRecord
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
        ];
    }
}
