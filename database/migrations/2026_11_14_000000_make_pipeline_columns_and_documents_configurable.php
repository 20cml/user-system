<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * The funnel's columns and each column's documents are now chosen by the
     * agent (Settings), so nothing is built in any more. Old leads pointed at
     * the built-in columns and documents, so they start over with no column
     * and no ticks. The listing's status and the "held under contract" flag
     * go away too.
     */
    public function up(): void
    {
        Schema::create('pipeline_stages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('lead_type');
            $table->string('key');
            $table->string('label');
            $table->unsignedInteger('position')->default(0);
            $table->timestamps();

            $table->unique(['user_id', 'lead_type', 'key']);
        });

        Schema::create('stage_documents', function (Blueprint $table) {
            $table->id();
            $table->foreignId('pipeline_stage_id')->constrained()->cascadeOnDelete();
            $table->string('property_type');
            $table->string('key');
            $table->string('label');
            $table->unsignedInteger('position')->default(0);
            $table->timestamps();

            $table->unique(['pipeline_stage_id', 'property_type', 'key']);
        });

        DB::table('lead_documents')->delete();

        Schema::table('leads', function (Blueprint $table) {
            $table->string('status')->nullable()->default(null)->change();
            $table->dropColumn('held_under_contract');
        });

        DB::table('leads')->update(['status' => null]);

        Schema::table('listings', function (Blueprint $table) {
            $table->dropColumn('status');
        });
    }

    public function down(): void
    {
        Schema::table('listings', function (Blueprint $table) {
            $table->string('status')->default('available');
        });

        Schema::table('leads', function (Blueprint $table) {
            $table->boolean('held_under_contract')->default(false);
        });

        DB::table('leads')->whereNull('status')->update(['status' => 'new']);

        Schema::table('leads', function (Blueprint $table) {
            $table->string('status')->default('new')->nullable(false)->change();
        });

        Schema::dropIfExists('stage_documents');
        Schema::dropIfExists('pipeline_stages');
    }
};
