<?php

namespace App\Filament\Resources\Visits\Widgets;

use App\Models\Visit;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Database\Eloquent\Builder;

class VisitsOverview extends StatsOverviewWidget
{
    protected function getStats(): array
    {
        $sevenDaysAgo = now()->subDays(7);

        $humanViews = $this->humans()
            ->where('event', 'page_view')
            ->where('created_at', '>=', $sevenDaysAgo);

        $uniqueHumans = (clone $humanViews)->distinct('ip_hash')->count('ip_hash');

        // Visitors (by IP hash) whose page views span more than one day = returning.
        $returning = (clone $humanViews)
            ->whereNotNull('ip_hash')
            ->groupBy('ip_hash')
            ->havingRaw('COUNT(DISTINCT DATE(created_at)) > 1')
            ->pluck('ip_hash')
            ->count();

        $cvDownloads = $this->humans()
            ->where('event', 'cv_download')
            ->where('created_at', '>=', $sevenDaysAgo)
            ->count();

        $ctaClicks = $this->humans()
            ->where('event', 'click')
            ->where('created_at', '>=', $sevenDaysAgo)
            ->count();

        $botHits = Visit::query()
            ->where('is_bot', true)
            ->where('created_at', '>=', $sevenDaysAgo)
            ->count();

        return [
            Stat::make(__('Human visitors (7d)'), (string) $uniqueHumans)
                ->description(__(':count returning (came back another day)', ['count' => $returning]))
                ->color('success'),
            Stat::make(__('Human page views (7d)'), (string) $humanViews->count())
                ->description(__('Total real loads')),
            Stat::make(__('CV downloads (7d)'), (string) $cvDownloads)
                ->description(__('Strongest interest signal'))
                ->color('success'),
            Stat::make(__('Link clicks (7d)'), (string) $ctaClicks)
                ->description(__('Contact / LinkedIn / GitHub / projects')),
            Stat::make(__('Bots filtered (7d)'), (string) $botHits)
                ->description(__('Excluded from the counts above'))
                ->color('gray'),
        ];
    }

    /**
     * @return Builder<Visit>
     */
    private function humans(): Builder
    {
        return Visit::query()->where('is_bot', false);
    }
}
