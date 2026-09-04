<?php

namespace App\Filament\Resources\Cvs\Tables;

use App\Filament\Resources\Cvs\Actions\DownloadCvAction;
use App\Filament\Resources\Cvs\Actions\DuplicateCvAction;
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
                TextColumn::make('full_name')->label(__('Full name'))->searchable()->sortable(),
                TextColumn::make('portfolio.label')->label(__('Portfolio'))->sortable(),
                TextColumn::make('locale')->label(__('Language'))->badge(),
                IconColumn::make('cv_path')
                    ->label(__('Generated'))
                    ->boolean()
                    ->state(fn ($record): bool => filled($record->cv_path)),
                TextColumn::make('skills_count')->label(__('Skills'))->counts('skills'),
                TextColumn::make('projects_count')->label(__('Projects'))->counts('projects'),
                TextColumn::make('work_experiences_count')->label(__('Work experience'))->counts('workExperiences'),
                TextColumn::make('education_count')->label(__('Education'))->counts('education'),
            ])
            // Icon buttons: three labelled actions overflow the row once the
            // count columns are in place.
            ->recordActions([
                EditAction::make()->iconButton(),
                DownloadCvAction::make()->iconButton(),
                DuplicateCvAction::make()->iconButton(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
