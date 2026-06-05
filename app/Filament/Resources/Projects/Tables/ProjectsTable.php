<?php

namespace App\Filament\Resources\Projects\Tables;

use App\Enums\ProjectType;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class ProjectsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('title')->label(__('Title'))->searchable()->sortable(),
                TextColumn::make('type')
                    ->label(__('Type'))
                    ->badge()
                    ->formatStateUsing(fn (ProjectType $state): string => $state->label()),
                IconColumn::make('featured')->label(__('Featured'))->boolean(),
                TextColumn::make('sort_order')->label(__('Sort order'))->sortable(),
            ])
            ->filters([
                SelectFilter::make('type')
                    ->label(__('Type'))
                    ->options(collect(ProjectType::cases())->mapWithKeys(
                        fn (ProjectType $type): array => [$type->value => $type->label()]
                    )),
            ])
            ->reorderable('sort_order')
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
