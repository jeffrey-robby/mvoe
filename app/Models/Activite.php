<?php

namespace App\Models;

use App\Enums\TypeActivite;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Une activité de terrain.
 *
 * Elle porte son arrondissement directement : une activité a bien lieu quelque
 * part, et passer par le facilitateur serait fragile le jour où il est muté.
 */
class Activite extends Model
{
    use \App\Models\Concerns\LimiteParPortee;

    protected $fillable = [
        'uuid', 'facilitateur_id', 'arrondissement_id', 'cohorte_id',
        'type', 'date', 'lieu', 'duree_minutes',
        'nb_parents_touches', 'nb_hommes', 'nb_femmes', 'nb_participants_handicap',
        'commentaire', 'recue_a',
    ];

    protected function casts(): array
    {
        return [
            'type' => TypeActivite::class,
            'date' => 'date',
            'recue_a' => 'datetime',
        ];
    }

    public function facilitateur(): BelongsTo
    {
        return $this->belongsTo(Facilitateur::class);
    }

    public function arrondissement(): BelongsTo
    {
        return $this->belongsTo(Arrondissement::class);
    }

    public function cohorte(): BelongsTo
    {
        return $this->belongsTo(Cohorte::class);
    }

    public function signalements(): HasMany
    {
        return $this->hasMany(Signalement::class);
    }

    /**
     * La part des participants declares en situation de handicap.
     *
     * C'est le chiffre qui rend le critere mesurable. Il n'a de sens que
     * rapporte au nombre touche : « 2 » ne veut rien dire, « 2 sur 35 » si.
     */
    public function partHandicap(): ?float
    {
        return $this->nb_parents_touches > 0
            ? round($this->nb_participants_handicap / $this->nb_parents_touches * 100, 1)
            : null;
    }

    /**
     * Le sexe n'est pas toujours renseigné pour tout le monde. On le dit au
     * lieu de compléter au hasard.
     */
    public function sexeNonRenseigne(): int
    {
        return max(0, $this->nb_parents_touches - $this->nb_hommes - $this->nb_femmes);
    }
}
