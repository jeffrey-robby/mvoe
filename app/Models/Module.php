<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Module extends Model
{
    protected $fillable = ['curriculum_version_id', 'numero', 'titre', 'ordre'];

    public function curriculumVersion(): BelongsTo
    {
        return $this->belongsTo(CurriculumVersion::class);
    }

    public function sequences(): HasMany
    {
        return $this->hasMany(Sequence::class);
    }

    public function unites(): HasMany
    {
        return $this->hasMany(UniteDigitale::class);
    }

    public function seances(): HasMany
    {
        return $this->hasMany(Seance::class);
    }

    /**
     * `ordre` sert a afficher, jamais a identifier : on trie avec, on ne cible pas avec.
     */
    public function scopeOrdonnes(Builder $query): Builder
    {
        return $query->orderBy('ordre');
    }

    /**
     * Les modules annonces mais encore vides doivent apparaitre dans l'interface,
     * en montrant qu'ils ne sont pas prets plutot qu'en se faisant passer pour prets.
     */
    public function estRenseigne(): bool
    {
        return $this->sequences()->exists();
    }
}
