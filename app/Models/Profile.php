<?php

namespace App\Models;

use App\Observers\CvDependencyObserver;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Model;

/**
 * @property array<int, string> $experience_highlights
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
 * @property string|null $cv_path
 * @property string|null $footer_text
 */
#[ObservedBy(CvDependencyObserver::class)]
class Profile extends Model
{
    protected $table = 'profile';

    protected $fillable = [
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
        'contact_heading',
        'contact_intro',
        'email',
        'phone',
        'linkedin_url',
        'github_url',
        'portfolio_url',
        'cv_path',
        'footer_text',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'available' => 'boolean',
            'experience_highlights' => 'array',
        ];
    }

    public static function singleton(): self
    {
        return static::firstOrCreate(['id' => 1]);
    }
}
