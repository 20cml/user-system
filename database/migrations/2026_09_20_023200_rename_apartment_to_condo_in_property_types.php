<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        DB::table('listings')->where('property_type', 'apartment')->update(['property_type' => 'condo']);
        DB::table('leads')->where('property_type', 'apartment')->update(['property_type' => 'condo']);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::table('listings')->where('property_type', 'condo')->update(['property_type' => 'apartment']);
        DB::table('leads')->where('property_type', 'condo')->update(['property_type' => 'apartment']);
    }
};
