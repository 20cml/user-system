<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * A document a column asks for. `key` is what a
 * lead's tick is stored under.
 */
#[Fillable(['pipeline_stage_id', 'key', 'label', 'position'])]
class StageDocument extends Model
{
    public function stage(): BelongsTo
    {
        return $this->belongsTo(PipelineStage::class, 'pipeline_stage_id');
    }
}
