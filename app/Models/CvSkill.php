<?php

namespace App\Models;

use App\Enums\SkillGroup;
use App\Observers\CvDependencyObserver;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * A skill printed on the CV. Separate from Skill, which belongs to the site.
 *
 * @property int|null $cv_id
 * @property SkillGroup $group
 * @property string $name
 * @property int $sort_order
 */
#[ObservedBy(CvDependencyObserver::class)]
class CvSkill extends Model
{
    protected $table = 'cv_skills';

    protected $fillable = [
        'cv_id',
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
     * @return BelongsTo<Cv, $this>
     */
    public function cv(): BelongsTo
    {
        return $this->belongsTo(Cv::class);
    }
}
