<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SectionFormation extends Model
{
    protected $table = 'sections_formation';

    protected $fillable = [
        'module_formation_id', 'ordre', 'titre', 'contenu_texte', 'fichier_audio', 'duree_minutes',
    ];

    public function module(): BelongsTo
    {
        return $this->belongsTo(ModuleFormation::class, 'module_formation_id');
    }

    /** L'audio peut manquer : aucun parcours ne s'interrompt pour autant. */
    public function aUnAudio(): bool
    {
        return filled($this->fichier_audio);
    }
}
