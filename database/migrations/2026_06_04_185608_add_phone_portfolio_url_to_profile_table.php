<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('profile', function (Blueprint $table) {
            $table->string('phone')->nullable()->after('email');
            $table->string('portfolio_url')->nullable()->after('github_url');
        });
    }

    public function down(): void
    {
        Schema::table('profile', function (Blueprint $table) {
            $table->dropColumn(['phone', 'portfolio_url']);
        });
    }
};
