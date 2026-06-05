<?php

namespace App\Models;

use App\Enums\SkillGroup;
use App\Observers\CvDependencyObserver;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Model;

/**
 * @property SkillGroup $group
 */
#[ObservedBy(CvDependencyObserver::class)]
class Skill extends Model
{
    protected $fillable = [
        'group',
        'name',
        'sort_order',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'group' => SkillGroup::class,
            'sort_order' => 'integer',
        ];
    }
}
