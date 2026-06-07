<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Convert the legacy singleton `profile` row into one default Portfolio plus
     * its linked CV, and re-home the existing children. Fresh installs have no
     * `profile` row yet, so this is a no-op there (the seeder creates portfolios).
     */
    public function up(): void
    {
        if (! Schema::hasTable('profile')) {
            return;
        }

        $profile = DB::table('profile')->find(1);

        if ($profile === null) {
            return;
        }

        DB::transaction(function () use ($profile): void {
            $now = now();

            $portfolioId = DB::table('portfolios')->insertGetId([
                'label' => 'Full-stack',
                'slug' => 'fullstack',
                'locale' => 'hu',
                'is_default' => true,
                'full_name' => $profile->full_name,
                'role' => $profile->role,
                'tagline' => $profile->tagline,
                'hero_eyebrow' => $profile->hero_eyebrow,
                'available' => $profile->available,
                'available_text' => $profile->available_text,
                'location' => $profile->location,
                'avatar_path' => $profile->avatar_path,
                'projects_heading' => $profile->projects_heading,
                'projects_subheading' => $profile->projects_subheading,
                'experiments_heading' => $profile->experiments_heading,
                'experiments_intro' => $profile->experiments_intro,
                'about_heading' => $profile->about_heading,
                'about' => $profile->about,
                'experience_highlights' => $profile->experience_highlights,
                'contact_heading' => $profile->contact_heading,
                'contact_intro' => $profile->contact_intro,
                'email' => $profile->email,
                'phone' => $profile->phone,
                'linkedin_url' => $profile->linkedin_url,
                'github_url' => $profile->github_url,
                'portfolio_url' => $profile->portfolio_url,
                'footer_text' => $profile->footer_text,
                'created_at' => $now,
                'updated_at' => $now,
            ]);

            $cvId = DB::table('cvs')->insertGetId([
                'portfolio_id' => $portfolioId,
                'label' => 'Full-stack CV',
                'locale' => 'hu',
                'cv_path' => $profile->cv_path,
                'created_at' => $now,
                'updated_at' => $now,
            ]);

            DB::table('portfolios')->where('id', $portfolioId)->update(['cv_id' => $cvId]);

            DB::table('projects')->whereNull('portfolio_id')->update(['portfolio_id' => $portfolioId]);
            DB::table('skills')->whereNull('portfolio_id')->update(['portfolio_id' => $portfolioId]);
            DB::table('work_experiences')->whereNull('cv_id')->update(['cv_id' => $cvId]);
            DB::table('education')->whereNull('cv_id')->update(['cv_id' => $cvId]);
        });
    }

    public function down(): void
    {
        // One-way data migration; the dropped `profile` table is not restored here.
    }
};
