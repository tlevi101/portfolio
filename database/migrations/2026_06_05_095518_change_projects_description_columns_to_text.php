<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Widen the project narrative columns so longer summaries and
     * problem/role/outcome descriptions are not truncated at 255 chars.
     */
    public function up(): void
    {
        Schema::table('projects', function (Blueprint $table) {
            $table->text('summary')->change();
            $table->text('problem')->nullable()->change();
            $table->text('role_description')->nullable()->change();
            $table->text('outcome')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('projects', function (Blueprint $table) {
            $table->string('summary')->change();
            $table->string('problem')->nullable()->change();
            $table->string('role_description')->nullable()->change();
            $table->string('outcome')->nullable()->change();
        });
    }
};
