<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Une des dix regions du Cameroun.
 *
 * Neuf existent en libelle seul dans ce prototype : elles montrent que le
 * systeme est national par construction. `peuplee` dit lesquelles portent
 * reellement des donnees -- l'interface ne doit pas laisser croire qu'une
 * region vide est une region sans activite.
 */
class Region extends Model
{
    protected $fillable = ['code', 'libelle', 'peuplee'];

    protected function casts(): array
    {
        return ['peuplee' => 'boolean'];
    }

    public function departements(): HasMany
    {
        return $this->hasMany(Departement::class);
    }

    public function arrondissements(): HasMany
    {
        return $this->hasMany(Arrondissement::class);
    }
}
