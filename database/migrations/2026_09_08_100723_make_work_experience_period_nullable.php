<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * The form has never required a period — `location` beside it is already
     * nullable — but the column was not, so saving a job without one failed at
     * the database rather than in the form. Match the column to the form.
     */
    public function up(): void
    {
        Schema::table('work_experiences', function (Blueprint $table) {
            $table->string('period', 500)->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('work_experiences', function (Blueprint $table) {
            $table->string('period', 500)->nullable(false)->change();
        });
    }
};
