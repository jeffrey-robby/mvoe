<?php

namespace App\Models;

use App\Enums\DifficulteFonctionnelle;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Collection;

/**
 * Un dossier de foyer, sans identité.
 *
 * Aucun nom, aucun prénom, aucune adresse précise, aucune coordonnée GPS. Ce
 * modèle n'a pas de colonne où mettre un nom, et il ne doit jamais en avoir :
 * c'est ce qui permet d'enregistrer une visite à domicile sans constituer un
 * fichier de familles vulnérables.
 */
class Foyer extends Model
{
    use \App\Models\Concerns\LimiteParPortee;

    protected $fillable = [
        'uuid', 'facilitateur_id', 'arrondissement_id', 'localite',
        'nb_adultes', 'nb_enfants', 'difficultes_fonctionnelles_foyer',
        'deja_suivi_programme', 'parent_id', 'recue_a',
    ];

    protected function casts(): array
    {
        return [
            'difficultes_fonctionnelles_foyer' => 'array',
            'deja_suivi_programme' => 'boolean',
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

    public function visites(): HasMany
    {
        return $this->hasMany(Visite::class);
    }

    /** @return Collection<int, DifficulteFonctionnelle> */
    public function difficultes(): Collection
    {
        return collect($this->difficultes_fonctionnelles_foyer ?? [])
            ->map(fn (string $valeur) => DifficulteFonctionnelle::tryFrom($valeur))
            ->filter()
            ->values();
    }

    public function taille(): int
    {
        return $this->nb_adultes + $this->nb_enfants;
    }
}
