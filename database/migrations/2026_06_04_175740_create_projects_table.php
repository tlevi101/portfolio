<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('projects', function (Blueprint $table) {
            $table->id();
            $table->string('type');
            $table->boolean('featured')->default(false);
            $table->string('title');
            $table->string('summary');
            $table->string('problem')->nullable();
            $table->string('role_description')->nullable();
            $table->string('outcome')->nullable();
            $table->json('stack');
            $table->string('url')->nullable();
            $table->unsignedInteger('sort_order')->default(0)->index();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('projects');
    }
};
