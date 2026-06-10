<?php

namespace App\Filament\Resources\Visitors;

use App\Filament\Resources\Visitors\Pages\ListVisitors;
use App\Filament\Resources\Visitors\Tables\VisitorsTable;
use App\Models\Visitor;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class VisitorResource extends Resource
{
    protected static ?string $model = Visitor::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedUsers;

    protected static ?int $navigationSort = 3;

    public static function getNavigationLabel(): string
    {
        return __('Visitors');
    }

    public static function getModelLabel(): string
    {
        return __('Visitor');
    }

    public static function getPluralModelLabel(): string
    {
        return __('Visitors');
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function table(Table $table): Table
    {
        return VisitorsTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListVisitors::route('/'),
        ];
    }
}
