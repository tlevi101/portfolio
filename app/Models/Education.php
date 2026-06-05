<?php

namespace App\Models;

use App\Observers\CvDependencyObserver;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Model;

#[ObservedBy(CvDependencyObserver::class)]
class Education extends Model
{
    protected $fillable = [
        'school',
        'degree',
        'graduation_year',
        'location',
        'sort_order',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'sort_order' => 'integer',
        ];
    }
}
