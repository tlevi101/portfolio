<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Let a CV hang off another as a variant, so a master CV can be tuned per job
     * ad without every attempt cluttering the index.
     *
     * Deliberately no foreign key: nothing else in this schema has one, and the
     * cascade lives on the model alongside the rest of the CV's cleanup.
     */
    public function up(): void
    {
        Schema::table('cvs', function (Blueprint $table) {
            $table->unsignedBigInteger('parent_id')->nullable()->after('id')->index();
        });
    }

    public function down(): void
    {
        Schema::table('cvs', function (Blueprint $table) {
            $table->dropIndex(['parent_id']);
            $table->dropColumn('parent_id');
        });
    }
};
