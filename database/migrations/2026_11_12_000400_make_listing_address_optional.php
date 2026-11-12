<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * A listing starts with just "selling or renting" and the property type;
     * the address is filled in afterwards from the listing page.
     */
    public function up(): void
    {
        Schema::table('listings', function (Blueprint $table) {
            $table->string('address_line')->nullable()->change();
            $table->string('city')->nullable()->change();
            $table->string('state_province')->nullable()->change();
            $table->string('postal_code')->nullable()->change();
            $table->string('country')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('listings', function (Blueprint $table) {
            $table->string('address_line')->nullable(false)->change();
            $table->string('city')->nullable(false)->change();
            $table->string('state_province')->nullable(false)->change();
            $table->string('postal_code')->nullable(false)->change();
            $table->string('country')->nullable(false)->change();
        });
    }
};
