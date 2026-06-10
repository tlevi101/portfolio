<?php

namespace App\Filament\Resources\Visits;

use App\Filament\Resources\Visits\Pages\ListVisits;
use App\Filament\Resources\Visits\Tables\VisitsTable;
use App\Models\Visit;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class VisitResource extends Resource
{
    protected static ?string $model = Visit::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedChartBar;

    protected static ?int $navigationSort = 4;

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
        return VisitsTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListVisits::route('/'),
        ];
    }
}
