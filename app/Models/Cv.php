<?php

namespace App\Models;

use App\Observers\CvDependencyObserver;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property int $id
 * @property int|null $portfolio_id
 * @property string|null $label
 * @property string $locale
 * @property string|null $cv_path
 * @property-read Portfolio|null $portfolio
 */
#[ObservedBy(CvDependencyObserver::class)]
class Cv extends Model
{
    protected $table = 'cvs';

    protected $fillable = [
        'portfolio_id',
        'label',
        'locale',
        'cv_path',
    ];

    protected static function booted(): void
    {
        static::deleting(function (Cv $cv): void {
            // No DB-level foreign keys, so clean up dependents explicitly.
            $cv->workExperiences()->delete();
            $cv->education()->delete();
        });
    }

    /**
     * @return BelongsTo<Portfolio, $this>
     */
    public function portfolio(): BelongsTo
    {
        return $this->belongsTo(Portfolio::class);
    }

    /**
     * @return HasMany<WorkExperience, $this>
     */
    public function workExperiences(): HasMany
    {
        return $this->hasMany(WorkExperience::class);
    }

    /**
     * @return HasMany<Education, $this>
     */
    public function education(): HasMany
    {
        return $this->hasMany(Education::class);
    }
}
