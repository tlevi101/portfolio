<?php

namespace App\Filament\Resources\Visits\Pages;

use App\Filament\Resources\Visits\VisitResource;
use App\Models\Visit;
use Filament\Resources\Pages\ListRecords;
use Filament\Schemas\Components\Tabs\Tab;
use Illuminate\Database\Eloquent\Builder;

class ListVisits extends ListRecords
{
    protected static string $resource = VisitResource::class;

    public function getTabs(): array
    {
        return [
            'humans' => Tab::make(__('Humans'))
                ->badge(Visit::query()->where('is_bot', false)->count())
                ->badgeColor('success')
                ->modifyQueryUsing(fn (Builder $query): Builder => $query->where('is_bot', false)),
            'cv_downloads' => Tab::make(__('CV downloads'))
                ->badge(Visit::query()->where('is_bot', false)->where('event', 'cv_download')->count())
                ->badgeColor('success')
                ->modifyQueryUsing(fn (Builder $query): Builder => $query->where('is_bot', false)->where('event', 'cv_download')),
            'activity' => Tab::make(__('Activity'))
                ->badge(Visit::query()->where('is_bot', false)->whereIn('event', ['click', 'section', 'duration'])->count())
                ->modifyQueryUsing(fn (Builder $query): Builder => $query->where('is_bot', false)->whereIn('event', ['click', 'section', 'duration'])),
            'bots' => Tab::make(__('Bots'))
                ->badge(Visit::query()->where('is_bot', true)->count())
                ->badgeColor('gray')
                ->modifyQueryUsing(fn (Builder $query): Builder => $query->where('is_bot', true)),
            'all' => Tab::make(__('All')),
        ];
    }
}
