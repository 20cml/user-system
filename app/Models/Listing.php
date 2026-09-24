<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Support\Facades\Storage;

#[Fillable([
    'user_id', 'owner_lead_id', 'source', 'listing_type', 'property_type', 'status',
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
     * The lead selling or renting out this listing — every listing has
     * exactly one (seller for a 'sale' listing, landlord for a 'rent' one).
     */
    public function owner(): BelongsTo
    {
        return $this->belongsTo(Lead::class, 'owner_lead_id');
    }

    public function leads(): BelongsToMany
    {
        return $this->belongsToMany(Lead::class, 'lead_listing');
    }

    /**
     * Recompute this listing's status from the funnel status of its linked
     * leads: 'closed' once any linked lead has closed, 'pending' once any
     * linked lead has completed its 'under_contract' stage, otherwise
     * 'available'. Always reflects the leads' current statuses — if the
     * lead that had closed it later regresses (e.g. a document gets
     * unchecked by mistake), the listing follows it back down.
     */
    public function syncStatusFromLeads(): void
    {
        $leadStatuses = $this->leads()->pluck('status');

        $this->status = match (true) {
            $leadStatuses->contains('closed') => 'closed',
            $leadStatuses->contains('under_contract') => 'pending',
            default => 'available',
        };

        $this->save();
    }

    /**
     * Whether $lead is the one whose funnel progress is driving this
     * listing's current status — the lead that closed it, or (while still
     * pending) the one under contract. Used to highlight which of several
     * "Interested Leads" is the actual one, without touching the others.
     */
    public function leadDrivesCurrentStatus(Lead $lead): bool
    {
        return match ($this->status) {
            'closed' => $lead->status === 'closed',
            'pending' => $lead->status === 'under_contract',
            default => false,
        };
    }

    /**
     * Delete this listing along with everything that only exists because of
     * it: its photo file, and its owner lead (a Seller/Landlord lead has no
     * page or purpose of its own — see LeadRequest). Deletes the listing
     * before the owner so the owner_lead_id foreign key (restrictOnDelete)
     * never blocks it, and doesn't rely on the database's own multi-hop
     * cascade ordering, which isn't guaranteed to run this way on its own.
     */
    public function deleteWithOwner(): void
    {
        if ($this->photo_path) {
            Storage::disk('public')->delete($this->photo_path);
        }

        $owner = $this->owner;

        $this->delete();

        $owner?->delete();
    }
}
