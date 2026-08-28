<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Option extends Model
{
    protected $fillable = ['question_id', 'pictogramme', 'est_attendue'];

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

    public function traductions(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(OptionTraduite::class);
    }

    public function libelle(\App\Enums\Langue $langue): ?string
    {
        $traduction = $this->traductions->firstWhere('langue', $langue)
            ?? $this->traductions->firstWhere('langue', \App\Enums\Langue::Fr);

        return $traduction?->libelle;
    }
}
