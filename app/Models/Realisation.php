<?php

namespace App\Models;

use App\Enums\Langue;
use App\Enums\Modalite;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Realisation extends Model
{
    protected $fillable = [
        'unite_id', 'langue', 'modalite', 'titre', 'contenu_texte', 'fichier_audio', 'pictogrammes',
    ];

    protected function casts(): array
    {
        return [
            'langue' => Langue::class,
            'modalite' => Modalite::class,
            'pictogrammes' => 'array',
        ];
    }

    public function unite(): BelongsTo
    {
        return $this->belongsTo(UniteDigitale::class, 'unite_id');
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
