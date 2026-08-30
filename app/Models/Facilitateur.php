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

    use \App\Models\Concerns\LimiteParPortee;

    protected $fillable = [
        'nom', 'telephone', 'code_appareil', 'email', 'password',
        'arrondissement_id', 'superviseur_id', 'type_juridique',
        'organisation_rattachement', 'date_formation_initiale', 'derniere_activite',
    ];

    protected $hidden = ['code_appareil', 'password'];

    protected function casts(): array
    {
        return [
            'date_formation_initiale' => 'date',
            'type_juridique' => \App\Enums\TypeJuridique::class,
            'derniere_activite' => 'date',
            // Code d'appareil remis en main propre a la formation, jamais par SMS.
            'code_appareil' => 'hashed',
            'password' => 'hashed',
        ];
    }

    public function arrondissement(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(Arrondissement::class);
    }

    /** Le superviseur qui l'a enregistré et lui a remis ses identifiants. */
    public function superviseur(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(User::class, 'superviseur_id');
    }

    public function portee(): \App\Support\Portee
    {
        return \App\Support\Portee::pourFacilitateur($this);
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
     * Où il en est dans les modules de formation.
     *
     * Visible par son superviseur. Ce n'est pas une surveillance : c'est la
     * seule façon de repérer qui décroche avant qu'il ne disparaîsse du
     * registre, et de lui proposer quelque chose plutôt que de constater six
     * mois plus tard qu'il n'anime plus.
     */
    public function progressions(): HasMany
    {
        return $this->hasMany(ProgressionFormation::class);
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
        // `diffInDays` rend un flottant : la troncature implicite est dépréciée,
        // et un « il y a 13 jours » n'a de toute façon pas de décimales.
        return $this->derniere_activite === null
            ? null
            : (int) $this->derniere_activite->diffInDays(now());
    }
}
