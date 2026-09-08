<?php

namespace App\Models;

use App\Enums\PortfolioSection;
use App\Enums\VisitEvent;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * A single anonymous, cookieless hit recorded for first-party analytics.
 *
 * @property int $id
 * @property int|null $visit_session_id
 * @property string|null $ip_hash
 * @property int|null $cv_id
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
 * @property Carbon|null $created_at
 */
class Visit extends Model
{
    /**
     * Only a creation timestamp is meaningful for an immutable hit log.
     */
    public const UPDATED_AT = null;

    /**
     * The links worth knowing were clicked, and what to call each of them.
     * Matched by the beacon script, which labels a click by where it points.
     * Translated at the point of use — a constant cannot hold a `__()` call.
     *
     * @var array<string, string>
     */
    private const CLICK_NAMES = [
        'contact_email' => 'Clicked the email address',
        'contact_phone' => 'Clicked the phone number',
        'linkedin' => 'Opened LinkedIn',
        'github' => 'Opened GitHub',
        'view_projects' => 'Clicked "View projects"',
    ];

    protected $table = 'visits';

    protected $fillable = [
        'visit_session_id',
        'ip_hash',
        'cv_id',
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

    /**
     * What the visitor actually did, in words.
     *
     * The stored row is a pair of codes; this is the only thing anyone reading
     * the admin wants from it.
     */
    public function describe(): ?string
    {
        return match ($this->event) {
            VisitEvent::Click->value => isset(self::CLICK_NAMES[$this->label]) ? __(self::CLICK_NAMES[$this->label]) : $this->label,
            VisitEvent::Section->value => __('Scrolled to: :section', ['section' => PortfolioSection::labelFor($this->label) ?? '?']),
            VisitEvent::Duration->value => __(':time on the page', ['time' => self::formatSeconds((int) $this->value)]),
            VisitEvent::CvDownload->value => __('Downloaded the CV'),
            default => null,
        };
    }

    public function eventLabel(): string
    {
        return VisitEvent::tryFrom($this->event)?->getLabel() ?? $this->event;
    }

    public function eventColor(): string
    {
        return VisitEvent::tryFrom($this->event)?->getColor() ?? 'gray';
    }

    public static function formatSeconds(int $seconds): string
    {
        if ($seconds < 60) {
            return $seconds.'s';
        }

        return intdiv($seconds, 60).'m '.($seconds % 60).'s';
    }

    /**
     * The browsing session this hit belongs to.
     *
     * @return BelongsTo<VisitSession, $this>
     */
    public function session(): BelongsTo
    {
        return $this->belongsTo(VisitSession::class, 'visit_session_id');
    }

    /**
     * The CV whose printed link brought this visitor in, if one did.
     *
     * @return BelongsTo<Cv, $this>
     */
    public function cv(): BelongsTo
    {
        return $this->belongsTo(Cv::class);
    }
}
