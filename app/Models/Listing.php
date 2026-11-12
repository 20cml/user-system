<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Storage;

#[Fillable([
    'user_id', 'owner_lead_id', 'buyer_lead_id', 'source', 'listing_type', 'property_type',
    'address_line', 'city', 'state_province', 'postal_code', 'country',
    'price', 'currency', 'area_sqm', 'description', 'photo_path',
])]
class Listing extends Model
{
    /** @use HasFactory<\Database\Factories\ListingFactory> */
    use HasFactory;

    protected function casts(): array
    {
        return [
            'price' => 'decimal:2',
            'area_sqm' => 'decimal:2',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * The lead selling or renting out this listing — at most one (seller for
     * a 'sale' listing, landlord for a 'rent' one); empty until picked.
     */
    public function owner(): BelongsTo
    {
        return $this->belongsTo(Lead::class, 'owner_lead_id');
    }

    /**
     * The lead buying (or renting) this listing — chosen by the agent from
     * the listing page, and always one of its interested leads. Optional.
     */
    public function buyer(): BelongsTo
    {
        return $this->belongsTo(Lead::class, 'buyer_lead_id');
    }

    /**
     * "address, city" for display, or a placeholder while the listing has
     * no address yet.
     */
    public function title(): string
    {
        return collect([$this->address_line, $this->city])->filter()->implode(', ') ?: __('New listing');
    }

    /** The most photos one listing can have. */
    public const MAX_PHOTOS = 6;

    /**
     * Photos in display order; the first is the cover (see syncCoverPhoto()).
     */
    public function photos(): HasMany
    {
        return $this->hasMany(ListingPhoto::class)->orderBy('position')->orderBy('id');
    }

    /**
     * Keep `photo_path` — what the listings index shows — pointing at the
     * first photo, or empty when there are none.
     */
    public function syncCoverPhoto(): void
    {
        $this->photo_path = $this->photos()->first()?->path;
        $this->save();
    }

    public function notes(): HasMany
    {
        // id, not created_at: two notes added within the same second share a timestamp.
        return $this->hasMany(ListingNote::class)->latest('id');
    }

    public function leads(): BelongsToMany
    {
        return $this->belongsToMany(Lead::class, 'lead_listing');
    }

    /**
     * Delete this listing along with its photo files. The owner lead stays:
     * a Seller/Landlord is a lead in their own right and may own other
     * listings.
     */
    public function deleteWithPhoto(): void
    {
        $files = $this->photos()->pluck('path')->push($this->photo_path)->filter()->unique();

        Storage::disk('public')->delete($files->all());

        $this->delete();
    }
}
