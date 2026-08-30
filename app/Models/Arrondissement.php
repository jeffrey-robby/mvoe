<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * La maille de base du systeme.
 *
 * Facilitateurs, cohortes, activites, foyers et signalements y sont tous
 * rattaches : c'est ce qui permet a une portee, quel que soit son niveau, de
 * se resoudre en une simple liste d'arrondissements.
 */
class Arrondissement extends Model
{
    protected $fillable = ['departement_id', 'region_id', 'libelle'];

    public function departement(): BelongsTo
    {
        return $this->belongsTo(Departement::class);
    }

    public function region(): BelongsTo
    {
        return $this->belongsTo(Region::class);
    }

    public function facilitateurs(): HasMany
    {
        return $this->hasMany(Facilitateur::class);
    }

    public function cohortes(): HasMany
    {
        return $this->hasMany(Cohorte::class);
    }

    /** Le superviseur de cet arrondissement. */
    public function superviseur(): BelongsTo
    {
        return $this->belongsTo(User::class, 'id', 'arrondissement_id');
    }
}
