<?php

namespace App\Enums;

use Filament\Support\Contracts\HasLabel;

/**
 * The seniority a job ad asks for, when it says.
 *
 * Nullable everywhere it is used: plenty of ads never name a level, and
 * guessing one from the wording would be inventing information.
 */
enum ExperienceLevel: string implements HasLabel
{
    case Junior = 'junior';
    case Medior = 'medior';
    case Senior = 'senior';
    case Lead = 'lead';

    public function getLabel(): string
    {
        return match ($this) {
            self::Junior => __('Junior'),
            self::Medior => __('Medior'),
            self::Senior => __('Senior'),
            self::Lead => __('Lead'),
        };
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
