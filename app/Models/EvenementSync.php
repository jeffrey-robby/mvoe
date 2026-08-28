<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Journal brut de la remontee. Le client envoie des evenements horodates et
 * idempotents, jamais des etats : chaque evenement porte un UUID genere hors
 * ligne, et un renvoi du meme UUID est ignore en silence.
 *
 * Ce journal n'est jamais modifie ni purge. Les tables seances, presences,
 * sequences_ouvertes et fiches_fidelite n'en sont que la projection courante :
 * si une correction ecrase un etat, l'evenement d'origine reste ici.
 */
class EvenementSync extends Model
{
    protected $table = 'evenements_sync';

    public $timestamps = false;

    protected $fillable = ['uuid', 'type', 'seance_uuid', 'charge', 'emis_a', 'recu_a'];

    protected function casts(): array
    {
        return [
            'charge' => 'array',
            'emis_a' => 'datetime',
            'recu_a' => 'datetime',
        ];
    }
}
