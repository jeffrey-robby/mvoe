<?php

namespace App\Models;

use App\Enums\StatutValidation;
use App\Enums\TypeFormation;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Un module du catalogue destiné au facilitateur.
 *
 * Deux catalogues distincts cohabitent : celui des parents (unités, feuilleton,
 * questions) et celui-ci. Le second existe parce qu'un facilitateur formé il y
 * a deux ans ne se refait pas former — il rouvre ses modules, et ce faisant il
 * rouvre l'application.
 */
class ModuleFormation extends Model
{
    protected $table = 'modules_formation';

    protected $fillable = [
        'code', 'titre', 'type', 'objectif', 'ordre', 'duree_minutes', 'statut_validation',
    ];

    protected function casts(): array
    {
        return [
            'type' => TypeFormation::class,
            'statut_validation' => StatutValidation::class,
        ];
    }

    /**
     * Le seul scope par lequel un module atteint un facilitateur.
     *
     * Un contenu non validé ne peut pas être diffusé : la règle est ici, dans
     * une condition de requête, pas dans une note de documentation. Un module
     * mal relu qui atteint cinquante facilitateurs se rattrape mal.
     */
    public function scopeDiffusables(Builder $requete): Builder
    {
        // Par `ordre` seul : trier d'abord par type donnerait l'ordre
        // alphabétique de l'enum, qui ne veut rien dire pour un facilitateur.
        // C'est le ministère qui décide de la séquence, en base.
        return $requete->where('statut_validation', StatutValidation::Valide->value)
            ->orderBy('ordre');
    }

    public function sections(): HasMany
    {
        return $this->hasMany(SectionFormation::class)->orderBy('ordre');
    }

    public function progressions(): HasMany
    {
        return $this->hasMany(ProgressionFormation::class);
    }
}
