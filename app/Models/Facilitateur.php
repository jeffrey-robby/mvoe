<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Laravel\Sanctum\HasApiTokens;

/**
 * Seule entite nominative du systeme. Ce sont des agents publics :
 * leur nom et leur telephone constituent l'annuaire que le parent consulte.
 */
class Facilitateur extends Model
{
    use HasApiTokens;

    protected $fillable = [
        'nom', 'telephone', 'code_appareil', 'email', 'password',
        'arrondissement', 'date_formation', 'derniere_activite',
    ];

    protected $hidden = ['code_appareil', 'password'];

    protected function casts(): array
    {
        return [
            'date_formation' => 'date',
            'derniere_activite' => 'date',
            // Code d'appareil remis en main propre a la formation, jamais par SMS.
            'code_appareil' => 'hashed',
            'password' => 'hashed',
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
