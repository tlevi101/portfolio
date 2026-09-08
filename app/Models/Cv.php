<?php

namespace App\Models;

use App\Observers\CvDependencyObserver;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * A printable CV. It owns all of its content: the portfolio link only decides
 * which site serves it from the "Download CV" button.
 *
 * @property int $id
 * @property int|null $portfolio_id
 * @property int|null $parent_id
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
 * @property-read Cv|null $parent
 * @property-read Collection<int, Cv> $children
 */
#[ObservedBy(CvDependencyObserver::class)]
class Cv extends Model
{
    protected $table = 'cvs';

    protected $fillable = [
        'portfolio_id',
        'parent_id',
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

            // Deleted one at a time rather than through the relation's query so
            // each variant runs this same hook and takes its own content with it.
            $cv->children->each->delete();
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
     * The CV this one is a variant of, if any.
     *
     * @return BelongsTo<Cv, $this>
     */
    public function parent(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_id');
    }

    /**
     * Variants of this CV — copies retuned for a particular job ad. Nesting is
     * one level deep, so these never have children of their own.
     *
     * @return HasMany<Cv, $this>
     */
    public function children(): HasMany
    {
        return $this->hasMany(self::class, 'parent_id');
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
     */
    public function duplicate(?string $label = null): self
    {
        return $this->copyItself(
            $label ?? trim(($this->label ?? '').' '.__('(copy)')),
            // A duplicate sits wherever the original sits: copying a variant
            // gives another variant of the same master, not a nested one.
            $this->parent_id,
        );
    }

    /**
     * A copy of this CV attached to it as a variant — the starting point for
     * retuning it against a particular job ad, kept off the index and listed on
     * the CV it came from.
     */
    public function createChild(?string $label = null): self
    {
        return $this->copyItself(
            $label ?? trim(($this->label ?? '').' '.__('(variant)')),
            // One level only: asked for a variant of a variant, hand back another
            // variant of the master they share.
            $this->parent_id ?? $this->getKey(),
        );
    }

    /**
     * The copy both of the above are built from.
     *
     * Assembled from the fillable content rather than `replicate()`: a record
     * loaded through the resource table carries `withCount()` aggregates
     * (skills_count, ...) that have no column to be inserted into.
     *
     * The stored PDF is deliberately not carried over — the copy renders its own
     * on first download.
     */
    private function copyItself(string $label, ?int $parentId): self
    {
        return DB::transaction(function () use ($label, $parentId): self {
            $copy = new self(Arr::except($this->only($this->getFillable()), ['cv_path']));
            $copy->label = $label;
            $copy->parent_id = $parentId;
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
     * What the downloaded PDF is called on the reader's disk. Derived from the
     * CV's own name so a recruiter ends up with "Torma_Levente_CV.pdf" rather
     * than an opaque id.
     */
    public function downloadFilename(): string
    {
        $name = Str::of((string) $this->full_name)
            ->ascii()
            ->replaceMatches('/[^A-Za-z0-9]+/', '_')
            ->trim('_')
            ->toString();

        return ($name !== '' ? $name : 'cv').'_CV.pdf';
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
