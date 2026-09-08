<?php

namespace App\Filament\Resources\VisitSessions\RelationManagers;

use App\Models\Visit;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;

/**
 * The raw hits behind one visit, in the order they happened.
 *
 * This is where the old Visits page went. As a list of its own it was twenty
 * rows of scroll telemetry with nothing to anchor them to; read as one
 * person's minute on the page, the same rows are a story.
 */
class VisitsRelationManager extends RelationManager
{
    protected static string $relationship = 'visits';

    public static function getTitle(Model $ownerRecord, string $pageClass): string
    {
        return __('What they did');
    }

    public function table(Table $table): Table
    {
        return $table
            ->defaultSort('created_at')
            ->paginated([25, 50, 100])
            ->columns([
                TextColumn::make('created_at')->label(__('At'))->dateTime('H:i:s'),
                TextColumn::make('event')
                    ->label(__('Event'))
                    ->badge()
                    ->formatStateUsing(fn (Visit $record): string => $record->eventLabel())
                    ->color(fn (Visit $record): string => $record->eventColor()),
                TextColumn::make('label')
                    ->label(__('Detail'))
                    ->state(fn (Visit $record): ?string => $record->describe())
                    ->placeholder('—'),
                TextColumn::make('path')->label(__('Page'))->limit(50)->placeholder('—')->toggleable(),
            ]);
    }
}
