<?php

namespace App\Services;

use App\Enums\StatutPresence;
use App\Models\Cohorte;
use App\Models\Facilitateur;
use App\Models\Presence;
use App\Models\Seance;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

/**
 * Le livrable du superviseur est un DOCUMENT trimestriel, pas un tableau de
 * bord temps réel. Ce service en calcule le contenu ; la mise en forme et
 * l'export viendront par-dessus.
 *
 * Quatre chiffres comptent :
 *   - la dose moyenne par parent : combien de séances un parent a réellement
 *     reçues, en comptant le rattrapage par binôme comme une séance reçue ;
 *   - l'écart déclaré/observé par facilitateur, qu'aucun formulaire papier ne
 *     peut produire ;
 *   - le délai de remontée, qui mesure la chaîne d'information elle-même ;
 *   - le nombre de facilitateurs formés encore actifs, aujourd'hui inconnu.
 */
class RapportTrimestriel
{
    /**
     * @param  ?string  $arrondissement  Périmètre de la délégation. `null` pour
     *                                   la délégation départementale, qui lit
     *                                   les huit arrondissements de la Mvila.
     */
    public function pour(int $annee, int $trimestre, ?string $arrondissement = null): array
    {
        $debut = Carbon::create($annee, ($trimestre - 1) * 3 + 1, 1)->startOfDay();
        $fin = $debut->copy()->addMonths(3)->subDay()->endOfDay();

        $seances = Seance::with('facilitateur', 'cohorte', 'module')
            ->whereBetween('date', [$debut->toDateString(), $fin->toDateString()])
            // Une délégation d'arrondissement ne lit que ses propres séances :
            // l'écart d'un facilitateur se lit avec lui, et son supérieur
            // direct est le seul à en avoir l'usage.
            ->when($arrondissement !== null,
                fn ($q) => $q->whereRelation('cohorte', 'arrondissement', $arrondissement))
            ->get();

        $facilitateurs = Facilitateur::query()
            ->when($arrondissement !== null, fn ($q) => $q->where('arrondissement', $arrondissement))
            ->get();

        return [
            'perimetre' => $arrondissement ?? 'Département de la Mvila',
            'periode' => [
                'annee' => $annee,
                'trimestre' => $trimestre,
                'du' => $debut->toDateString(),
                'au' => $fin->toDateString(),
            ],
            'synthese' => [
                'seances_tenues' => $seances->count(),
                'cohortes_actives' => $seances->pluck('cohorte_id')->unique()->count(),
                'facilitateurs_ayant_anime' => $seances->pluck('facilitateur_id')->unique()->count(),
                'facilitateurs_formes' => $facilitateurs->count(),
                'facilitateurs_actifs' => $facilitateurs->filter->estActif()->count(),
                'dose_moyenne_par_parent' => $this->doseMoyenneParParent($seances),
                'delai_moyen_remontee_jours' => $this->delaiMoyen($seances),
                'ecarts_total' => $seances->sum(fn (Seance $s) => $s->nombreEcarts()),
            ],
            'cohortes' => $this->cohortes($seances),
            'facilitateurs' => $this->facilitateurs($seances),
        ];
    }

    /**
     * Nombre moyen de séances effectivement reçues par parent inscrit.
     * Un parent rattrapé par son binôme a reçu la séance : il compte.
     */
    private function doseMoyenneParParent(Collection $seances): ?float
    {
        $parents = Cohorte::whereIn('id', $seances->pluck('cohorte_id')->unique())
            ->withCount('parents')
            ->get()
            ->sum('parents_count');

        if ($parents === 0) {
            return null;
        }

        $recues = Presence::whereIn('seance_id', $seances->pluck('id'))
            ->whereIn('statut', [StatutPresence::Present->value, StatutPresence::RattrapeBinome->value])
            ->count();

        return round($recues / $parents, 2);
    }

    private function delaiMoyen(Collection $seances): ?float
    {
        $delais = $seances->map(fn (Seance $s) => $s->delaiRemonteeJours())->filter(fn ($d) => $d !== null);

        return $delais->isEmpty() ? null : round($delais->avg(), 1);
    }

    private function cohortes(Collection $seances): array
    {
        return Cohorte::whereIn('id', $seances->pluck('cohorte_id')->unique())
            ->withCount('parents')
            ->get()
            ->map(fn (Cohorte $c) => [
                'libelle' => $c->libelle,
                'arrondissement' => $c->arrondissement,
                'ratio_max' => $c->ratio_max,
                'effectif' => $c->parents_count,
                'places_restantes' => $c->placesRestantes(),
                'seances_tenues' => $seances->where('cohorte_id', $c->id)->count(),
            ])->values()->all();
    }

    /**
     * C'est le tableau que le jury doit lire : pour chaque facilitateur, ce
     * qu'il a déclaré, ce que l'outil a observé, et l'écart entre les deux.
     */
    private function facilitateurs(Collection $seances): array
    {
        return $seances->groupBy('facilitateur_id')
            ->map(function (Collection $lot) {
                $facilitateur = $lot->first()->facilitateur;
                $lignes = $lot->flatMap(fn (Seance $s) => $s->ecarts());

                $declarees = $lignes->where('declaree', true)->count();
                $ecarts = $lignes->whereNotNull('ecart');

                return [
                    'nom' => $facilitateur->nom,
                    'arrondissement' => $facilitateur->arrondissement,
                    'seances' => $lot->count(),
                    'sequences_declarees_realisees' => $declarees,
                    'ecarts' => $ecarts->count(),
                    'declarees_jamais_ouvertes' => $ecarts->where('ecart', 'declaree_non_observee')->count(),
                    'ouvertes_declarees_non_faites' => $ecarts->where('ecart', 'observee_non_declaree')->count(),
                    'delai_moyen_remontee_jours' => $this->delaiMoyen($lot),
                ];
            })->values()->all();
    }
}
