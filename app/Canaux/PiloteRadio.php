<?php

namespace App\Canaux;

use App\Enums\Canal;
use App\Models\Diffusion;
use Illuminate\Support\Carbon;

/**
 * Radio communautaire.
 *
 * **AUCUNE AUDIENCE N'EST FABRIQUÉE ICI.** Une station qui annonce « deux
 * millions d'auditeurs » n'a compté personne : elle multiplie une couverture
 * théorique par une population. Reprendre ce chiffre dans un rapport de
 * programme reviendrait à mentir avec les mots de quelqu'un d'autre.
 *
 * Ce qui est enregistré : les diffusions **attestées** — quelqu'un a signé que
 * l'émission est passée, à telle heure, sur telle station.
 *
 * Ce qui est mesuré : le **surcroît d'appels vocaux et de sessions USSD dans
 * les 48 heures qui suivent**, comparé à la moyenne des jours ordinaires. C'est
 * la seule mesure d'effet radio qui soit honnête, parce qu'elle compte des
 * gestes réels au lieu d'estimer des oreilles.
 *
 * Elle a ses limites, et il faut les dire : elle ne capte que ceux qui ont un
 * téléphone et qui rappellent. Elle sous-estime donc l'effet — ce qui vaut
 * mieux que l'inverse.
 */
class PiloteRadio extends PiloteFactice
{
    /** La fenêtre pendant laquelle on impute un appel à une diffusion. */
    private const FENETRE_HEURES = 48;

    public function canal(): Canal
    {
        return Canal::Radio;
    }

    /**
     * Une diffusion « aboutit » quand elle est attestée. Il n'y a rien d'autre
     * à mesurer sur ce canal : le reste se lit dans les autres.
     */
    protected function tauxDaboutissement(): float
    {
        return 1.0;
    }

    public function statistiques(Carbon $du, Carbon $au): array
    {
        $diffusions = Diffusion::where('canal', Canal::Radio->value)
            ->whereBetween('date', [$du, $au])
            ->orderBy('date')
            ->get();

        $attestees = $diffusions->whereNotNull('atteste_par');

        return [
            'canal' => Canal::Radio->value,
            'libelle' => Canal::Radio->libelle(),
            'factice' => $this->factice(),
            'diffusions' => $diffusions->count(),
            'volume' => $diffusions->count(),
            'unite' => Canal::Radio->unite(),
            'aboutis' => $attestees->count(),
            'aboutissement' => Canal::Radio->aboutissement(),
            'taux' => $diffusions->count() > 0
                ? round($attestees->count() / $diffusions->count() * 100, 1)
                : null,

            // Ce qui remplace l'audience.
            'audience' => null,
            'mesure' => 'surcroit_48h',
            'surcroit' => $this->surcroit($attestees, $du, $au),
        ];
    }

    /**
     * Le surcroît d'appels et de sessions dans les 48 heures suivant chaque
     * diffusion attestée, comparé à la moyenne des heures ordinaires.
     *
     * @param  \Illuminate\Support\Collection<int, Diffusion>  $attestees
     */
    public function surcroit($attestees, Carbon $du, Carbon $au): array
    {
        $reponses = Diffusion::whereIn('canal', [Canal::Ivr->value, Canal::Ussd->value])
            ->whereBetween('date', [$du, $au])
            ->get();

        if ($attestees->isEmpty() || $reponses->isEmpty()) {
            return [
                'mesurable' => false,
                // On le dit au lieu d'afficher zéro : « pas mesurable » et
                // « aucun effet » ne veulent pas dire la même chose.
                'raison' => $attestees->isEmpty()
                    ? 'Aucune diffusion attestée sur la période.'
                    : 'Aucun appel ni session sur la période.',
            ];
        }

        // Les fenêtres de 48 h ouvertes par une diffusion attestée.
        $fenetres = $attestees->map(fn (Diffusion $d) => [
            $d->date->copy(),
            $d->date->copy()->addHours(self::FENETRE_HEURES),
        ]);

        $dansUneFenetre = fn (Diffusion $r) => $fenetres->contains(
            fn (array $f) => $r->date->betweenIncluded($f[0], $f[1]),
        );

        $apres = $reponses->filter($dansUneFenetre);
        $ordinaire = $reponses->reject($dansUneFenetre);

        // On compare des MOYENNES HORAIRES : les fenêtres et les jours
        // ordinaires ne durent pas le même temps, et comparer des totaux
        // ferait passer une différence de durée pour un effet.
        $heuresApres = $attestees->count() * self::FENETRE_HEURES;
        $heuresTotal = max(1, $du->diffInHours($au));
        $heuresOrdinaires = max(1, $heuresTotal - $heuresApres);

        $parHeureApres = $apres->sum('volume') / max(1, $heuresApres);
        $parHeureOrdinaire = $ordinaire->sum('volume') / $heuresOrdinaires;

        return [
            'mesurable' => true,
            'fenetre_heures' => self::FENETRE_HEURES,
            'diffusions_attestees' => $attestees->count(),
            'volume_apres_diffusion' => $apres->sum('volume'),
            'volume_hors_fenetre' => $ordinaire->sum('volume'),
            'par_heure_apres' => round($parHeureApres, 2),
            'par_heure_ordinaire' => round($parHeureOrdinaire, 2),
            'surcroit_pourcent' => $parHeureOrdinaire > 0
                ? round(($parHeureApres - $parHeureOrdinaire) / $parHeureOrdinaire * 100, 1)
                : null,
            // La limite, écrite à côté du chiffre plutôt que dans une annexe.
            'limite' => 'Ne compte que ceux qui ont un téléphone et qui rappellent. '
                .'Sous-estime donc l\'effet réel.',
        ];
    }
}
