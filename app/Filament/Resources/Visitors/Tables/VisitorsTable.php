<?php

namespace App\Filament\Resources\Visitors\Tables;

use App\Filament\Resources\Visits\VisitResource;
use App\Models\Visitor;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class VisitorsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('last_seen_at', 'desc')
            ->columns([
                TextColumn::make('ip_hash')
                    ->label(__('Visitor'))
                    ->formatStateUsing(fn (string $state): string => substr($state, 0, 8))
                    ->badge()
                    ->color('gray')
                    ->tooltip(__('Same code = same visitor (by IP)')),
                TextColumn::make('country')->label(__('Country'))->badge()->placeholder('—'),
                IconColumn::make('returning')
                    ->label(__('Returning'))
                    ->boolean()
                    ->state(fn (Visitor $record): bool => $record->isReturning())
                    ->tooltip(__('Came back on a different day')),
                TextColumn::make('page_views_count')
                    ->label(__('Page views'))
                    ->counts(['visits as page_views_count' => fn ($query) => $query->where('event', 'page_view')])
                    ->sortable(),
                TextColumn::make('cv_downloads_count')
                    ->label(__('CV downloads'))
                    ->counts(['visits as cv_downloads_count' => fn ($query) => $query->where('event', 'cv_download')])
                    ->badge()
                    ->color(fn (int $state): string => $state > 0 ? 'success' : 'gray')
                    ->sortable(),
                TextColumn::make('clicks_count')
                    ->label(__('Clicks'))
                    ->counts(['visits as clicks_count' => fn ($query) => $query->where('event', 'click')])
                    ->sortable(),
                TextColumn::make('total_seconds')
                    ->label(__('Time on site'))
                    ->sum(['visits as total_seconds' => fn ($query) => $query->where('event', 'duration')], 'value')
                    ->formatStateUsing(fn (?int $state): string => self::formatSeconds((int) $state))
                    ->placeholder('—')
                    ->sortable(),
                TextColumn::make('first_seen_at')->label(__('First seen'))->dateTime('Y-m-d H:i')->sortable(),
                TextColumn::make('last_seen_at')->label(__('Last seen'))->dateTime('Y-m-d H:i')->sortable(),
            ])
            ->recordActions([
                Action::make('activity')
                    ->label(__('Activity'))
                    ->icon(Heroicon::OutlinedListBullet)
                    ->url(fn (Visitor $record): string => VisitResource::getUrl('index', [
                        'activeTab' => 'all',
                        'tableSearch' => $record->ip_hash,
                    ])),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }

    private static function formatSeconds(int $seconds): string
    {
        if ($seconds < 60) {
            return $seconds.'s';
        }

        return intdiv($seconds, 60).'m '.($seconds % 60).'s';
    }
}
