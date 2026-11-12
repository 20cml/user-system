<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private const PROPERTY_TYPES = ['condo', 'house', 'land', 'commercial'];

    /**
     * A funnel's columns now belong to a lead type AND a property type, so a
     * condo and a land deal can have different columns. Each existing column
     * is copied to every property type (keeping its key), and each document
     * moves to the copy of its column for the document's own property type.
     * New unique indexes are added before the old ones are dropped because
     * MySQL needs an index for the foreign keys at all times.
     */
    public function up(): void
    {
        Schema::table('pipeline_stages', function (Blueprint $table) {
            $table->string('property_type')->default('condo')->after('lead_type');
            $table->unique(['user_id', 'lead_type', 'property_type', 'key']);
        });

        Schema::table('pipeline_stages', function (Blueprint $table) {
            $table->dropUnique(['user_id', 'lead_type', 'key']);
        });

        foreach (DB::table('pipeline_stages')->get() as $stage) {
            foreach (array_slice(self::PROPERTY_TYPES, 1) as $propertyType) {
                $copyId = DB::table('pipeline_stages')->insertGetId([
                    'user_id' => $stage->user_id,
                    'lead_type' => $stage->lead_type,
                    'property_type' => $propertyType,
                    'key' => $stage->key,
                    'label' => $stage->label,
                    'position' => $stage->position,
                    'created_at' => $stage->created_at,
                    'updated_at' => $stage->updated_at,
                ]);

                DB::table('stage_documents')
                    ->where('pipeline_stage_id', $stage->id)
                    ->where('property_type', $propertyType)
                    ->update(['pipeline_stage_id' => $copyId]);
            }
        }

        Schema::table('stage_documents', function (Blueprint $table) {
            $table->unique(['pipeline_stage_id', 'key']);
        });

        Schema::table('stage_documents', function (Blueprint $table) {
            $table->dropUnique(['pipeline_stage_id', 'property_type', 'key']);
        });

        Schema::table('stage_documents', function (Blueprint $table) {
            $table->dropColumn('property_type');
        });
    }

    public function down(): void
    {
        Schema::table('stage_documents', function (Blueprint $table) {
            $table->string('property_type')->default('condo')->after('pipeline_stage_id');
            $table->unique(['pipeline_stage_id', 'property_type', 'key']);
        });

        Schema::table('stage_documents', function (Blueprint $table) {
            $table->dropUnique(['pipeline_stage_id', 'key']);
        });

        Schema::table('pipeline_stages', function (Blueprint $table) {
            $table->unique(['user_id', 'lead_type', 'key']);
        });

        DB::table('pipeline_stages')->where('property_type', '!=', 'condo')->delete();

        Schema::table('pipeline_stages', function (Blueprint $table) {
            $table->dropUnique(['user_id', 'lead_type', 'property_type', 'key']);
        });

        Schema::table('pipeline_stages', function (Blueprint $table) {
            $table->dropColumn('property_type');
        });
    }
};
