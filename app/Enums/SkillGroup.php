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
}
