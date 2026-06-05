<?php

namespace App\Models;

use App\Observers\CvDependencyObserver;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Model;

/**
 * @property array<int, string> $bullets
 */
#[ObservedBy(CvDependencyObserver::class)]
class WorkExperience extends Model
{
    protected $fillable = [
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
}
