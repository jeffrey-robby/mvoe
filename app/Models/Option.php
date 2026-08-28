<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Option extends Model
{
    protected $fillable = ['question_id', 'libelle', 'pictogramme', 'est_attendue'];

    // `est_attendue` sert a l'analyse du programme, jamais a l'affichage.
    // Il est masque par defaut pour qu'aucune serialisation ne le laisse
    // filer vers l'espace parent.
    protected $hidden = ['est_attendue'];

    protected function casts(): array
    {
        return ['est_attendue' => 'boolean'];
    }

    public function question(): BelongsTo
    {
        return $this->belongsTo(Question::class);
    }
}
