<?php

namespace App\Models;

use App\Enums\TypeSequence;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Sequence extends Model
{
    protected $fillable = [
        'module_id', 'titre', 'ordre', 'duree_minutes', 'type', 'consigne', 'est_brise_glace',
    ];

    protected function casts(): array
    {
        return [
            'type' => TypeSequence::class,
            'est_brise_glace' => 'boolean',
        ];
    }

    public function module(): BelongsTo
    {
        return $this->belongsTo(Module::class);
    }

    /**
     * Une sequence de contenu peut porter plusieurs messages cles.
     */
    public function unites(): HasMany
    {
        return $this->hasMany(UniteDigitale::class);
    }

    public function ouvertures(): HasMany
    {
        return $this->hasMany(SequenceOuverte::class);
    }

    public function fichesFidelite(): HasMany
    {
        return $this->hasMany(FicheFidelite::class);
    }

    public function scopeOrdonnees(Builder $query): Builder
    {
        return $query->orderBy('ordre');
    }
}
