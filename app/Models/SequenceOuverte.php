<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * L'OBSERVE. Ecrit quand le facilitateur ouvre une sequence pendant la seance.
 * Il ne declare rien ici et n'a aucun bouton a presser pour cela : c'est une
 * trace d'usage, pas une saisie. C'est ce qui la rend opposable a la declaration.
 */
class SequenceOuverte extends Model
{
    protected $table = 'sequences_ouvertes';

    protected $fillable = ['uuid', 'seance_id', 'sequence_id', 'ouverte_a', 'duree_reelle_secondes'];

    protected function casts(): array
    {
        return ['ouverte_a' => 'datetime'];
    }

    public function seance(): BelongsTo
    {
        return $this->belongsTo(Seance::class);
    }

    public function sequence(): BelongsTo
    {
        return $this->belongsTo(Sequence::class);
    }

    /**
     * Ecart entre la duree officielle du guide et le temps reellement passe.
     * Positif = la sequence a dure plus longtemps que prevu.
     */
    public function ecartDureeSecondes(): ?int
    {
        return $this->duree_reelle_secondes === null
            ? null
            : $this->duree_reelle_secondes - ($this->sequence->duree_minutes * 60);
    }
}
