<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * LE DECLARE. Rempli par le facilitateur apres la seance, jamais pendant.
 *
 * Une ligne par sequence, plus une ligne a `sequence_id` null qui porte le
 * champ libre de fin de seance (« qu'est-ce qui a le moins bien marche ? »).
 */
class FicheFidelite extends Model
{
    protected $table = 'fiches_fidelite';

    protected $fillable = ['uuid', 'seance_id', 'sequence_id', 'realisee_bool', 'note_qualite', 'commentaire'];

    protected function casts(): array
    {
        return ['realisee_bool' => 'boolean'];
    }

    public function seance(): BelongsTo
    {
        return $this->belongsTo(Seance::class);
    }

    public function sequence(): BelongsTo
    {
        return $this->belongsTo(Sequence::class);
    }

    public function estLeBilanDeSeance(): bool
    {
        return $this->sequence_id === null;
    }
}
