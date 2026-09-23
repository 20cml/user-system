<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

#[Fillable([
    'user_id', 'source', 'listing_type', 'property_type', 'status',
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

    public function leads(): BelongsToMany
    {
        return $this->belongsToMany(Lead::class, 'lead_listing');
    }

    /**
     * Recompute this listing's status from the funnel status of its linked
     * leads: 'closed' once any linked lead has closed (whichever lead closes
     * first locks the listing there — once closed, this never re-evaluates,
     * even if that lead's status later regresses), 'pending' once any linked
     * lead has completed its 'under_contract' stage, otherwise 'available'.
     * No-ops once the listing is already closed.
     */
    public function syncStatusFromLeads(): void
    {
        if ($this->status === 'closed') {
            return;
        }

        $leadStatuses = $this->leads()->pluck('status');

        $this->status = match (true) {
            $leadStatuses->contains('closed') => 'closed',
            $leadStatuses->contains('under_contract') => 'pending',
            default => 'available',
        };

        $this->save();
    }
}
