<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('job_applications', function (Blueprint $table) {
            $table->id();
            // The CV that was sent. No DB-level foreign keys anywhere in this
            // schema; Cv::deleting cleans these up instead.
            $table->unsignedBigInteger('cv_id')->index();

            $table->string('company');
            $table->string('title')->nullable();
            $table->string('status', 32)->default('pending')->index();

            // How it was sent, and — for an ATS or an "other" — which one.
            $table->string('method', 32)->nullable();
            $table->string('method_detail')->nullable();
            $table->string('source_url', 1024)->nullable();

            // Job ads are wildly inconsistent about stating any of this, so all
            // of it is nullable rather than guessed at.
            $table->string('location')->nullable();
            $table->string('experience_level', 16)->nullable();
            $table->unsignedTinyInteger('required_years')->nullable();
            // [{ "name": "Laravel", "years": 3 }, { "name": "React", "years": null }]
            $table->json('required_skills')->nullable();

            // The ad itself, kept verbatim: postings are taken down, and without
            // the original text there is no way to tell later what was applied
            // for or to check a tuned CV against it.
            $table->longText('job_ad')->nullable();
            $table->text('notes')->nullable();

            $table->timestamp('applied_at')->nullable()->index();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('job_applications');
    }
};
