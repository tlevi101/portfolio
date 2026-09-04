<?php

namespace App\Models;

use App\Observers\CvDependencyObserver;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * A project listed on the CV. The CV only prints the name and the stack, so
 * this carries far less than the site's Project model.
 *
 * @property int|null $cv_id
 * @property string $title
 * @property array<int, string>|null $stack
 * @property int $sort_order
 */
#[ObservedBy(CvDependencyObserver::class)]
class CvProject extends Model
{
    protected $table = 'cv_projects';

    protected $fillable = [
        'cv_id',
        'title',
        'stack',
        'sort_order',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'stack' => 'array',
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
