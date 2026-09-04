<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * CV-owned skills. Mirrors `skills` (which stays with the portfolio) so the
     * CV's chip list can be tailored per job ad without touching the site.
     */
    public function up(): void
    {
        Schema::create('cv_skills', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('cv_id')->index();
            $table->string('group');
            $table->string('name', 500);
            $table->unsignedInteger('sort_order')->default(0)->index();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cv_skills');
    }
};
