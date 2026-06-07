<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('portfolios', function (Blueprint $table) {
            $table->id();

            // Version & language identity
            $table->string('label');
            $table->string('slug');
            $table->string('locale', 5)->default('hu');
            $table->boolean('is_default')->default(false)->index();
            $table->unsignedBigInteger('cv_id')->nullable()->index();

            // Hero
            $table->string('full_name');
            $table->string('role');
            $table->string('tagline');
            $table->string('hero_eyebrow')->nullable();
            $table->boolean('available')->default(true);
            $table->string('available_text')->nullable();
            $table->string('location');
            $table->string('avatar_path')->nullable();

            // Projects section
            $table->string('projects_heading')->nullable();
            $table->string('projects_subheading')->nullable();

            // Experiments section
            $table->string('experiments_heading')->nullable();
            $table->text('experiments_intro')->nullable();

            // About section
            $table->string('about_heading')->nullable();
            $table->text('about');
            $table->json('experience_highlights')->nullable();

            // Contact section
            $table->string('contact_heading')->nullable();
            $table->text('contact_intro')->nullable();
            $table->string('email');
            $table->string('phone')->nullable();
            $table->string('linkedin_url')->nullable();
            $table->string('github_url')->nullable();
            $table->string('portfolio_url')->nullable();

            // Footer
            $table->string('footer_text')->nullable();

            $table->timestamps();

            $table->unique(['slug', 'locale']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('portfolios');
    }
};
