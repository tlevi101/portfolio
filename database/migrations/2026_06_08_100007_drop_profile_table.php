<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::dropIfExists('profile');
    }

    public function down(): void
    {
        // The `profile` table is superseded by `portfolios` and is not recreated.
    }
};
