<?php

namespace App\Models;

use App\Enums\SkillGroup;
use App\Observers\CvDependencyObserver;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int|null $portfolio_id
 * @property SkillGroup $group
 */
#[ObservedBy(CvDependencyObserver::class)]
class Skill extends Model
{
    protected $fillable = [
        'portfolio_id',
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

    /**
     * @return BelongsTo<Portfolio, $this>
     */
    public function portfolio(): BelongsTo
    {
        return $this->belongsTo(Portfolio::class);
    }
}
