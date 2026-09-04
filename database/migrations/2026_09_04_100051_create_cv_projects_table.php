<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * CV-owned projects. The CV only prints a name plus its stack, so this is a
     * deliberately smaller shape than the site's `projects` table.
     */
    public function up(): void
    {
        Schema::create('cv_projects', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('cv_id')->index();
            $table->string('title', 500);
            $table->json('stack')->nullable();
            $table->unsignedInteger('sort_order')->default(0)->index();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cv_projects');
    }
};
