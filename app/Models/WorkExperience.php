<?php

namespace App\Models;

use App\Observers\CvDependencyObserver;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int|null $cv_id
 * @property array<int, string> $bullets
 */
#[ObservedBy(CvDependencyObserver::class)]
class WorkExperience extends Model
{
    protected $fillable = [
        'cv_id',
        'company',
        'title',
        'period',
        'location',
        'bullets',
        'sort_order',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'bullets' => 'array',
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
