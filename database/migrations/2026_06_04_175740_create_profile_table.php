<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('profile', function (Blueprint $table) {
            $table->id();

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
            $table->string('linkedin_url')->nullable();
            $table->string('github_url')->nullable();
            $table->string('cv_path')->nullable();

            // Footer
            $table->string('footer_text')->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('profile');
    }
};
