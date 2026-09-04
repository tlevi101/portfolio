<?php

namespace App\Models;

use App\Observers\CvDependencyObserver;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;

/**
 * A printable CV. It owns all of its content: the portfolio link only decides
 * which site serves it from the "Download CV" button.
 *
 * @property int $id
 * @property int|null $portfolio_id
 * @property string|null $label
 * @property string $locale
 * @property string|null $cv_path
 * @property string|null $full_name
 * @property string|null $role
 * @property string|null $summary
 * @property array<int, string>|null $stack_highlights
 * @property string|null $email
 * @property string|null $phone
 * @property string|null $location
 * @property string|null $linkedin_url
 * @property string|null $github_url
 * @property string|null $portfolio_url
 * @property string|null $avatar_path
 * @property array<int, array<string, string>>|null $languages
 * @property-read Portfolio|null $portfolio
 */
#[ObservedBy(CvDependencyObserver::class)]
class Cv extends Model
{
    protected $table = 'cvs';

    protected $fillable = [
        'portfolio_id',
        'label',
        'locale',
        'cv_path',
        'full_name',
        'role',
        'summary',
        'stack_highlights',
        'email',
        'phone',
        'location',
        'linkedin_url',
        'github_url',
        'portfolio_url',
        'avatar_path',
        'languages',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'stack_highlights' => 'array',
            'languages' => 'array',
        ];
    }

    protected static function booted(): void
    {
        static::deleting(function (Cv $cv): void {
            // No DB-level foreign keys, so clean up dependents explicitly.
            $cv->workExperiences()->delete();
            $cv->education()->delete();
            $cv->skills()->delete();
            $cv->projects()->delete();
        });
    }

    /**
     * @return BelongsTo<Portfolio, $this>
     */
    public function portfolio(): BelongsTo
    {
        return $this->belongsTo(Portfolio::class);
    }

    /**
     * @return HasMany<WorkExperience, $this>
     */
    public function workExperiences(): HasMany
    {
        return $this->hasMany(WorkExperience::class);
    }

    /**
     * @return HasMany<Education, $this>
     */
    public function education(): HasMany
    {
        return $this->hasMany(Education::class);
    }

    /**
     * @return HasMany<CvSkill, $this>
     */
    public function skills(): HasMany
    {
        return $this->hasMany(CvSkill::class);
    }

    /**
     * @return HasMany<CvProject, $this>
     */
    public function projects(): HasMany
    {
        return $this->hasMany(CvProject::class);
    }

    /**
     * The relations that make up a CV's own content, and therefore travel with
     * it when it is duplicated.
     *
     * @var array<int, string>
     */
    private const OWNED_RELATIONS = ['workExperiences', 'education', 'skills', 'projects'];

    /**
     * A standalone copy of this CV: its own identity, skills, projects, work
     * experience and education. Nothing is shared with the original, so the copy
     * can be reshaped for a job ad without touching what it came from.
     *
     * The stored PDF is deliberately not carried over — the copy renders its
     * own on first download.
     */
    public function duplicate(?string $label = null): self
    {
        return DB::transaction(function () use ($label): self {
            // Built from the fillable content rather than `replicate()`: a record
            // loaded through the resource table carries `withCount()` aggregates
            // (skills_count, ...) that have no column to be inserted into.
            $copy = new self(Arr::except($this->only($this->getFillable()), ['cv_path']));
            $copy->label = $label ?? trim(($this->label ?? '').' '.__('(copy)'));
            $copy->save();

            $this->copyOwnedContentTo($copy);

            return $copy;
        });
    }

    /**
     * Clone every row this CV owns onto another CV, preserving their order.
     */
    public function copyOwnedContentTo(self $target): void
    {
        foreach (self::OWNED_RELATIONS as $relation) {
            foreach ($this->{$relation}()->orderBy('sort_order')->get() as $record) {
                $clone = $record->replicate();
                $clone->cv_id = $target->getKey();
                $clone->save();
            }
        }
    }

    /**
     * A short token that changes whenever the stored PDF is rebuilt.
     *
     * The download URL is otherwise identical forever, and mobile browsers and
     * download managers happily hand back the copy they already have — even
     * with `no-store` on the response. Versioning the URL makes a rebuilt CV a
     * different resource, which is the only thing they reliably respect.
     */
    public function downloadVersion(): string
    {
        return substr(md5($this->cv_path.'|'.($this->updated_at?->getTimestamp() ?? 0)), 0, 8);
    }

    /**
     * Where the portfolio QR code points. Falls back to the linked portfolio's
     * public URL so a CV never renders a QR to nowhere.
     */
    public function portfolioUrl(): ?string
    {
        if (filled($this->portfolio_url)) {
            return $this->portfolio_url;
        }

        if ($this->portfolio === null) {
            return null;
        }

        return $this->portfolio->portfolio_url ?: route('portfolio.show', $this->portfolio->slug);
    }
}
