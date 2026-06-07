<?php

namespace App\Filament\Resources\Cvs\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class CvsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('label')->label(__('Label'))->searchable()->sortable(),
                TextColumn::make('portfolio.label')->label(__('Portfolio'))->sortable(),
                TextColumn::make('portfolio.slug')->label(__('Slug')),
                TextColumn::make('locale')->label(__('Language'))->badge(),
                IconColumn::make('cv_path')
                    ->label(__('Generated'))
                    ->boolean()
                    ->state(fn ($record): bool => filled($record->cv_path)),
                TextColumn::make('work_experiences_count')->label(__('Work experience'))->counts('workExperiences'),
                TextColumn::make('education_count')->label(__('Education'))->counts('education'),
            ])
            ->recordActions([
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
