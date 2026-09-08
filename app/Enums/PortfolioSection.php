<?php

namespace App\Enums;

/**
 * The landing page's sections, in the order they are scrolled through.
 *
 * The beacon reports the section a visitor reaches by the element's id, so the
 * order here is what makes "got as far as Contact" mean more than "got as far
 * as Hero" — it is the closest thing the analytics have to a measure of
 * interest.
 */
enum PortfolioSection: string
{
    case Top = 'top';
    case Projects = 'projects';
    case Experiments = 'experiments';
    case About = 'about';
    case Experience = 'experience';
    case Contact = 'contact';

    public function label(): string
    {
        return match ($this) {
            self::Top => __('Hero'),
            self::Projects => __('Projects'),
            self::Experiments => __('Side projects'),
            self::About => __('About'),
            self::Experience => __('Experience'),
            self::Contact => __('Contact'),
        };
    }

    /**
     * How far down the page this section sits, counting from zero.
     */
    public function depth(): int
    {
        return array_search($this, self::cases(), true);
    }

    /**
     * The further of two sections, either of which may be unknown.
     */
    public static function deeper(?string $current, ?string $candidate): ?string
    {
        $currentSection = $current !== null ? self::tryFrom($current) : null;
        $candidateSection = $candidate !== null ? self::tryFrom($candidate) : null;

        if ($candidateSection === null) {
            return $current;
        }

        return $currentSection === null || $candidateSection->depth() > $currentSection->depth()
            ? $candidateSection->value
            : $current;
    }

    public static function labelFor(?string $value): ?string
    {
        return $value !== null ? (self::tryFrom($value)?->label() ?? $value) : null;
    }
}
