<?php

use App\Models\Cv;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('cvs', function (Blueprint $table) {
            // Printed into the portfolio link the CV carries, so a visit can be
            // traced back to the copy that was sent. Random rather than derived
            // from the id: a recruiter who spots it must not be able to count
            // how many other CVs exist, or guess another one's link.
            $table->string('tracking_token', 16)->nullable()->unique()->after('parent_id');
        });

        Cv::query()->whereNull('tracking_token')->eachById(function (Cv $cv): void {
            $cv->forceFill(['tracking_token' => Cv::newTrackingToken()])->saveQuietly();
        });
    }

    public function down(): void
    {
        Schema::table('cvs', function (Blueprint $table) {
            $table->dropColumn('tracking_token');
        });
    }
};
