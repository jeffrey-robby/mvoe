<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Une visite à domicile.
 *
 * `observations_structurees` est un tableau de cases cochées, jamais un récit :
 * un champ libre finit toujours par contenir un nom, une rue, un détail qui
 * réidentifie. Ce qui doit être dit avec des mots passe par un signalement,
 * qui lui non plus ne porte aucune identité.
 */
class Visite extends Model
{
    protected $fillable = [
        'uuid', 'foyer_id', 'facilitateur_id', 'date',
        'observations_structurees', 'suivi_prevu', 'recue_a',
    ];

    protected function casts(): array
    {
        return [
            'date' => 'date',
            'observations_structurees' => 'array',
            'suivi_prevu' => 'boolean',
            'recue_a' => 'datetime',
        ];
    }

    public function foyer(): BelongsTo
    {
        return $this->belongsTo(Foyer::class);
    }

    public function facilitateur(): BelongsTo
    {
        return $this->belongsTo(Facilitateur::class);
    }
}
