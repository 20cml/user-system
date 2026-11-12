<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * A seller/landlord is now a lead in their own right, picked from the
     * listing form, so one of them can own several listings.
     *
     * MySQL won't drop the unique index while the owner_lead_id foreign key
     * relies on it, so the foreign key is dropped first and put back after,
     * where it gets a plain (non-unique) index of its own.
     */
    public function up(): void
    {
        Schema::table('listings', function (Blueprint $table) {
            $table->dropForeign(['owner_lead_id']);
            $table->dropUnique(['owner_lead_id']);
            $table->foreign('owner_lead_id')->references('id')->on('leads')->restrictOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('listings', function (Blueprint $table) {
            $table->dropForeign(['owner_lead_id']);
            $table->unique('owner_lead_id');
            $table->foreign('owner_lead_id')->references('id')->on('leads')->restrictOnDelete();
        });
    }
};
