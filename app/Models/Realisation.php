<?php

namespace App\Models;

use App\Enums\Modalite;
use App\Enums\StatutValidation;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Realisation extends Model
{
    protected $fillable = [
        'unite_id', 'langue_id', 'modalite', 'statut_validation',
        'titre', 'contenu_texte', 'fichier_audio', 'pictogrammes',
    ];

    protected function casts(): array
    {
        return [
            'modalite' => Modalite::class,
            'statut_validation' => StatutValidation::class,
            'pictogrammes' => 'array',
        ];
    }

    public function unite(): BelongsTo
    {
        return $this->belongsTo(UniteDigitale::class, 'unite_id');
    }

    public function langue(): BelongsTo
    {
        return $this->belongsTo(Langue::class);
    }

    /**
     * Le seul scope par lequel une realisation atteint un parent ou un paquet.
     *
     * Un contenu non valide ne peut pas etre diffuse. La regle valait deja pour
     * les modules de formation ; elle compte davantage ici : une realisation
     * mal relue qui part en audio dans une langue que personne ne relit au
     * ministere ne se rattrape pas. Le brouillon reste visible du MINPROFF,
     * jamais du terrain.
     */
    public function scopeDiffusables(Builder $requete): Builder
    {
        return $requete->where('statut_validation', StatutValidation::Valide->value);
    }

    public function estDiffusable(): bool
    {
        return $this->statut_validation->estDiffusable();
    }

    /**
     * L'audio peut manquer (fichier pas encore enregistre). Aucun parcours
     * ne doit s'interrompre pour autant : l'interface bascule sur le texte.
     */
    public function aUnAudio(): bool
    {
        return filled($this->fichier_audio);
    }
}
