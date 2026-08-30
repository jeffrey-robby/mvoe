<?php

namespace App\Models;

use App\Enums\Modalite;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Realisation extends Model
{
    protected $fillable = [
        'unite_id', 'langue_id', 'modalite', 'titre', 'contenu_texte', 'fichier_audio', 'pictogrammes',
    ];

    protected function casts(): array
    {
        return [
            'modalite' => Modalite::class,
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
     * L'audio peut manquer (fichier pas encore enregistre). Aucun parcours
     * ne doit s'interrompre pour autant : l'interface bascule sur le texte.
     */
    public function aUnAudio(): bool
    {
        return filled($this->fichier_audio);
    }
}
