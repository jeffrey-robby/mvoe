<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Une langue du programme.
 *
 * C'est une DONNÉE, plus jamais du code. Le Cameroun compte plus de deux cents
 * langues ; un enum PHP en fige trois et exige un déploiement pour en ajouter
 * une quatrième. Cette décision appartient au ministère, et elle se prend en
 * chargeant des réalisations.
 *
 * `actif` retire une langue de l'interface sans supprimer les contenus déjà
 * chargés : on cesse de la proposer, on ne perd rien.
 */
class Langue extends Model
{
    protected $table = 'langues';

    protected $fillable = ['code', 'libelle', 'endonyme', 'actif', 'ordre'];

    protected function casts(): array
    {
        return ['actif' => 'boolean'];
    }

    public function scopeActives(Builder $requete): Builder
    {
        return $requete->where('actif', true)->orderBy('ordre')->orderBy('libelle');
    }

    public function realisations(): HasMany
    {
        return $this->hasMany(Realisation::class);
    }

    /**
     * Le nom à afficher dans un sélecteur de langue.
     *
     * L'endonyme d'abord : personne ne cherche « Bulu » écrit en français quand
     * il ne lit pas le français.
     */
    public function nom(): string
    {
        return $this->endonyme ?: $this->libelle;
    }

    /** La langue de repli du programme, quand rien d'autre n'est chargé. */
    public static function parDefaut(): self
    {
        return static::where('code', 'fr')->firstOrFail();
    }
}
