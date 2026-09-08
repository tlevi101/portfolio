<?php

namespace App\Filament\Resources\JobApplications\Pages;

use App\Enums\ApplicationStatus;
use App\Filament\Resources\JobApplications\JobApplicationResource;
use App\Models\JobApplication;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use Filament\Schemas\Components\Tabs\Tab;
use Illuminate\Database\Eloquent\Builder;

class ListJobApplications extends ListRecords
{
    protected static string $resource = JobApplicationResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()->label(__('Record an application')),
        ];
    }

    /**
     * Open first: the whole point of the list is remembering who has not
     * replied yet. Everything that is finished with is one tab away.
     */
    public function getTabs(): array
    {
        $open = array_map(
            fn (ApplicationStatus $status): string => $status->value,
            array_filter(ApplicationStatus::cases(), fn (ApplicationStatus $status): bool => $status->isOpen()),
        );

        return [
            'open' => Tab::make(__('Open'))
                ->badge(JobApplication::query()->whereIn('status', $open)->count())
                ->badgeColor('warning')
                ->modifyQueryUsing(fn (Builder $query): Builder => $query->whereIn('status', $open)),
            'closed' => Tab::make(__('Closed'))
                ->badge(JobApplication::query()->whereNotIn('status', $open)->count())
                ->badgeColor('gray')
                ->modifyQueryUsing(fn (Builder $query): Builder => $query->whereNotIn('status', $open)),
            'all' => Tab::make(__('All')),
        ];
    }
}
