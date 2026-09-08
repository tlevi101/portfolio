<?php

namespace App\Filament\Resources\VisitSessions\Pages;

use App\Filament\Resources\VisitSessions\VisitSessionResource;
use App\Filament\Resources\VisitSessions\Widgets\VisitsOverview;
use App\Models\VisitSession;
use Filament\Resources\Pages\ListRecords;
use Filament\Schemas\Components\Tabs\Tab;
use Illuminate\Database\Eloquent\Builder;

class ListVisitSessions extends ListRecords
{
    protected static string $resource = VisitSessionResource::class;

    protected function getHeaderWidgets(): array
    {
        return [
            VisitsOverview::class,
        ];
    }

    /**
     * People first, then the ones a job application brought in — the visits
     * worth looking at, but a tab that stays empty until the first application
     * lands is a poor thing to open the page on.
     */
    public function getTabs(): array
    {
        return [
            'humans' => Tab::make(__('People'))
                ->badge(VisitSession::query()->where('is_bot', false)->count())
                ->badgeColor('success')
                ->modifyQueryUsing(fn (Builder $query): Builder => $query->where('is_bot', false)),
            'attributed' => Tab::make(__('Through a CV'))
                ->badge(VisitSession::query()->where('is_bot', false)->whereNotNull('cv_id')->count())
                ->badgeColor('success')
                ->modifyQueryUsing(fn (Builder $query): Builder => $query->where('is_bot', false)->whereNotNull('cv_id')),
            'bots' => Tab::make(__('Bots'))
                ->badge(VisitSession::query()->where('is_bot', true)->count())
                ->badgeColor('gray')
                ->modifyQueryUsing(fn (Builder $query): Builder => $query->where('is_bot', true)),
            'all' => Tab::make(__('All')),
        ];
    }
}
