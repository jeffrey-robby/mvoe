<?php

namespace App\Services;

use App\Models\Activite;
use App\Models\Arrondissement;
use App\Models\Cohorte;
use App\Models\Departement;
use App\Models\Facilitateur;
use App\Models\Foyer;
use App\Models\GroupeSoutien;
use App\Models\Region;
use App\Models\Seance;
use App\Models\Signalement;
use App\Support\Portee;
use Illuminate\Support\Collection;

/**
 * Le tableau de bord. Un seul, pour les cinq niveaux.
 *
 * C'est le point d'ingénierie central du brief : ne pas construire cinq
 * tableaux de bord, en construire un et le filtrer. Les mêmes indicateurs
 * remontent en s'agrégeant, et un délégué régional voit ses quatre départements
 * exactement comme le ministère voit ses dix régions.
 *
 * Deux choses seulement changent avec le niveau :
 *
 *   1. **Ce qu'on lit** — la portée, appliquée par `dansLaPortee()`, jamais
 *      écrite ici. Un manquement serait une fuite entre régions.
 *   2. **Comment on le découpe** — `Portee::sousNiveau()` : le national se lit
 *      en régions, une région en départements, un arrondissement en
 *      facilitateurs.
 *
 * Les indicateurs, eux, sont calculés par une seule fonction, appliquée au
 * total puis à chaque ligne du découpage. C'est ce qui garantit que la somme
 * des enfants fasse le parent : ce n'est pas une convention, c'est le même code.
 */
class TableauDeBord
{
    /** Les données de la portée, chargées une fois puis regroupées en mémoire. */
    private Collection $facilitateurs;

    private Collection $cohortes;

    private Collection $seances;

    private Collection $activites;

    private Collection $foyers;

    private Collection $groupes;

    private Collection $signalements;

    public function pour(Portee $portee): array
    {
        $this->charger($portee);

        return [
            'portee' => [
                'niveau' => $portee->niveau,
                'libelle' => $portee->libelle,
                'arrondissements' => $portee->arrondissements()?->count()
                    ?? Arrondissement::count(),
            ],
            'indicateurs' => $this->indicateurs(
                $this->facilitateurs, $this->cohortes, $this->seances,
                $this->activites, $this->foyers, $this->groupes, $this->signalements,
            ),
            'decoupage' => $this->decoupage($portee),
            'seuil_inactivite_jours' => config('mvoe.facilitateur.jours_inactivite'),
        ];
    }

    /**
     * Un seul passage en base, quel que soit le niveau.
     *
     * Le découpage se fait ensuite en mémoire : dix régions ne valent pas dix
     * fois les mêmes requêtes.
     */
    private function charger(Portee $portee): void
    {
        $this->facilitateurs = Facilitateur::dansLaPortee($portee)
            ->when($portee->estFacilitateur(),
                fn ($q) => $q->whereKey($portee->facilitateurId))
            ->with('arrondissement', 'progressions')
            ->get();

        $this->cohortes = Cohorte::dansLaPortee($portee)
            ->when($portee->estFacilitateur(),
                fn ($q) => $q->where('facilitateur_id', $portee->facilitateurId))
            ->with('arrondissement')
            ->withCount('parents')
            ->get();

        // Les relations dont dépend le calcul de l'écart sont chargées d'avance :
        // sans cela, chaque séance coûterait trois requêtes, et un tableau de
        // bord national en coûterait des milliers.
        $this->seances = Seance::dansLaPortee($portee)
            ->when($portee->estFacilitateur(),
                fn ($q) => $q->where('facilitateur_id', $portee->facilitateurId))
            ->with(['cohorte.arrondissement', 'presences', 'sequencesOuvertes',
                'fichesFidelite', 'module.sequences'])
            ->get();

        // Le travail de terrain. Ces quatre modèles portent leur arrondissement
        // directement : ils passent donc par le même filtre de portée, sans
        // relais et sans condition écrite à la main.
        $sien = fn ($q) => $q->when($portee->estFacilitateur(),
            fn ($r) => $r->where('facilitateur_id', $portee->facilitateurId));

        $this->activites = Activite::dansLaPortee($portee)->tap($sien)->get();
        $this->foyers = Foyer::dansLaPortee($portee)->tap($sien)->get();
        $this->groupes = GroupeSoutien::dansLaPortee($portee)->tap($sien)->get();
        $this->signalements = Signalement::dansLaPortee($portee)->tap($sien)->get();
    }

    /**
     * Les indicateurs. La même fonction pour le pays entier et pour une ligne
     * du découpage — c'est ce qui rend l'agrégation vraie par construction.
     */
    private function indicateurs(
        Collection $facilitateurs,
        Collection $cohortes,
        Collection $seances,
        Collection $activites,
        Collection $foyers,
        Collection $groupes,
        Collection $signalements,
    ): array {
        $parents = $cohortes->sum('parents_count');
        $touches = $seances->sum(fn (Seance $s) => $s->nombreTouches());
        $delais = $seances->map(fn (Seance $s) => $s->delaiRemonteeJours())
            ->filter(fn (?int $d) => $d !== null);

        return [
            'facilitateurs_formes' => $facilitateurs->count(),
            'facilitateurs_actifs' => $facilitateurs->filter->estActif()->count(),
            'facilitateurs_jamais_actifs' => $facilitateurs->whereNull('derniere_activite')->count(),
            'cohortes' => $cohortes->count(),
            'parents_inscrits' => $parents,
            'seances_tenues' => $seances->count(),

            // La dose : combien de séances un parent a réellement reçues. Un
            // parent rattrapé par son binôme a reçu la séance, il compte.
            'dose_moyenne_par_parent' => $parents > 0 ? round($touches / $parents, 2) : null,

            'ecarts_releves' => $seances->sum(fn (Seance $s) => $s->nombreEcarts()),
            'delai_moyen_remontee_jours' => $delais->isEmpty() ? null : round($delais->avg(), 1),

            /*
            | Le terrain. Sans ces lignes, un tableau de bord ne montrerait que
            | les séances de cohorte, et l'on conclurait qu'un facilitateur qui
            | fait des causeries et du porte-à-porte ne fait rien.
            */
            'activites' => $activites->count(),
            'parents_touches' => $activites->sum('nb_parents_touches'),
            'dont_hommes' => $activites->sum('nb_hommes'),
            'dont_femmes' => $activites->sum('nb_femmes'),
            // Le chiffre qui rend le critère « handicap » mesurable plutôt que
            // déclaratif : on ne l'écrit pas dans un rapport, on le compte.
            'participants_handicap' => $activites->sum('nb_participants_handicap'),

            'foyers_suivis' => $foyers->count(),
            'foyers_avec_difficulte' => $foyers
                ->filter(fn (Foyer $f) => $f->difficultes()->isNotEmpty())->count(),

            'groupes_soutien' => $groupes->count(),
            // La continuité du dossier : un groupe sans réunion depuis des mois
            // n'est pas un groupe, c'est une ligne dans un rapport.
            'groupes_actifs' => $groupes->filter->estActif()->count(),

            'signalements' => $signalements->count(),
            'signalements_a_traiter' => $signalements->filter->estOuvert()->count(),

            /*
            | La formation continue.
            |
            | Un facilitateur qui rouvre ses modules rouvre l'application, donc
            | il reste actif. C'est le seul dispositif de réactivation qui ne
            | coûte ni déplacement, ni per diem, ni convocation — encore
            | faut-il pouvoir en mesurer l'usage.
            */
            'modules_formation_ouverts' => $facilitateurs
                ->sum(fn (Facilitateur $f) => $f->progressions->count()),
            'facilitateurs_en_formation' => $facilitateurs
                ->filter(fn (Facilitateur $f) => $f->progressions->isNotEmpty())->count(),
        ];
    }

    /**
     * Le niveau en dessous, ligne à ligne.
     *
     * C'est la descente que le brief demande : un délégué régional voit ses
     * quatre départements et peut ouvrir l'un d'eux. Chaque ligne porte les
     * mêmes indicateurs que le total, calculés par le même code.
     */
    private function decoupage(Portee $portee): ?array
    {
        $sousNiveau = $portee->sousNiveau();

        if ($sousNiveau === null) {
            return null;
        }

        return [
            'niveau' => $sousNiveau,
            'libelle' => match ($sousNiveau) {
                'region' => 'Régions',
                'departement' => 'Départements',
                'arrondissement' => 'Arrondissements',
                'facilitateur' => 'Facilitateurs',
            },
            'lignes' => match ($sousNiveau) {
                'region' => $this->parRegion(),
                'departement' => $this->parDepartement($portee),
                'arrondissement' => $this->parArrondissement($portee),
                'facilitateur' => $this->parFacilitateur(),
            },
        ];
    }

    /**
     * Les dix régions. Neuf ne sont pas peuplées, et l'interface le dit
     * plutôt que de les taire : le système est national par construction, et
     * une région à zéro n'est pas une région absente.
     */
    private function parRegion(): array
    {
        $regions = Region::orderByDesc('peuplee')->orderBy('libelle')->get();

        return $regions->map(fn (Region $region) => [
            'id' => $region->id,
            'libelle' => $region->libelle,
            'peuplee' => (bool) $region->peuplee,
            ...$this->pourLesArrondissements(
                Arrondissement::where('region_id', $region->id)->pluck('id'),
            ),
        ])->all();
    }

    private function parDepartement(Portee $portee): array
    {
        return Departement::where('region_id', $portee->regionId)
            ->orderBy('libelle')
            ->get()
            ->map(fn (Departement $departement) => [
                'id' => $departement->id,
                'libelle' => $departement->libelle,
                'peuplee' => true,
                ...$this->pourLesArrondissements(
                    Arrondissement::where('departement_id', $departement->id)->pluck('id'),
                ),
            ])->all();
    }

    private function parArrondissement(Portee $portee): array
    {
        return Arrondissement::where('departement_id', $portee->departementId)
            ->orderBy('libelle')
            ->get()
            ->map(fn (Arrondissement $arrondissement) => [
                'id' => $arrondissement->id,
                'libelle' => $arrondissement->libelle,
                'peuplee' => true,
                ...$this->pourLesArrondissements(collect([$arrondissement->id])),
            ])->all();
    }

    /**
     * Le dernier échelon : les facilitateurs d'un arrondissement.
     *
     * Ici la ligne cesse d'être un territoire et devient une personne. C'est le
     * bas de la descente : au-delà, c'est le registre qui prend le relais.
     */
    private function parFacilitateur(): array
    {
        return $this->facilitateurs
            ->sortBy('nom')
            ->map(function (Facilitateur $facilitateur) {
                $cohortes = $this->cohortes->where('facilitateur_id', $facilitateur->id);
                $seances = $this->seances->where('facilitateur_id', $facilitateur->id);

                return [
                    'id' => $facilitateur->id,
                    'libelle' => $facilitateur->nom,
                    'peuplee' => true,
                    'actif' => $facilitateur->estActif(),
                    'jours_depuis_activite' => $facilitateur->joursDepuisActivite(),
                    ...$this->indicateurs(
                        collect([$facilitateur]), $cohortes, $seances,
                        $this->activites->where('facilitateur_id', $facilitateur->id),
                        $this->foyers->where('facilitateur_id', $facilitateur->id),
                        $this->groupes->where('facilitateur_id', $facilitateur->id),
                        $this->signalements->where('facilitateur_id', $facilitateur->id),
                    ),
                ];
            })->values()->all();
    }

    /**
     * Les indicateurs d'un sous-ensemble d'arrondissements, découpés dans ce
     * qui a déjà été chargé. Aucune requête supplémentaire : c'est ce qui rend
     * un découpage en dix régions aussi coûteux qu'un total.
     *
     * @param  Collection<int, int>  $arrondissements
     */
    private function pourLesArrondissements(Collection $arrondissements): array
    {
        $dedans = fn ($modele) => $arrondissements->contains(
            $modele->arrondissement_id ?? $modele->cohorte?->arrondissement_id,
        );

        return $this->indicateurs(
            $this->facilitateurs->filter($dedans),
            $this->cohortes->filter($dedans),
            $this->seances->filter($dedans),
            $this->activites->filter($dedans),
            $this->foyers->filter($dedans),
            $this->groupes->filter($dedans),
            $this->signalements->filter($dedans),
        );
    }
}
