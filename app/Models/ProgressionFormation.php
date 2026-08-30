<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Où en est un facilitateur dans un module.
 *
 * Cette progression est visible par son superviseur. Ce n'est pas une
 * surveillance : c'est la seule façon de repérer qui décroche avant qu'il ne
 * disparaisse du registre, et de lui proposer quelque chose plutôt que de
 * constater six mois plus tard qu'il n'anime plus.
 */
class ProgressionFormation extends Model
{
    protected $table = 'progressions_formation';

    protected $fillable = [
        'facilitateur_id', 'module_formation_id', 'sections_vues',
        'derniere_ouverture', 'termine_a',
    ];

    protected function casts(): array
    {
        return [
            'sections_vues' => 'array',
            'derniere_ouverture' => 'datetime',
            'termine_a' => 'datetime',
        ];
    }

    public function facilitateur(): BelongsTo
    {
        return $this->belongsTo(Facilitateur::class);
    }

    public function module(): BelongsTo
    {
        return $this->belongsTo(ModuleFormation::class, 'module_formation_id');
    }

    public function estTermine(): bool
    {
        return $this->termine_a !== null;
    }

    /** Le pourcentage de sections vues, pour l'afficher d'un coup d'œil. */
    public function avancement(int $sectionsTotal): int
    {
        if ($sectionsTotal === 0) {
            return 0;
        }

        return (int) round(count($this->sections_vues ?? []) / $sectionsTotal * 100);
    }
}
