<?php

namespace App\Models\Concerns;

use App\Support\Portee;
use Illuminate\Database\Eloquent\Builder;

/**
 * Applique une portée à un modèle.
 *
 * Le seul endroit du système où un filtre de portée est écrit. Toute requête de
 * données passe par `dansLaPortee()` ; une condition équivalente écrite à la
 * main dans un contrôleur est un défaut, parce que c'est celle-là qu'on
 * oubliera dans la requête suivante. Un manquement ici est une fuite de données
 * entre régions.
 *
 * Deux façons pour un modèle d'être rattaché à un arrondissement :
 *
 *   - **directement**, par une colonne `arrondissement_id` — c'est le cas du
 *     facilitateur, de la cohorte, et le sera des activités, des foyers et des
 *     signalements ;
 *   - **par un relais**, quand le rattachement passe par un autre modèle — une
 *     séance appartient à une cohorte, qui appartient à un arrondissement. Le
 *     modèle déclare alors `relaisDePortee()`, et la condition reste écrite une
 *     seule fois : celle du relais, qui est elle-même une portée.
 */
trait LimiteParPortee
{
    public function scopeDansLaPortee(Builder $requete, Portee $portee): Builder
    {
        // Le national ne filtre rien : il voit jusqu'au dernier parent des dix
        // régions. C'est le seul cas où l'absence de filtre est correcte.
        if ($portee->estNationale()) {
            return $requete;
        }

        $table = $requete->getModel()->getTable();

        if ($relais = static::relaisDePortee()) {
            [$modele, $cleEtrangere] = $relais;

            return $requete->whereIn(
                $table.'.'.$cleEtrangere,
                $modele::dansLaPortee($portee)->select($modele::make()->getTable().'.id'),
            );
        }

        // `null` ne peut arriver que pour le national, traité au-dessus. Une
        // liste vide reste une liste vide : elle ne montre rien, elle n'ouvre
        // pas tout.
        return $requete->whereIn(
            $table.'.'.static::colonneDArrondissement(),
            $portee->arrondissements() ?? collect(),
        );
    }

    /**
     * Le modèle qui porte l'arrondissement à la place de celui-ci, et la clé
     * étrangère qui y mène.
     *
     * @return ?array{class-string, string}
     */
    protected static function relaisDePortee(): ?array
    {
        return null;
    }

    protected static function colonneDArrondissement(): string
    {
        return 'arrondissement_id';
    }
}
