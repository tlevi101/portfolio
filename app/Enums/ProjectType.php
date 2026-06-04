<?php

namespace App\Enums;

enum ProjectType: string
{
    case Selected = 'Selected';
    case SideProject = 'SideProject';

    public function label(): string
    {
        return match($this) {
            self::Selected => 'Selected Project',
            self::SideProject => 'Side Project / Experiment',
        };
    }
}
