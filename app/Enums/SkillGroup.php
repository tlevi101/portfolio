<?php

namespace App\Enums;

enum SkillGroup: string
{
    case Backend = 'Backend';
    case Frontend = 'Frontend';
    case Tools = 'Tools';
    case Other = 'Other';

    public function label(): string
    {
        return $this->value;
    }

    /**
     * Where this group sits relative to the others. The admin edits one
     * repeater per group, so each group numbers its own skills from zero and
     * `sort_order` can no longer say which group comes first.
     */
    public function sortIndex(): int
    {
        return array_search($this, self::cases(), true);
    }
}
