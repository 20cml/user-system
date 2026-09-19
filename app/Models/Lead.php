<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
    'user_id', 'first_name', 'last_name', 'phone', 'email', 'status', 'type',
    'financing_preapproval', 'financing_income_proof', 'financing_id_document',
])]
class Lead extends Model
{
    /** @use HasFactory<\Database\Factories\LeadFactory> */
    use HasFactory;

    protected function casts(): array
    {
        return [
            'financing_preapproval' => 'boolean',
            'financing_income_proof' => 'boolean',
            'financing_id_document' => 'boolean',
        ];
    }

    /**
     * The full display name — `last_name` is optional, so this only adds a
     * space and the surname when one is actually on file.
     */
    protected function name(): Attribute
    {
        return Attribute::make(
            get: fn () => trim("{$this->first_name} {$this->last_name}"),
        );
    }

    /**
     * Re-derive this Buyer lead's status from its current data, advancing it
     * at most one step per stage (New → Contacted → Qualified → Active Search).
     * No-ops for non-Buyer leads and for leads already marked Lost — only a
     * manual status change can move a Lost lead anywhere else.
     */
    public function advanceBuyerFunnel(): void
    {
        if ($this->type !== 'buyer' || $this->status === 'lost') {
            return;
        }

        if ($this->status === 'new' && $this->notes()->exists()) {
            $this->status = 'contacted';
        }

        if ($this->status === 'contacted'
                && $this->financing_preapproval
                && $this->financing_income_proof
                && $this->financing_id_document) {
            $this->status = 'qualified';
        }

        if ($this->status === 'qualified' && $this->listings()->exists()) {
            $this->status = 'active_search';
        }

        $this->save();
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function listings(): BelongsToMany
    {
        return $this->belongsToMany(Listing::class, 'lead_listing');
    }

    public function notes(): HasMany
    {
        // Ordering by created_at alone isn't reliable when two notes are added within the
        // same second (equal timestamps); id always reflects true insertion order.
        return $this->hasMany(LeadNote::class)->latest('id');
    }
}
