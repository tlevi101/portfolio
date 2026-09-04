<?php

use Database\Seeders\CvContentFromPortfolioSeeder;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Move existing CVs onto their own content without losing anything: every
     * value the CV template used to read off the portfolio is copied across.
     *
     * The copy itself lives in CvContentFromPortfolioSeeder so it can also be
     * re-run by hand (`artisan db:seed --class=CvContentFromPortfolioSeeder`);
     * it only fills empty fields, so running it twice is harmless.
     */
    public function up(): void
    {
        (new CvContentFromPortfolioSeeder)->run();

        // The stored PDFs were rendered from the old template and the old
        // inherited data. Dropping the path makes the next download rebuild
        // them; the files themselves are overwritten in place.
        DB::table('cvs')->update(['cv_path' => null]);
    }

    public function down(): void
    {
        // One-way data migration: the copied values stay on the CVs, which is
        // what makes rolling the schema back non-destructive to the portfolios.
    }
};
