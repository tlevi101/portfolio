<?php

namespace App\Models;

use App\Enums\SkillGroup;
use Illuminate\Database\Eloquent\Model;

/**
 * @property SkillGroup $group
 */
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
