<?php

namespace App\Filament\Resources\Cvs\Pages;

use App\Filament\Concerns\InteractsWithCvPreview;
use App\Filament\Resources\Cvs\Actions\CreateChildCvAction;
use App\Filament\Resources\Cvs\Actions\DownloadCvAction;
use App\Filament\Resources\Cvs\Actions\DuplicateCvAction;
use App\Filament\Resources\Cvs\Actions\ExportCvJsonAction;
use App\Filament\Resources\Cvs\Actions\ImportCvJsonAction;
use App\Filament\Resources\Cvs\CvResource;
use App\Models\Cv;
use App\Services\CvGeneratorService;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;
use Filament\Schemas\Schema;
use Filament\Support\Enums\Width;
use Filament\Support\Icons\Heroicon;
use Illuminate\Contracts\View\View;
use Throwable;

/**
 * @extends EditRecord<Cv>
 */
class EditCv extends EditRecord
{
    use InteractsWithCvPreview;

    protected static string $resource = CvResource::class;

    public function content(Schema $schema): Schema
    {
        return $this->cvPreviewContent($schema, $this->getFormContentComponent(), [
            $this->getRelationManagersContentComponent(),
        ]);
    }

    /**
     * The header carries the retuning loop — export, retune elsewhere, import —
     * plus the two things done to a finished CV: look at it and download it.
     *
     * Everything else lives behind the dropdown. Eight labelled buttons do not
     * fit the header at any useful width, and the ones that matter here are the
     * ones used on every pass.
     */
    protected function getHeaderActions(): array
    {
        return [
            $this->previewAction(),
            DownloadCvAction::make()->iconButton(),
            ExportCvJsonAction::make(),
            ImportCvJsonAction::make(),
            ActionGroup::make([
                CreateChildCvAction::make(),
                DuplicateCvAction::make(),
                $this->regenerateAction(),
                $this->deleteAction(),
            ]),
        ];
    }

    protected function previewAction(): Action
    {
        return Action::make('previewCv')
            ->label(__('Preview'))
            ->icon(Heroicon::OutlinedEye)
            ->color('gray')
            ->modalHeading(__('Live preview'))
            ->modalWidth(Width::SixExtraLarge)
            ->modalSubmitAction(false)
            ->modalCancelActionLabel(__('Close'))
            ->modalContent(fn (): View => view('filament.cv.preview-modal', [
                'url' => $this->getCvPreviewUrl(),
            ]))
                // The pane beside the form is hidden below `xl`, which is
                // exactly where the header is tightest — so this stays out of
                // the dropdown, as an icon.
            ->iconButton();
    }

    protected function regenerateAction(): Action
    {
        return Action::make('regenerateCv')
            ->label(__('Regenerate CV'))
            ->icon(Heroicon::OutlinedArrowPath)
            ->action(function (): void {
                try {
                    app(CvGeneratorService::class)->generateFor($this->getRecord());
                } catch (Throwable $e) {
                    // Rendering needs a working headless Chrome; surface why
                    // it is unhappy instead of throwing a 500 at the admin.
                    Notification::make()
                        ->title(__('Could not build the PDF'))
                        ->body($e->getMessage())
                        ->danger()
                        ->persistent()
                        ->send();

                    return;
                }

                Notification::make()
                    ->title(__('CV regenerated'))
                    ->success()
                    ->send();
            });
    }

    /**
     * Deleting a CV takes its variants with it; say how many so that is not a
     * surprise discovered afterwards.
     */
    protected function deleteAction(): DeleteAction
    {
        return DeleteAction::make()
            ->modalDescription(function (Cv $record): string {
                $variants = $record->children()->count();

                return $variants === 0
                    ? __('This cannot be undone.')
                    : trans_choice(
                        '{1} This CV and its :count variant will be deleted. This cannot be undone.|[2,*] This CV and its :count variants will be deleted. This cannot be undone.',
                        $variants,
                    );
            });
    }
}
