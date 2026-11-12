<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * One column of an agent's funnel for one lead type and property type. `key` is what a lead's
 * status stores; it never changes when the column is renamed.
 */
#[Fillable(['user_id', 'lead_type', 'property_type', 'key', 'label', 'position'])]
class PipelineStage extends Model
{
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function documents(): HasMany
    {
        return $this->hasMany(StageDocument::class)->orderBy('position')->orderBy('id');
    }
}
