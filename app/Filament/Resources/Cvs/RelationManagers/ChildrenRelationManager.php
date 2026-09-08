<?php

namespace App\Filament\Resources\Cvs\RelationManagers;

use App\Filament\Resources\Cvs\Actions\DownloadCvAction;
use App\Filament\Resources\Cvs\CvResource;
use App\Models\Cv;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;

/**
 * The variants cut from the CV being edited.
 *
 * They are hidden from the resource index, so this is where they are reached
 * from. Nothing is created or edited inline: a variant is made by the header's
 * "Create variant" action so it starts as a copy of this CV's content, and it is
 * edited on its own page, which has the live preview beside it.
 */
class ChildrenRelationManager extends RelationManager
{
    protected static string $relationship = 'children';

    public static function getTitle(Model $ownerRecord, string $pageClass): string
    {
        return __('Variants');
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('label')
            ->emptyStateHeading(__('No variants yet'))
            ->emptyStateDescription(__('Use "Create variant" above to copy this CV for a job ad.'))
            ->defaultSort('updated_at', 'desc')
            ->columns([
                TextColumn::make('label')->label(__('Label'))->searchable()->sortable(),
                TextColumn::make('role')->label(__('Role'))->searchable()->toggleable(),
                TextColumn::make('locale')->label(__('Language'))->badge(),
                IconColumn::make('cv_path')
                    ->label(__('Generated'))
                    ->boolean()
                    ->state(fn (Cv $record): bool => filled($record->cv_path)),
                TextColumn::make('updated_at')->label(__('Updated'))->dateTime()->sortable(),
            ])
            ->recordActions([
                Action::make('openCv')
                    ->label(__('Open'))
                    ->icon(Heroicon::OutlinedPencilSquare)
                    ->color('gray')
                    ->url(fn (Cv $record): string => CvResource::getUrl('edit', ['record' => $record])),
                DownloadCvAction::make(),
                DeleteAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
