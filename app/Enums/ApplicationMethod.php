<?php

namespace App\Enums;

use Filament\Support\Contracts\HasLabel;

/**
 * How the application was sent.
 *
 * Worth recording because the channel decides where a reply will turn up, and
 * an application submitted through someone else's ATS is the one most likely to
 * vanish without a word.
 */
enum ApplicationMethod: string implements HasLabel
{
    case LinkedIn = 'linkedin';
    case Profession = 'profession_hu';
    case Email = 'email';
    case CompanySite = 'company_site';
    case Ats = 'ats';
    case Other = 'other';

    public function getLabel(): string
    {
        return match ($this) {
            self::LinkedIn => 'LinkedIn',
            self::Profession => 'profession.hu',
            self::Email => __('Email'),
            self::CompanySite => __('Company site'),
            self::Ats => __('Other HR system'),
            self::Other => __('Other'),
        };
    }

    /**
     * Whether the channel needs naming — "some other HR system" is only useful
     * once it says which one.
     */
    public function needsDetail(): bool
    {
        return in_array($this, [self::Ats, self::Other], true);
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
