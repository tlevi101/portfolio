<?php

namespace App\Filament\Resources\VisitSessions\Tables;

use App\Filament\Resources\Cvs\CvResource;
use App\Filament\Resources\VisitSessions\VisitSessionResource;
use App\Models\Visit;
use App\Models\VisitSession;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\ViewAction;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Grouping\Group;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class VisitSessionsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('started_at', 'desc')
            ->emptyStateHeading(__('No visits yet'))
            ->emptyStateDescription(__('Hits from your own network and from a logged-in admin are never recorded.'))
            ->modifyQueryUsing(fn (Builder $query): Builder => $query->with('cv'))
            ->groups([
                Group::make('started_at')->label(__('Day'))->date()->collapsible(),
            ])
            ->columns([
                TextColumn::make('started_at')
                    ->label(__('When'))
                    ->dateTime('Y-m-d H:i')
                    ->description(fn (VisitSession $record): string => Visit::formatSeconds($record->duration_seconds))
                    ->sortable(),
                TextColumn::make('ip_hash')
                    ->label(__('Visitor'))
                    ->formatStateUsing(fn (string $state): string => substr($state, 0, 8))
                    ->badge()
                    ->color('gray')
                    ->searchable()
                    ->tooltip(__('Same code = same visitor (by IP)')),
                // The whole reason for the tracking token: which CV, and so
                // which application, this person came in through.
                TextColumn::make('cv.label')
                    ->label(__('Via'))
                    ->placeholder(__('Found it another way'))
                    ->url(fn (VisitSession $record): ?string => $record->cv_id !== null
                        ? CvResource::getUrl('edit', ['record' => $record->cv_id])
                        : null)
                    ->color(fn (VisitSession $record): string => $record->cv_id !== null ? 'primary' : 'gray')
                    ->searchable(query: fn (Builder $query, string $search): Builder => $query
                        ->whereHas('cv', fn (Builder $cv) => $cv->where('label', 'like', "%{$search}%"))),
                TextColumn::make('deepest_section')
                    ->label(__('Got as far as'))
                    ->state(fn (VisitSession $record): ?string => $record->deepestSectionLabel())
                    ->badge()
                    ->color('warning')
                    ->placeholder('—'),
                TextColumn::make('page_views')->label(__('Views'))->sortable(),
                TextColumn::make('clicks')->label(__('Clicks'))->sortable(),
                IconColumn::make('cv_downloads')
                    ->label(__('CV taken'))
                    ->state(fn (VisitSession $record): bool => $record->cv_downloads > 0)
                    ->trueIcon(Heroicon::OutlinedArrowDownTray)
                    ->falseIcon(Heroicon::OutlinedMinusSmall)
                    ->trueColor('success')
                    ->falseColor('gray'),
                TextColumn::make('country')->label(__('Country'))->badge()->placeholder('—'),
                TextColumn::make('referer')
                    ->label(__('Came from'))
                    ->limit(30)
                    ->placeholder(__('Typed or bookmarked'))
                    ->toggleable(),
                TextColumn::make('slug')->label(__('Version'))->placeholder('—')->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('locale')->label(__('Lang'))->badge()->placeholder('—')->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('user_agent')->label(__('User agent'))->limit(40)->placeholder('—')->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                TernaryFilter::make('cv_id')
                    ->label(__('Through a CV'))
                    ->placeholder(__('Everything'))
                    ->trueLabel(__('Came in through a CV'))
                    ->falseLabel(__('Found it another way'))
                    ->queries(
                        true: fn (Builder $query): Builder => $query->whereNotNull('cv_id'),
                        false: fn (Builder $query): Builder => $query->whereNull('cv_id'),
                        blank: fn (Builder $query): Builder => $query,
                    ),
                Filter::make('engaged')
                    ->label(__('Read past the hero'))
                    ->query(fn (Builder $query): Builder => $query
                        ->whereNotNull('deepest_section')
                        ->where('deepest_section', '!=', 'top')),
                Filter::make('downloaded')
                    ->label(__('Downloaded the CV'))
                    ->query(fn (Builder $query): Builder => $query->where('cv_downloads', '>', 0)),
            ])
            ->recordUrl(fn (VisitSession $record): string => VisitSessionResource::getUrl('view', ['record' => $record]))
            ->recordActions([
                ViewAction::make()->iconButton(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
