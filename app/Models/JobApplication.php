<?php

namespace App\Models;

use App\Enums\ApplicationMethod;
use App\Enums\ApplicationStatus;
use App\Enums\ExperienceLevel;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * One job applied for, and the CV that was sent for it.
 *
 * Almost every field is nullable on purpose. Job ads are wildly inconsistent
 * about stating a seniority, a years-of-experience figure, or even a location,
 * and the alternative to a null is an invented value — which is exactly what
 * this whole flow exists to avoid.
 *
 * @property int $id
 * @property int $cv_id
 * @property string $company
 * @property string|null $title
 * @property ApplicationStatus $status
 * @property ApplicationMethod|null $method
 * @property string|null $method_detail
 * @property string|null $source_url
 * @property string|null $location
 * @property ExperienceLevel|null $experience_level
 * @property int|null $required_years
 * @property array<int, mixed>|null $required_skills
 * @property string|null $job_ad
 * @property string|null $notes
 * @property Carbon|null $applied_at
 * @property-read Cv $cv
 * @property-read Collection<int, VisitSession> $visitSessions
 * @property-read int|null $visit_sessions_count aggregate, only when the query asks for it
 * @property-read int|null $cv_downloads_total aggregate, only when the query asks for it
 */
class JobApplication extends Model
{
    /**
     * Short free-text columns are varchar(255). Read by the AI schema so the
     * document handed over cannot describe an application the form would then
     * refuse to save.
     */
    public const TEXT_LIMIT = 255;

    public const URL_LIMIT = 1024;

    protected $table = 'job_applications';

    protected $fillable = [
        'cv_id',
        'company',
        'title',
        'status',
        'method',
        'method_detail',
        'source_url',
        'location',
        'experience_level',
        'required_years',
        'required_skills',
        'job_ad',
        'notes',
        'applied_at',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'status' => ApplicationStatus::class,
            'method' => ApplicationMethod::class,
            'experience_level' => ExperienceLevel::class,
            'required_skills' => 'array',
            'applied_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<Cv, $this>
     */
    public function cv(): BelongsTo
    {
        return $this->belongsTo(Cv::class);
    }

    /**
     * Who came to the portfolio through the CV that was sent for this job.
     *
     * Joined on `cv_id` rather than on this application: the tracking token is
     * printed on the CV, so that is as precise as the link can be. When a CV
     * has one application — the usual case, since a variant is cut per job ad —
     * these sessions are that application's. When a master CV was sent to
     * several places, they share what arrives and there is no honest way to
     * split it.
     *
     * Not narrowed to visits after `applied_at`: the link exists nowhere but
     * inside the PDF that was sent, so there is nothing earlier to exclude.
     *
     * @return HasMany<VisitSession, $this>
     */
    public function visitSessions(): HasMany
    {
        return $this->hasMany(VisitSession::class, 'cv_id', 'cv_id');
    }

    /**
     * Whether the CV's link has been opened by a human at all — the signal this
     * whole feature exists to surface.
     */
    public function wasOpened(): bool
    {
        return $this->visitSessions()->where('is_bot', false)->exists();
    }

    /**
     * Whether anyone who arrived through this CV went on to download it.
     */
    public function cvWasDownloaded(): bool
    {
        return $this->visitSessions()->where('is_bot', false)->where('cv_downloads', '>', 0)->exists();
    }

    /**
     * How the application was sent, naming the system where the enum only says
     * that there was one.
     */
    public function methodLabel(): ?string
    {
        if ($this->method === null) {
            return $this->method_detail;
        }

        return $this->method->needsDetail() && filled($this->method_detail)
            ? $this->method_detail
            : $this->method->getLabel();
    }

    /**
     * The required skills as printable lines, e.g. "Laravel (3 y)".
     *
     * Read defensively: this is a JSON column, written to by an import, a
     * repeater and — one day — a hand-edited row, and a malformed entry should
     * cost a missing line rather than a 500 on the list page.
     *
     * @return array<int, string>
     */
    public function requiredSkillLines(): array
    {
        $lines = [];

        foreach ($this->required_skills ?? [] as $skill) {
            if (! is_array($skill) || blank($skill['name'] ?? null)) {
                continue;
            }

            $years = $skill['years'] ?? null;

            $lines[] = is_numeric($years)
                ? __(':skill (:years y)', ['skill' => $skill['name'], 'years' => (int) $years])
                : (string) $skill['name'];
        }

        return $lines;
    }
}
