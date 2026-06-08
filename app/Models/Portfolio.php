<?php

namespace App\Models;

use App\Observers\CvDependencyObserver;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

/**
 * @property int $id
 * @property string $label
 * @property string $slug
 * @property string $locale
 * @property bool $is_default
 * @property int|null $cv_id
 * @property array<int, string> $experience_highlights
 * @property array<int, array{name: string, level: string}>|null $languages
 * @property string|null $hero_eyebrow
 * @property string|null $available_text
 * @property string|null $avatar_path
 * @property string|null $projects_heading
 * @property string|null $projects_subheading
 * @property string|null $experiments_heading
 * @property string|null $experiments_intro
 * @property string|null $about_heading
 * @property string|null $contact_heading
 * @property string|null $contact_intro
 * @property string|null $phone
 * @property string|null $linkedin_url
 * @property string|null $github_url
 * @property string|null $portfolio_url
 * @property string|null $footer_text
 * @property-read Cv|null $cv
 */
#[ObservedBy(CvDependencyObserver::class)]
class Portfolio extends Model
{
    /**
     * Supported content languages, keyed by locale code.
     *
     * @var array<string, string>
     */
    public const LOCALES = [
        'hu' => 'Magyar',
        'en' => 'English',
    ];

    protected $table = 'portfolios';

    protected $fillable = [
        'label',
        'slug',
        'locale',
        'is_default',
        'cv_id',
        'full_name',
        'role',
        'tagline',
        'hero_eyebrow',
        'available',
        'available_text',
        'location',
        'avatar_path',
        'projects_heading',
        'projects_subheading',
        'experiments_heading',
        'experiments_intro',
        'about_heading',
        'about',
        'experience_highlights',
        'languages',
        'contact_heading',
        'contact_intro',
        'email',
        'phone',
        'linkedin_url',
        'github_url',
        'portfolio_url',
        'footer_text',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'available' => 'boolean',
            'is_default' => 'boolean',
            'experience_highlights' => 'array',
            'languages' => 'array',
        ];
    }

    protected static function booted(): void
    {
        static::saving(function (Portfolio $portfolio): void {
            if (blank($portfolio->slug)) {
                $portfolio->slug = $portfolio->uniqueSlug(Str::slug($portfolio->label ?: $portfolio->locale));
            }
        });

        static::saved(function (Portfolio $portfolio): void {
            // Only one default portfolio per locale may render at "/".
            if ($portfolio->is_default) {
                static::query()
                    ->where('locale', $portfolio->locale)
                    ->whereKeyNot($portfolio->getKey())
                    ->where('is_default', true)
                    ->update(['is_default' => false]);
            }
        });

        static::deleting(function (Portfolio $portfolio): void {
            // No DB-level foreign keys, so clean up dependents explicitly.
            $portfolio->projects()->delete();
            $portfolio->skills()->delete();
            $portfolio->cvs->each->delete();
        });
    }

    /**
     * @return HasMany<Project, $this>
     */
    public function projects(): HasMany
    {
        return $this->hasMany(Project::class);
    }

    /**
     * @return HasMany<Skill, $this>
     */
    public function skills(): HasMany
    {
        return $this->hasMany(Skill::class);
    }

    /**
     * @return HasMany<Cv, $this>
     */
    public function cvs(): HasMany
    {
        return $this->hasMany(Cv::class);
    }

    /**
     * The CV served by this portfolio's "Download CV" button.
     *
     * @return BelongsTo<Cv, $this>
     */
    public function cv(): BelongsTo
    {
        return $this->belongsTo(Cv::class);
    }

    /**
     * Normalise a requested language to a supported locale, falling back to the
     * application default when the value is unknown.
     */
    public static function resolveLocale(?string $lang): string
    {
        return array_key_exists((string) $lang, self::LOCALES)
            ? (string) $lang
            : (string) config('app.locale');
    }

    /**
     * The portfolio rendered at "/" for the given locale, with graceful
     * fallbacks so the homepage never 404s on a missing default or language.
     */
    public static function default(string $locale): self
    {
        return static::query()
            ->where('locale', $locale)
            ->orderByDesc('is_default')
            ->first()
            ?? static::query()->orderByDesc('is_default')->firstOrFail();
    }

    /**
     * Locales in which this portfolio's version (slug) exists, for the picker.
     *
     * @return Collection<int, string>
     */
    public function availableLocales(): Collection
    {
        return static::query()
            ->where('slug', $this->slug)
            ->orderBy('locale')
            ->pluck('locale');
    }

    /**
     * Suffix the base slug until it is unique within this locale.
     */
    protected function uniqueSlug(string $base): string
    {
        $base = $base !== '' ? $base : 'portfolio';
        $slug = $base;
        $suffix = 2;

        while (
            static::query()
                ->where('slug', $slug)
                ->where('locale', $this->locale)
                ->whereKeyNot($this->getKey())
                ->exists()
        ) {
            $slug = $base.'-'.$suffix++;
        }

        return $slug;
    }
}
