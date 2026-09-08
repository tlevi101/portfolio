<?php

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

/**
 * Where an application has got to.
 *
 * Deliberately short. This is a tracker for remembering who was written to and
 * whether anything came back, not a pipeline to be managed — every extra stage
 * is one more thing to keep up to date by hand.
 */
enum ApplicationStatus: string implements HasColor, HasLabel
{
    case Pending = 'pending';
    case Interviewing = 'interviewing';
    case Offer = 'offer';
    case Rejected = 'rejected';
    case Withdrawn = 'withdrawn';
    case NoResponse = 'no_response';

    public function getLabel(): string
    {
        return match ($this) {
            self::Pending => __('Pending'),
            self::Interviewing => __('Interviewing'),
            self::Offer => __('Offer'),
            self::Rejected => __('Rejected'),
            self::Withdrawn => __('Withdrawn'),
            self::NoResponse => __('No response'),
        };
    }

    public function getColor(): string
    {
        return match ($this) {
            self::Pending => 'warning',
            self::Interviewing => 'info',
            self::Offer => 'success',
            self::Rejected => 'danger',
            self::Withdrawn, self::NoResponse => 'gray',
        };
    }

    /**
     * Whether this application is still worth watching for a reply.
     */
    public function isOpen(): bool
    {
        return in_array($this, [self::Pending, self::Interviewing, self::Offer], true);
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
