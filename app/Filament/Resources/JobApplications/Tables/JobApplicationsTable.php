<?php

namespace App\Filament\Resources\JobApplications\Tables;

use App\Enums\ApplicationMethod;
use App\Enums\ApplicationStatus;
use App\Enums\ExperienceLevel;
use App\Filament\Resources\Cvs\CvResource;
use App\Models\JobApplication;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

/**
 * The scoreboard: who was written to, and whether anything came of it.
 *
 * The two columns that matter most are the ones that need no upkeep — whether
 * the CV's link was opened, and whether the CV was downloaded. Everything else
 * on this row is only as current as the last time it was edited by hand.
 */
class JobApplicationsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('applied_at', 'desc')
            ->emptyStateHeading(__('No applications yet'))
            ->emptyStateDescription(__('They arrive with an "Import from AI" on a CV, or can be recorded here by hand.'))
            ->columns([
                TextColumn::make('company')
                    ->label(__('Company'))
                    ->weight('medium')
                    ->description(fn (JobApplication $record): ?string => $record->title)
                    ->searchable()
                    ->sortable(),
                TextColumn::make('status')
                    ->label(__('Status'))
                    ->badge()
                    ->sortable(),
                IconColumn::make('opened')
                    ->label(__('Opened'))
                    ->state(fn (JobApplication $record): bool => $record->visit_sessions_count > 0)
                    ->trueIcon(Heroicon::OutlinedEye)
                    ->falseIcon(Heroicon::OutlinedMinusSmall)
                    ->trueColor('success')
                    ->falseColor('gray')
                    ->tooltip(__('Somebody followed the link printed on this CV')),
                IconColumn::make('downloaded')
                    ->label(__('CV taken'))
                    ->state(fn (JobApplication $record): bool => $record->cv_downloads_total > 0)
                    ->trueIcon(Heroicon::OutlinedArrowDownTray)
                    ->falseIcon(Heroicon::OutlinedMinusSmall)
                    ->trueColor('success')
                    ->falseColor('gray')
                    ->tooltip(__('They went on to download the CV from the site')),
                TextColumn::make('cv.label')
                    ->label(__('CV'))
                    ->url(fn (JobApplication $record): string => CvResource::getUrl('edit', ['record' => $record->cv_id]))
                    ->color('primary')
                    ->toggleable(),
                TextColumn::make('method')
                    ->label(__('Sent through'))
                    ->state(fn (JobApplication $record): ?string => $record->methodLabel())
                    ->placeholder('—')
                    ->toggleable(),
                TextColumn::make('applied_at')
                    ->label(__('Applied'))
                    ->date('Y-m-d')
                    ->placeholder('—')
                    ->sortable(),
                TextColumn::make('experience_level')
                    ->label(__('Seniority'))
                    ->badge()
                    ->color('gray')
                    ->placeholder('—')
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('required_years')
                    ->label(__('Years asked'))
                    ->placeholder('—')
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('location')
                    ->label(__('Location'))
                    ->placeholder('—')
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            // Both attribution columns come from the same relation, counted in
            // the list query rather than per row.
            ->modifyQueryUsing(fn (Builder $query): Builder => $query
                ->with('cv')
                ->withCount(['visitSessions' => fn (Builder $sessions) => $sessions->where('is_bot', false)])
                ->withSum(['visitSessions as cv_downloads_total' => fn (Builder $sessions) => $sessions->where('is_bot', false)], 'cv_downloads'))
            ->filters([
                SelectFilter::make('status')
                    ->label(__('Status'))
                    ->options(ApplicationStatus::options())
                    ->multiple(),
                SelectFilter::make('method')
                    ->label(__('Sent through'))
                    ->options(ApplicationMethod::options())
                    ->multiple(),
                SelectFilter::make('experience_level')
                    ->label(__('Seniority'))
                    ->options(ExperienceLevel::options())
                    ->multiple(),
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
