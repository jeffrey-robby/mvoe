<?php

namespace App\Canaux;

use App\Enums\Canal;
use App\Models\Diffusion;
use Illuminate\Support\Carbon;

/**
 * Base commune aux quatre pilotes de démonstration.
 *
 * Ils sont FACTICES et le disent : `factice()` rend `true`, et l'interface
 * l'affiche. Un prototype qui laisserait croire que des SMS partent vraiment
 * mentirait à son jury.
 *
 * Ce qu'ils font de vrai : ils écrivent des `diffusions` et savent les relire.
 * C'est cette moitié-là qui survivra au passage en production.
 */
abstract class PiloteFactice implements PiloteDeCanal
{
    public function factice(): bool
    {
        return true;
    }

    /**
     * Le taux d'aboutissement du canal, sur le terrain.
     *
     * Ces valeurs viennent des ordres de grandeur observés au Cameroun, pas
     * d'un générateur aléatoire : un chiffre inventé au hasard serait moins
     * utile qu'aucun chiffre.
     */
    abstract protected function tauxDaboutissement(): float;

    public function envoyer(array $charge): array
    {
        $volume = (int) $charge['volume'];
        $aboutis = (int) round($volume * $this->tauxDaboutissement());

        return ['volume' => $volume, 'aboutis' => $aboutis, 'statut' => 'diffusee'];
    }

    public function statistiques(Carbon $du, Carbon $au): array
    {
        $diffusions = Diffusion::where('canal', $this->canal()->value)
            ->whereBetween('date', [$du, $au])
            ->get();

        $volume = $diffusions->sum('volume');
        $aboutis = $diffusions->sum('aboutis');

        return [
            'canal' => $this->canal()->value,
            'libelle' => $this->canal()->libelle(),
            'factice' => $this->factice(),
            'diffusions' => $diffusions->count(),
            'volume' => $volume,
            'unite' => $this->canal()->unite(),
            'aboutis' => $aboutis,
            'aboutissement' => $this->canal()->aboutissement(),
            'taux' => $volume > 0 ? round($aboutis / $volume * 100, 1) : null,
        ];
    }
}
