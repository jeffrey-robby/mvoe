<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Un groupe de soutien parental.
 *
 * `derniere_reunion` est l'indicateur de continuité du dossier. Le statut
 * « actif » n'est PAS une colonne : il se recalcule à chaque consultation à
 * partir de cette date, exactement comme le statut d'un facilitateur. Un
 * booléen `actif` en base resterait à `true` pendant des années sans que
 * personne ne s'en aperçoive.
 */
class GroupeSoutien extends Model
{
    use \App\Models\Concerns\LimiteParPortee;

    protected $table = 'groupes_soutien';

    protected $fillable = [
        'uuid', 'libelle', 'cohorte_id', 'arrondissement_id', 'facilitateur_id',
        'date_creation', 'derniere_reunion',
    ];

    protected function casts(): array
    {
        return [
            'date_creation' => 'date',
            'derniere_reunion' => 'date',
        ];
    }

    public function cohorte(): BelongsTo
    {
        return $this->belongsTo(Cohorte::class);
    }

    public function arrondissement(): BelongsTo
    {
        return $this->belongsTo(Arrondissement::class);
    }

    public function facilitateur(): BelongsTo
    {
        return $this->belongsTo(Facilitateur::class);
    }

    public function membres(): BelongsToMany
    {
        return $this->belongsToMany(ParentProgramme::class, 'membres_gsp', 'gsp_id', 'parent_id')
            ->withTimestamps();
    }

    public function binomes(): HasMany
    {
        return $this->hasMany(Binome::class, 'gsp_id');
    }

    /** Un groupe qui ne s'est pas réuni depuis le seuil n'est plus un groupe. */
    public function estActif(): bool
    {
        if ($this->derniere_reunion === null) {
            return false;
        }

        return (int) $this->derniere_reunion->diffInDays(now())
            <= config('mvoe.gsp.jours_sans_reunion');
    }

    public function joursDepuisReunion(): ?int
    {
        return $this->derniere_reunion === null
            ? null
            : (int) $this->derniere_reunion->diffInDays(now());
    }
}
