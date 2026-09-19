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
        Schema::table('leads', function (Blueprint $table) {
            $table->string('type')->nullable()->after('user_id');
            $table->boolean('financing_preapproval')->default(false)->after('status');
            $table->boolean('financing_income_proof')->default(false)->after('financing_preapproval');
            $table->boolean('financing_id_document')->default(false)->after('financing_income_proof');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('leads', function (Blueprint $table) {
            $table->dropColumn(['type', 'financing_preapproval', 'financing_income_proof', 'financing_id_document']);
        });
    }
};
