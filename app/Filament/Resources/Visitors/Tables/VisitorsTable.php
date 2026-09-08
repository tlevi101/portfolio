<?php

namespace App\Filament\Resources\Visitors\Tables;

use App\Filament\Resources\Cvs\CvResource;
use App\Filament\Resources\Visitors\VisitorResource;
use App\Models\Visit;
use App\Models\Visitor;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\ViewAction;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class VisitorsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('last_seen_at', 'desc')
            ->emptyStateHeading(__('Nobody yet'))
            // Which CV last brought them in, without a query per row.
            ->modifyQueryUsing(fn (Builder $query): Builder => $query->with('latestSession.cv'))
            ->columns([
                TextColumn::make('ip_hash')
                    ->label(__('Visitor'))
                    ->formatStateUsing(fn (string $state): string => substr($state, 0, 8))
                    ->badge()
                    ->color('gray')
                    ->searchable()
                    ->tooltip(__('Same code = same visitor (by IP)')),
                TextColumn::make('latestSession.cv.label')
                    ->label(__('Via'))
                    ->placeholder(__('Found it another way'))
                    ->url(fn (Visitor $record): ?string => $record->latestSession?->cv_id !== null
                        ? CvResource::getUrl('edit', ['record' => $record->latestSession->cv_id])
                        : null)
                    ->color(fn (Visitor $record): string => $record->latestSession?->cv_id !== null ? 'primary' : 'gray'),
                TextColumn::make('sessions_count')
                    ->label(__('Visits'))
                    ->counts('sessions')
                    ->sortable(),
                TextColumn::make('country')->label(__('Country'))->badge()->placeholder('—'),
                IconColumn::make('returning')
                    ->label(__('Returning'))
                    // A plain boolean renders a red cross for "no", which reads
                    // as a failure; a first-time visitor has not failed at
                    // anything. Nothing is drawn unless they did come back.
                    ->state(fn (Visitor $record): bool => $record->isReturning())
                    ->trueIcon(Heroicon::OutlinedArrowPath)
                    ->falseIcon(Heroicon::OutlinedMinusSmall)
                    ->trueColor('success')
                    ->falseColor('gray')
                    ->tooltip(__('Came back on a different day')),
                TextColumn::make('page_views_count')
                    ->label(__('Page views'))
                    ->counts(['visits as page_views_count' => fn ($query) => $query->where('event', 'page_view')])
                    ->toggleable(),
                TextColumn::make('cv_downloads_count')
                    ->label(__('CV downloads'))
                    ->counts(['visits as cv_downloads_count' => fn ($query) => $query->where('event', 'cv_download')])
                    ->badge()
                    ->color(fn (int $state): string => $state > 0 ? 'success' : 'gray')
                    ->sortable(),
                TextColumn::make('clicks_count')
                    ->label(__('Clicks'))
                    ->counts(['visits as clicks_count' => fn ($query) => $query->where('event', 'click')])
                    ->toggleable(),
                TextColumn::make('total_seconds')
                    ->label(__('Time on site'))
                    ->sum(['visits as total_seconds' => fn ($query) => $query->where('event', 'duration')], 'value')
                    ->formatStateUsing(fn (?int $state): string => Visit::formatSeconds((int) $state))
                    ->placeholder('—')
                    ->sortable(),
                TextColumn::make('first_seen_at')->label(__('First seen'))->dateTime('Y-m-d H:i')->sortable()->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('last_seen_at')->label(__('Last seen'))->dateTime('Y-m-d H:i')->sortable(),
            ])
            // Opening a visitor used to jump to the event log with a search box
            // filled in, which lost your place and answered a different
            // question. Their own page keeps the context.
            ->recordUrl(fn (Visitor $record): string => VisitorResource::getUrl('view', ['record' => $record]))
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
