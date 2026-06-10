<?php

namespace App\Filament\Resources\Visitors\Pages;

use App\Filament\Resources\Visitors\VisitorResource;
use App\Filament\Resources\Visits\Widgets\VisitsOverview;
use App\Models\Visitor;
use Filament\Resources\Pages\ListRecords;
use Filament\Schemas\Components\Tabs\Tab;
use Illuminate\Database\Eloquent\Builder;

class ListVisitors extends ListRecords
{
    protected static string $resource = VisitorResource::class;

    protected function getHeaderWidgets(): array
    {
        return [
            VisitsOverview::class,
        ];
    }

    public function getTabs(): array
    {
        return [
            'humans' => Tab::make(__('Humans'))
                ->badge(Visitor::query()->where('is_bot', false)->count())
                ->badgeColor('success')
                ->modifyQueryUsing(fn (Builder $query): Builder => $query->where('is_bot', false)),
            'bots' => Tab::make(__('Bots'))
                ->badge(Visitor::query()->where('is_bot', true)->count())
                ->badgeColor('gray')
                ->modifyQueryUsing(fn (Builder $query): Builder => $query->where('is_bot', true)),
            'all' => Tab::make(__('All')),
        ];
    }
}
