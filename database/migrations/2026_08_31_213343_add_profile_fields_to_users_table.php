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
        Schema::table('users', function (Blueprint $table) {
            $table->string('phone')->nullable()->after('password');
            $table->string('address_line')->nullable()->after('phone');
            $table->string('address_complement')->nullable()->after('address_line');
            $table->string('city')->nullable()->after('address_complement');
            $table->string('region')->nullable()->after('city');
            $table->string('postal_code')->nullable()->after('region');
            $table->string('country')->nullable()->after('postal_code');
            $table->string('company_name')->nullable()->after('country');
            $table->timestamp('profile_completed_at')->nullable()->after('company_name');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn([
                'phone',
                'address_line',
                'address_complement',
                'city',
                'region',
                'postal_code',
                'country',
                'company_name',
                'profile_completed_at',
            ]);
        });
    }
};
