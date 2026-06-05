<?php

namespace App\Models;

use App\Enums\ProjectType;
use App\Observers\CvDependencyObserver;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Model;

/**
 * @property array<int, string> $stack
 * @property string|null $problem
 * @property string|null $role_description
 * @property string|null $outcome
 * @property string|null $url
 */
#[ObservedBy(CvDependencyObserver::class)]
class Project extends Model
{
    protected $fillable = [
        'type',
        'featured',
        'title',
        'summary',
        'problem',
        'role_description',
        'outcome',
        'stack',
        'url',
        'sort_order',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'type' => ProjectType::class,
            'featured' => 'boolean',
            'stack' => 'array',
            'sort_order' => 'integer',
        ];
    }
}
