<?php

namespace App\Filament\Resources\Portfolios\Tables;

use App\Models\Portfolio;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class PortfoliosTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('label')->label(__('Label'))->searchable()->sortable(),
                TextColumn::make('slug')->label(__('Slug'))->searchable()->sortable(),
                TextColumn::make('locale')
                    ->label(__('Language'))
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => Portfolio::LOCALES[$state] ?? $state),
                IconColumn::make('is_default')->label(__('Default'))->boolean(),
                TextColumn::make('projects_count')->label(__('Projects'))->counts('projects'),
                TextColumn::make('skills_count')->label(__('Skills'))->counts('skills'),
            ])
            ->filters([
                SelectFilter::make('locale')
                    ->label(__('Language'))
                    ->options(Portfolio::LOCALES),
            ])
            ->defaultSort('slug')
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
