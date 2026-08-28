<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Binome extends Model
{
    protected $fillable = ['parent_a_id', 'parent_b_id'];

    public function parentA(): BelongsTo
    {
        return $this->belongsTo(ParentProgramme::class, 'parent_a_id');
    }

    public function parentB(): BelongsTo
    {
        return $this->belongsTo(ParentProgramme::class, 'parent_b_id');
    }
}
