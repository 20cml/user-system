<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * A listing's seller/landlord can now be left as "None yet" and picked
     * later, so owner_lead_id may be empty.
     */
    public function up(): void
    {
        Schema::table('listings', function (Blueprint $table) {
            $table->foreignId('owner_lead_id')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('listings', function (Blueprint $table) {
            $table->foreignId('owner_lead_id')->nullable(false)->change();
        });
    }
};
