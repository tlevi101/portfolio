<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Give the CV its own copy of the identity block it used to inherit from the
     * portfolio, so site content and CV content can be edited independently.
     * Existing rows are filled by the backfill migration that follows.
     */
    public function up(): void
    {
        Schema::table('cvs', function (Blueprint $table) {
            // Header
            $table->string('full_name', 500)->nullable()->after('locale');
            $table->string('role', 500)->nullable()->after('full_name');
            $table->text('summary')->nullable()->after('role');

            // One-line highlighted stack bar above the experience section,
            // swapped per job ad. e.g. ["Laravel", "PHP 8", "React"].
            $table->json('stack_highlights')->nullable()->after('summary');

            // Contact block
            $table->string('email', 500)->nullable()->after('stack_highlights');
            $table->string('phone', 500)->nullable()->after('email');
            $table->string('location', 500)->nullable()->after('phone');
            $table->string('linkedin_url', 500)->nullable()->after('location');
            $table->string('github_url', 500)->nullable()->after('linkedin_url');
            $table->string('portfolio_url', 500)->nullable()->after('github_url');

            $table->string('avatar_path')->nullable()->after('portfolio_url');
            $table->json('languages')->nullable()->after('avatar_path');
        });
    }

    public function down(): void
    {
        Schema::table('cvs', function (Blueprint $table) {
            $table->dropColumn([
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
            ]);
        });
    }
};
