<?php

namespace App\Models;

use App\Enums\GraviteSignalement;
use App\Enums\StatutSignalement;
use App\Enums\TypeSignalement;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Une situation préoccupante remontée du terrain.
 *
 * Le modèle ne porte AUCUNE identité, et n'a pas de colonne où en mettre une.
 * Le système ne notifie jamais une autorité : ce modèle n'a pas de canal de
 * sortie, seulement un statut et une file.
 */
class Signalement extends Model
{
    use \App\Models\Concerns\LimiteParPortee;

    protected $fillable = [
        'uuid', 'activite_id', 'facilitateur_id', 'arrondissement_id',
        'type', 'gravite', 'statut',
        'traite_par_superviseur_id', 'date_traitement', 'suite_donnee', 'recue_a',
    ];

    protected function casts(): array
    {
        return [
            'type' => TypeSignalement::class,
            'gravite' => GraviteSignalement::class,
            'statut' => StatutSignalement::class,
            'date_traitement' => 'datetime',
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

    public function activite(): BelongsTo
    {
        return $this->belongsTo(Activite::class);
    }

    public function superviseur(): BelongsTo
    {
        return $this->belongsTo(User::class, 'traite_par_superviseur_id');
    }

    public function estOuvert(): bool
    {
        return $this->statut->estOuvert();
    }

    /**
     * Depuis combien de jours il attend.
     *
     * Le point de départ est `recue_a`, l'instant où le signalement est arrivé
     * au serveur — pas `created_at`, qui n'est que l'heure de l'écriture en
     * base. Sur une remontée différée de trois semaines, les deux n'ont rien
     * à voir, et c'est la première qui dit ce que le facilitateur a vécu.
     */
    public function joursDattente(): int
    {
        $fin = $this->date_traitement ?? now();

        return max(0, (int) $this->recue_a->diffInDays($fin));
    }
}
