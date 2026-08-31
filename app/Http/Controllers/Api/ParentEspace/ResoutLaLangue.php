<?php

namespace App\Http\Controllers\Api\ParentEspace;

use App\Models\Langue;
use Illuminate\Http\Request;

/**
 * Quelle langue servir a un parent.
 *
 * Trois sources, dans cet ordre : ce qu'il demande explicitement (il bascule
 * de langue sur l'ecran), sa langue enregistree, et enfin la langue de repli
 * du programme.
 *
 * Ce trait ne fait QUE resoudre la langue demandee. Il ne decide jamais d'un
 * repli silencieux : chaque ecran compare ce qu'il a servi a ce qui etait
 * demande, et le dit. Afficher du francais en laissant croire que c'est du
 * bulu serait pire que de ne rien afficher.
 */
trait ResoutLaLangue
{
    protected function langueDemandee(Request $request): Langue
    {
        $code = (string) $request->query('langue', $request->input('langue', ''));

        if ($code !== '') {
            $langue = Langue::where('code', $code)->where('actif', true)->first();

            if ($langue !== null) {
                return $langue;
            }
        }

        /*
        | Les contenus se lisent sans compte. Un parent connecté a une langue
        | attachée à son dossier ; un visiteur anonyme n'en a pas, et repart
        | sur la langue par défaut du programme tant qu'il n'en a pas choisi
        | une — auquel cas elle arrive par `?langue=`, traitée plus haut.
        */
        return $request->user('sanctum')?->langue ?? Langue::parDefaut();
    }

    /** Le format d'une langue partout ou l'API en rend une. */
    protected function langueRendue(?Langue $langue): ?array
    {
        return $langue === null ? null : [
            'code' => $langue->code,
            'libelle' => $langue->libelle,
            'nom' => $langue->nom(),
        ];
    }
}
