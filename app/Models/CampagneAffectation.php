<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Une entité touchée par une campagne, à un niveau donné.
 *
 * `entite_id` désigne une région, un département, un arrondissement ou un
 * facilitateur selon `niveau`. Il n'y a pas de clé étrangère : une contrainte
 * polymorphe coûterait plus qu'elle ne protégerait dans un prototype où ces
 * quatre tables ne sont jamais supprimées.
 */
class CampagneAffectation extends Model
{
    protected $table = 'campagne_affectations';

    protected $fillable = [
        'campagne_id', 'niveau', 'entite_id', 'statut', 'date_reception',
    ];

    protected function casts(): array
    {
        return ['date_reception' => 'datetime'];
    }

    public function campagne(): BelongsTo
    {
        return $this->belongsTo(Campagne::class);
    }

    public function recue(): bool
    {
        return $this->date_reception !== null;
    }

    /** Le libellé de l'entité, quel que soit son niveau. */
    public function libelleEntite(): ?string
    {
        return match ($this->niveau) {
            'region' => Region::find($this->entite_id)?->libelle,
            'departement' => Departement::find($this->entite_id)?->libelle,
            'arrondissement' => Arrondissement::find($this->entite_id)?->libelle,
            'facilitateur' => Facilitateur::find($this->entite_id)?->nom,
            default => null,
        };
    }
}
