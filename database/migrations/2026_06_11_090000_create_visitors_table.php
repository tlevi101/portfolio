<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('visitors', function (Blueprint $table) {
            $table->id();
            // One row per distinct salted IP hash; visits link on this value.
            $table->string('ip_hash', 64)->unique();
            $table->string('country', 2)->nullable();
            $table->boolean('is_bot')->default(false)->index();
            $table->timestamp('first_seen_at')->nullable();
            $table->timestamp('last_seen_at')->nullable()->index();
        });

        // Backfill from visits already recorded. MIN(is_bot): a visitor that
        // ever sent a human-looking event counts as human.
        DB::statement(<<<'SQL'
            INSERT INTO visitors (ip_hash, country, is_bot, first_seen_at, last_seen_at)
            SELECT ip_hash, MAX(country), MIN(is_bot), MIN(created_at), MAX(created_at)
            FROM visits
            WHERE ip_hash IS NOT NULL
            GROUP BY ip_hash
        SQL);
    }

    public function down(): void
    {
        Schema::dropIfExists('visitors');
    }
};
