<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * One distinct visitor, identified only by the salted hash of their IP.
 *
 * @property int $id
 * @property string $ip_hash
 * @property string|null $country
 * @property bool $is_bot
 * @property \Illuminate\Support\Carbon|null $first_seen_at
 * @property \Illuminate\Support\Carbon|null $last_seen_at
 */
class Visitor extends Model
{
    public $timestamps = false;

    protected $table = 'visitors';

    protected $fillable = [
        'ip_hash',
        'country',
        'is_bot',
        'first_seen_at',
        'last_seen_at',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'is_bot' => 'boolean',
            'first_seen_at' => 'datetime',
            'last_seen_at' => 'datetime',
        ];
    }

    /**
     * @return HasMany<Visit, $this>
     */
    public function visits(): HasMany
    {
        return $this->hasMany(Visit::class, 'ip_hash', 'ip_hash');
    }

    /**
     * A visitor who came back on a different day than their first visit.
     */
    public function isReturning(): bool
    {
        return $this->first_seen_at !== null
            && $this->last_seen_at !== null
            && ! $this->first_seen_at->isSameDay($this->last_seen_at);
    }
}
