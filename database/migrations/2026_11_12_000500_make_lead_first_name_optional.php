<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * A lead starts with just its type; the name and contact details are
     * filled in afterwards from the lead's own page.
     */
    public function up(): void
    {
        Schema::table('leads', function (Blueprint $table) {
            $table->string('first_name')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('leads', function (Blueprint $table) {
            $table->string('first_name')->nullable(false)->change();
        });
    }
};
