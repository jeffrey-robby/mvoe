<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Seule entite nominative du systeme. Ce sont des agents publics :
 * leur nom et leur telephone constituent l'annuaire que le parent consulte.
 */
class Facilitateur extends Model
{
    protected $fillable = ['nom', 'telephone', 'arrondissement', 'date_formation', 'derniere_activite'];

    protected function casts(): array
    {
        return [
            'date_formation' => 'date',
            'derniere_activite' => 'date',
        ];
    }

    public function cohortes(): HasMany
    {
        return $this->hasMany(Cohorte::class);
    }

    public function seances(): HasMany
    {
        return $this->hasMany(Seance::class);
    }

    /**
     * Statut recalcule a chaque consultation, jamais stocke : un statut en base
     * se perime en silence. Le seuil vit dans config/mvoe.php.
     */
    public function estActif(): bool
    {
        if ($this->derniere_activite === null) {
            return false;
        }

        return $this->derniere_activite->diffInDays(now()) <= config('mvoe.facilitateur.jours_inactivite');
    }

    public function joursDepuisActivite(): ?int
    {
        return $this->derniere_activite?->diffInDays(now());
    }
}
