<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::dropIfExists('contact_submissions');
    }

    public function down(): void
    {
        // The contact form has been removed; this table is not recreated.
    }
};
