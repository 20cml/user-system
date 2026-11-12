<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['listing_id', 'body'])]
class ListingNote extends Model
{
    /** @use HasFactory<\Database\Factories\ListingNoteFactory> */
    use HasFactory;

    public function listing(): BelongsTo
    {
        return $this->belongsTo(Listing::class);
    }
}
