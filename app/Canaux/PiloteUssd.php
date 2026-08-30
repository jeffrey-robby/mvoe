<?php

namespace App\Canaux;

use App\Enums\Canal;
use App\Models\Diffusion;
use Illuminate\Support\Carbon;

/**
 * USSD. Fonctionne sur n'importe quel téléphone, sans forfait data — c'est ce
 * qui en fait le canal le plus universel du pays.
 *
 * Son indicateur propre est l'ABANDON : à quel écran les gens raccrochent. Une
 * session ouverte ne dit rien ; une session abandonnée au troisième menu dit
 * que le troisième menu est mauvais.
 */
class PiloteUssd extends PiloteFactice
{
    public function canal(): Canal
    {
        return Canal::Ussd;
    }

    /** Beaucoup ouvrent, la moitié va au bout : les menus sont longs. */
    protected function tauxDaboutissement(): float
    {
        return 0.54;
    }

    public function statistiques(Carbon $du, Carbon $au): array
    {
        $base = parent::statistiques($du, $au);

        $sessions = Diffusion::where('canal', $this->canal()->value)
            ->whereBetween('date', [$du, $au])
            ->get();

        return [...$base, 'abandons' => $sessions->sum('volume') - $sessions->sum('aboutis')];
    }
}
