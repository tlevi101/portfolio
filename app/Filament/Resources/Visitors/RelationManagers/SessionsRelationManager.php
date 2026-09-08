<?php

namespace App\Filament\Resources\Visitors\RelationManagers;

use App\Filament\Resources\Cvs\CvResource;
use App\Filament\Resources\VisitSessions\VisitSessionResource;
use App\Models\Visit;
use App\Models\VisitSession;
use Filament\Actions\Action;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

/**
 * Every time this person came by, newest first.
 *
 * The list a visitor's page is actually for: how many times they came back,
 * which CV brought them, and how far they read each time.
 */
class SessionsRelationManager extends RelationManager
{
    protected static string $relationship = 'sessions';

    public static function getTitle(Model $ownerRecord, string $pageClass): string
    {
        return __('Visits');
    }

    public function table(Table $table): Table
    {
        return $table
            ->defaultSort('started_at', 'desc')
            ->modifyQueryUsing(fn (Builder $query): Builder => $query->with('cv'))
            ->columns([
                TextColumn::make('started_at')->label(__('When'))->dateTime('Y-m-d H:i'),
                TextColumn::make('cv.label')
                    ->label(__('Via'))
                    ->placeholder(__('Found it another way'))
                    ->url(fn (VisitSession $record): ?string => $record->cv_id !== null
                        ? CvResource::getUrl('edit', ['record' => $record->cv_id])
                        : null)
                    ->color(fn (VisitSession $record): string => $record->cv_id !== null ? 'primary' : 'gray'),
                TextColumn::make('duration_seconds')
                    ->label(__('Stayed'))
                    ->formatStateUsing(fn (int $state): string => Visit::formatSeconds($state)),
                TextColumn::make('deepest_section')
                    ->label(__('Got as far as'))
                    ->state(fn (VisitSession $record): ?string => $record->deepestSectionLabel())
                    ->badge()
                    ->color('warning')
                    ->placeholder('—'),
                TextColumn::make('page_views')->label(__('Views')),
                TextColumn::make('clicks')->label(__('Clicks')),
                IconColumn::make('cv_downloads')
                    ->label(__('CV taken'))
                    ->state(fn (VisitSession $record): bool => $record->cv_downloads > 0)
                    ->trueIcon(Heroicon::OutlinedArrowDownTray)
                    ->falseIcon(Heroicon::OutlinedMinusSmall)
                    ->trueColor('success')
                    ->falseColor('gray'),
            ])
            ->recordActions([
                Action::make('openSession')
                    ->label(__('Open'))
                    ->icon(Heroicon::OutlinedListBullet)
                    ->color('gray')
                    ->url(fn (VisitSession $record): string => VisitSessionResource::getUrl('view', ['record' => $record])),
            ]);
    }
}
