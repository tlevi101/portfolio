<?php

namespace App\Models;

use App\Enums\PortfolioSection;
use App\Enums\VisitEvent;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * One continuous browsing session, assembled from the hits that make it up.
 *
 * The event log is the record of what happened; this is the record of who came
 * by. A single reader generates a page view, six section views, two clicks and
 * a duration — twenty rows that all mean "one person read the page" — so the
 * admin lists these and keeps the events as the detail behind one.
 *
 * The aggregates are rolled up as each hit arrives rather than counted on
 * every render: this table is read as a sortable list, and counting five
 * event types per row would be five subqueries per row.
 *
 * @property int $id
 * @property string $ip_hash
 * @property int|null $cv_id
 * @property string|null $slug
 * @property string|null $locale
 * @property string|null $country
 * @property string|null $referer
 * @property string|null $user_agent
 * @property bool $is_bot
 * @property int $page_views
 * @property int $clicks
 * @property int $cv_downloads
 * @property int $duration_seconds
 * @property string|null $deepest_section
 * @property Carbon $started_at
 * @property Carbon $ended_at
 * @property-read Cv|null $cv
 * @property-read Visitor|null $visitor
 * @property-read Collection<int, Visit> $visits
 */
class VisitSession extends Model
{
    /**
     * How long a visitor may go quiet before their next hit starts a new
     * session. Thirty minutes is the web-analytics convention and sits well
     * clear of someone reading the page slowly.
     */
    public const GAP_MINUTES = 30;

    public $timestamps = false;

    protected $table = 'visit_sessions';

    protected $fillable = [
        'ip_hash',
        'cv_id',
        'slug',
        'locale',
        'country',
        'referer',
        'user_agent',
        'is_bot',
        'page_views',
        'clicks',
        'cv_downloads',
        'duration_seconds',
        'deepest_section',
        'started_at',
        'ended_at',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'is_bot' => 'boolean',
            'page_views' => 'integer',
            'clicks' => 'integer',
            'cv_downloads' => 'integer',
            'duration_seconds' => 'integer',
            'started_at' => 'datetime',
            'ended_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::deleting(function (self $session): void {
            // No DB-level foreign keys, so clearing a session out of the admin
            // has to take its hits with it rather than leave them pointing at a
            // row that is gone.
            $session->visits()->delete();
        });
    }

    /**
     * @return HasMany<Visit, $this>
     */
    public function visits(): HasMany
    {
        return $this->hasMany(Visit::class);
    }

    /**
     * The CV whose printed link started this session, if one did.
     *
     * @return BelongsTo<Cv, $this>
     */
    public function cv(): BelongsTo
    {
        return $this->belongsTo(Cv::class);
    }

    /**
     * @return BelongsTo<Visitor, $this>
     */
    public function visitor(): BelongsTo
    {
        return $this->belongsTo(Visitor::class, 'ip_hash', 'ip_hash');
    }

    /**
     * A new session, opened by the hit that starts it.
     *
     * The timestamps are set here rather than by the caller so that `absorb()`
     * can rely on a session always having them — which it does for a stored
     * session too, since both columns are NOT NULL.
     */
    public static function open(Visit $visit, ?string $referer = null): self
    {
        $at = $visit->created_at ?? now();

        return new self([
            'ip_hash' => $visit->ip_hash,
            'is_bot' => $visit->is_bot,
            'referer' => $referer,
            'user_agent' => $visit->user_agent,
            'page_views' => 0,
            'clicks' => 0,
            'cv_downloads' => 0,
            'duration_seconds' => 0,
            'started_at' => $at,
            'ended_at' => $at,
        ]);
    }

    /**
     * Fold one hit into this session's running totals.
     *
     * Called before the session is saved, so a new session and its first hit
     * are written once between them rather than inserted and then updated.
     */
    public function absorb(Visit $visit): void
    {
        $at = $visit->created_at ?? now();

        if ($at->greaterThan($this->ended_at)) {
            $this->ended_at = $at;
        }

        // Filled in from the first hit that knows: a section or duration beacon
        // carries no slug, and the page view that does may not be first.
        $this->slug ??= $visit->slug;
        $this->locale ??= $visit->locale;
        $this->country ??= $visit->country;
        $this->cv_id ??= $visit->cv_id;

        // A session counts as human the moment any one hit in it does — a real
        // reader whose first beacon looked automated is still a real reader.
        $this->is_bot = $this->is_bot && $visit->is_bot;

        match ($visit->event) {
            VisitEvent::PageView->value => $this->page_views++,
            VisitEvent::Click->value => $this->clicks++,
            VisitEvent::CvDownload->value => $this->cv_downloads++,
            // Reported once per page, so a session spanning two pages adds up.
            VisitEvent::Duration->value => $this->duration_seconds += (int) $visit->value,
            VisitEvent::Section->value => $this->deepest_section = PortfolioSection::deeper($this->deepest_section, $visit->label),
            default => null,
        };
    }

    /**
     * How far down the page they got, named rather than slugged.
     */
    public function deepestSectionLabel(): ?string
    {
        return PortfolioSection::labelFor($this->deepest_section);
    }

    /**
     * Whether this session came in through a CV's printed link rather than by
     * finding the site some other way.
     */
    public function isAttributed(): bool
    {
        return $this->cv_id !== null;
    }
}
