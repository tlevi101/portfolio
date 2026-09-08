<?php

namespace App\Filament\Resources\VisitSessions;

use App\Filament\Resources\VisitSessions\Pages\ListVisitSessions;
use App\Filament\Resources\VisitSessions\Pages\ViewVisitSession;
use App\Filament\Resources\VisitSessions\RelationManagers\VisitsRelationManager;
use App\Filament\Resources\VisitSessions\Tables\VisitSessionsTable;
use App\Models\VisitSession;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

/**
 * Visits, one row per person per sitting.
 *
 * This used to list the raw event log, which meant twenty rows saying that one
 * person had read the page — a page nobody opened twice. The events are still
 * there, as the timeline behind a session.
 */
class VisitSessionResource extends Resource
{
    protected static ?string $model = VisitSession::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedChartBar;

    protected static ?int $navigationSort = 5;

    public static function getNavigationLabel(): string
    {
        return __('Visits');
    }

    public static function getModelLabel(): string
    {
        return __('Visit');
    }

    public static function getPluralModelLabel(): string
    {
        return __('Visits');
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function table(Table $table): Table
    {
        return VisitSessionsTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            VisitsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListVisitSessions::route('/'),
            'view' => ViewVisitSession::route('/{record}'),
        ];
    }
}
