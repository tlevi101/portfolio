<?php

namespace App\Filament\Resources\Educations\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class EducationsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('sort_order')->label(__('Order'))->sortable(),
                TextColumn::make('school')->label(__('School'))->searchable()->sortable(),
                TextColumn::make('degree')->label(__('Degree')),
                TextColumn::make('graduation_year')->label(__('Graduation year')),
            ])
            ->defaultSort('sort_order')
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
