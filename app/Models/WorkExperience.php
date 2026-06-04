<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * @property array<int, string> $bullets
 */
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
