<?php

namespace App\Canaux;

use App\Enums\Canal;
use Illuminate\Support\Carbon;

/**
 * Un canal de diffusion, vu du programme.
 *
 * Deux gestes seulement : envoyer, et rendre compte. Tout le reste — API de
 * l'opérateur, format des numéros, quotas, files d'attente — appartient au
 * pilote et ne remonte jamais ici.
 *
 * **C'est l'argument de réplicabilité.** Passer de ce prototype à une
 * infrastructure nationale ne change qu'un pilote : le reste du programme ne
 * sait pas, et n'a pas à savoir, qui achemine ses messages. Si un jour cette
 * interface doit changer pour accueillir un opérateur, c'est que l'abstraction
 * était fausse.
 */
interface PiloteDeCanal
{
    public function canal(): Canal;

    /**
     * Émettre vers une cible.
     *
     * @param  array<string, mixed>  $charge  Ce qui est diffusé : unité, langue, territoire.
     * @return array{volume: int, aboutis: int, statut: string}
     */
    public function envoyer(array $charge): array;

    /**
     * Ce que le canal a produit sur une période.
     *
     * @return array<string, mixed>
     */
    public function statistiques(Carbon $du, Carbon $au): array;
}
