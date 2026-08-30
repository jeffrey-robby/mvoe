<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

/**
 * Entree guidee de l'assistant, pour le parent qui ne sait pas ecrire.
 *
 * Ces libelles ne sont PAS des reponses pre-ecrites : ils passent par le meme
 * appariement que le texte libre. Plusieurs d'entre eux ne trouvent rien dans
 * le corpus, et l'application le dit. C'est voulu, et c'est ce qu'il faut montrer.
 */
class SituationFrequente extends Model
{
    protected $table = 'situations_frequentes';

    protected $fillable = ['libelle', 'pictogramme', 'langue_id', 'fichier_audio', 'ordre'];

    public function langue(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(Langue::class);
    }

    protected function casts(): array
    {
        return [];
    }

    public function scopeOrdonnees(Builder $query): Builder
    {
        return $query->orderBy('ordre');
    }
}
