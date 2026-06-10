<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('visits', function (Blueprint $table) {
            $table->id();
            // Salted (HMAC) hash of the visitor IP — not reversible without the
            // app key, so the raw IP is never stored. Used only to roughly
            // distinguish unique visitors.
            $table->string('ip_hash', 64)->nullable()->index();
            // page_view | cv_download | click | section | duration
            $table->string('event', 32)->default('page_view')->index();
            // For click/section: which target (e.g. linkedin, view_projects, about).
            $table->string('label', 40)->nullable();
            // For duration: seconds spent on the page.
            $table->unsignedInteger('value')->nullable();
            $table->string('path', 1024)->nullable();
            $table->string('slug')->nullable();
            $table->string('locale', 5)->nullable();
            $table->string('country', 2)->nullable()->index();
            $table->string('referer', 1024)->nullable();
            $table->string('user_agent', 512)->nullable();
            $table->boolean('is_bot')->default(false)->index();
            $table->timestamp('created_at')->nullable()->index();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('visits');
    }
};
