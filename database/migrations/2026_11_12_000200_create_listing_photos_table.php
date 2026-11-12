<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * A listing can now have several photos (up to 6). listings.photo_path
     * stays as the cover photo — the first one — so the listings index keeps
     * working; every existing photo is copied in as that listing's first.
     */
    public function up(): void
    {
        Schema::create('listing_photos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('listing_id')->constrained()->cascadeOnDelete();
            $table->string('path');
            $table->unsignedSmallInteger('position')->default(0);
            $table->timestamps();
        });

        DB::table('listings')->whereNotNull('photo_path')->orderBy('id')->each(function ($listing) {
            DB::table('listing_photos')->insert([
                'listing_id' => $listing->id,
                'path' => $listing->photo_path,
                'position' => 0,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('listing_photos');
    }
};
