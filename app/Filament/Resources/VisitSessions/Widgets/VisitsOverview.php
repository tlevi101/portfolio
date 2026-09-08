<?php

namespace App\Filament\Resources\VisitSessions\Widgets;

use App\Enums\ApplicationStatus;
use App\Models\JobApplication;
use App\Models\Visit;
use App\Models\VisitSession;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Database\Eloquent\Builder;

/**
 * The four numbers worth checking.
 *
 * The old set counted page views, link clicks and filtered bots — three
 * measures that read zero on a portfolio with this much traffic, and would keep
 * reading zero. These answer the question the site is actually for: did the
 * people I applied to come and look?
 */
class VisitsOverview extends StatsOverviewWidget
{
    protected function getStats(): array
    {
        $sevenDaysAgo = now()->subDays(7);

        $visits = $this->humanSessions()->where('started_at', '>=', $sevenDaysAgo);
        $total = (clone $visits)->count();
        $throughACv = (clone $visits)->whereNotNull('cv_id')->count();

        // Everyone lands on the hero; going further is the first sign that
        // anything on the page held them.
        $read = (clone $visits)->whereNotNull('deepest_section')->where('deepest_section', '!=', 'top')->count();
        $timeOnSite = (int) (clone $visits)->sum('duration_seconds');

        $downloads = (int) (clone $visits)->sum('cv_downloads');

        $open = JobApplication::query()
            ->whereIn('status', array_map(
                fn (ApplicationStatus $status): string => $status->value,
                array_filter(ApplicationStatus::cases(), fn (ApplicationStatus $status): bool => $status->isOpen()),
            ));

        $openCount = (clone $open)->count();
        $opened = (clone $open)
            ->whereHas('visitSessions', fn (Builder $sessions) => $sessions->where('is_bot', false))
            ->count();

        return [
            Stat::make(__('Visits (7d)'), (string) $total)
                ->description($throughACv > 0
                    ? __(':count came in through a CV', ['count' => $throughACv])
                    : __('None traced to a CV yet'))
                ->color($throughACv > 0 ? 'success' : 'gray'),

            Stat::make(__('Read past the hero (7d)'), (string) $read)
                ->description(__('of :total, :time in total', [
                    'total' => $total,
                    'time' => Visit::formatSeconds($timeOnSite),
                ])),

            Stat::make(__('CV downloads (7d)'), (string) $downloads)
                ->description(__('Strongest interest signal'))
                ->color($downloads > 0 ? 'success' : 'gray'),

            Stat::make(__('Open applications'), (string) $openCount)
                ->description(trans_choice(
                    '{0} None have opened your portfolio|{1} :count has opened your portfolio|[2,*] :count have opened your portfolio',
                    $opened,
                ))
                ->color($opened > 0 ? 'success' : 'gray'),
        ];
    }

    /**
     * @return Builder<VisitSession>
     */
    private function humanSessions(): Builder
    {
        return VisitSession::query()->where('is_bot', false);
    }
}
