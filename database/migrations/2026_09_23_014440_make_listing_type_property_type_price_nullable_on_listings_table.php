<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('listings', function (Blueprint $table) {
            $table->string('listing_type')->nullable()->change();
            $table->string('property_type')->nullable()->change();
            $table->decimal('price', 12, 2)->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('listings', function (Blueprint $table) {
            $table->string('listing_type')->nullable(false)->change();
            $table->string('property_type')->nullable(false)->change();
            $table->decimal('price', 12, 2)->nullable(false)->change();
        });
    }
};
