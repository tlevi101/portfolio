<?php

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

/**
 * The kinds of hit the analytics beacon records.
 *
 * These strings were previously repeated across the recorder, the beacon's
 * validation rules, the admin tables and the widgets; sessions need to count
 * them in one more place again, so they live here now.
 */
enum VisitEvent: string implements HasColor, HasLabel
{
    case PageView = 'page_view';
    case CvDownload = 'cv_download';
    case Click = 'click';
    case Section = 'section';
    case Duration = 'duration';

    public function getLabel(): string
    {
        return match ($this) {
            self::PageView => __('Page view'),
            self::CvDownload => __('CV download'),
            self::Click => __('Click'),
            self::Section => __('Section view'),
            self::Duration => __('Time on page'),
        };
    }

    public function getColor(): string
    {
        return match ($this) {
            self::PageView => 'gray',
            self::CvDownload => 'success',
            self::Click => 'info',
            self::Section => 'warning',
            self::Duration => 'primary',
        };
    }

    /**
     * The events the beacon may send. A CV download is recorded server-side
     * when the file is served, so the browser is never trusted to claim one.
     *
     * @return array<int, string>
     */
    public static function beaconValues(): array
    {
        return [
            self::PageView->value,
            self::Click->value,
            self::Section->value,
            self::Duration->value,
        ];
    }

    /**
     * @return array<string, string>
     */
    public static function options(): array
    {
        return collect(self::cases())
            ->mapWithKeys(fn (self $case): array => [$case->value => $case->getLabel()])
            ->all();
    }
}
