<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Editable free-text columns that were left at the default varchar(255).
     * Technical columns (slug, locale, file paths, year fields) keep their size:
     * `slug` in particular is part of a unique index and must stay narrow.
     *
     * @var array<string, array<string, bool>> table => [column => nullable]
     */
    private const COLUMNS = [
        'portfolios' => [
            'label' => false,
            'full_name' => false,
            'role' => false,
            'tagline' => false,
            'hero_eyebrow' => true,
            'available_text' => true,
            'location' => false,
            'projects_heading' => true,
            'projects_subheading' => true,
            'experiments_heading' => true,
            'about_heading' => true,
            'contact_heading' => true,
            'email' => false,
            'phone' => true,
            'linkedin_url' => true,
            'github_url' => true,
            'portfolio_url' => true,
            'footer_text' => true,
        ],
        'cvs' => [
            'label' => true,
        ],
        'projects' => [
            'title' => false,
            'url' => true,
        ],
        'skills' => [
            'name' => false,
        ],
        'work_experiences' => [
            'company' => false,
            'title' => false,
            'period' => false,
            'location' => true,
        ],
        'education' => [
            'school' => false,
            'degree' => true,
            'location' => true,
        ],
    ];

    public function up(): void
    {
        $this->resize(500);
    }

    /**
     * Narrowing back to 255 truncates anything longer that was saved in the
     * meantime, so only roll this back on a database you are willing to lose
     * those characters from.
     */
    public function down(): void
    {
        $this->resize(255);
    }

    private function resize(int $length): void
    {
        foreach (self::COLUMNS as $table => $columns) {
            Schema::table($table, function (Blueprint $blueprint) use ($columns, $length): void {
                foreach ($columns as $column => $isNullable) {
                    $blueprint->string($column, $length)->nullable($isNullable)->change();
                }
            });
        }
    }
};
