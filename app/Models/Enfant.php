<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * AUCUNE date de naissance : une tranche d'age suffit au programme
 * et ne permet pas de reidentifier un enfant.
 */
class Enfant extends Model
{
    protected $fillable = ['parent_id', 'tranche_age', 'sexe'];

    public function parent(): BelongsTo
    {
        return $this->belongsTo(ParentProgramme::class, 'parent_id');
    }
}
