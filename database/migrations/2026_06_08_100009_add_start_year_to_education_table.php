<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('education', function (Blueprint $table) {
            $table->string('start_year')->nullable()->after('degree');
        });
    }

    public function down(): void
    {
        Schema::table('education', function (Blueprint $table) {
            $table->dropColumn('start_year');
        });
    }
};
