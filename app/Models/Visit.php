<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * A single anonymous, cookieless hit recorded for first-party analytics.
 *
 * @property int $id
 * @property string|null $ip_hash
 * @property string $event
 * @property string|null $label
 * @property int|null $value
 * @property string|null $path
 * @property string|null $slug
 * @property string|null $locale
 * @property string|null $country
 * @property string|null $referer
 * @property string|null $user_agent
 * @property bool $is_bot
 * @property \Illuminate\Support\Carbon|null $created_at
 */
class Visit extends Model
{
    /**
     * Only a creation timestamp is meaningful for an immutable hit log.
     */
    public const UPDATED_AT = null;

    protected $table = 'visits';

    protected $fillable = [
        'ip_hash',
        'event',
        'label',
        'value',
        'path',
        'slug',
        'locale',
        'country',
        'referer',
        'user_agent',
        'is_bot',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'is_bot' => 'boolean',
            'value' => 'integer',
            'created_at' => 'datetime',
        ];
    }
}
