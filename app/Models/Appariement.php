<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Journal de l'assistant. AUCUN parent_id, aucun identifiant de session,
 * aucune adresse. Il sert a reperer les questions que le corpus ne couvre pas
 * encore, afin d'ecrire de nouvelles unites. Jamais a profiler quelqu'un.
 */
class Appariement extends Model
{
    public $timestamps = false;

    protected $fillable = ['texte_question', 'unite_id', 'score', 'date'];

    protected function casts(): array
    {
        return [
            'score' => 'float',
            'date' => 'datetime',
        ];
    }

    public function unite(): BelongsTo
    {
        return $this->belongsTo(UniteDigitale::class, 'unite_id');
    }

    /**
     * Un refus est un resultat, pas une panne : aucune unite n'a depasse le seuil.
     */
    public function estUnRefus(): bool
    {
        return $this->unite_id === null;
    }
}
