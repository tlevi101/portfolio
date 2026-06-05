<?php

namespace App\Filament\Resources\Skills\Tables;

use App\Enums\SkillGroup;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class SkillsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('group')
                    ->label(__('Group'))
                    ->badge()
                    ->formatStateUsing(fn (SkillGroup $state): string => $state->label())
                    ->sortable(),
                TextColumn::make('name')->label(__('Skill name'))->searchable()->sortable(),
                TextColumn::make('sort_order')->label(__('Sort order'))->sortable(),
            ])
            ->filters([
                SelectFilter::make('group')
                    ->label(__('Group'))
                    ->options(collect(SkillGroup::cases())->mapWithKeys(
                        fn (SkillGroup $group): array => [$group->value => $group->label()]
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
