<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Carbon;

/**
 * One distinct visitor, identified only by the salted hash of their IP.
 *
 * @property int $id
 * @property string $ip_hash
 * @property string|null $country
 * @property bool $is_bot
 * @property Carbon|null $first_seen_at
 * @property Carbon|null $last_seen_at
 * @property-read VisitSession|null $latestSession
 * @property-read int|null $sessions_count aggregate, only when the query asks for it
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
     * This visitor's browsing sessions, newest first — what the admin shows
     * instead of an undifferentiated wall of events.
     *
     * @return HasMany<VisitSession, $this>
     */
    public function sessions(): HasMany
    {
        return $this->hasMany(VisitSession::class, 'ip_hash', 'ip_hash');
    }

    /**
     * The most recent sitting, eager-loaded so the list can say which CV — and
     * so which application — last brought this person in without a query per
     * row.
     *
     * @return HasOne<VisitSession, $this>
     */
    public function latestSession(): HasOne
    {
        return $this->hasOne(VisitSession::class, 'ip_hash', 'ip_hash')->latestOfMany('started_at');
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
