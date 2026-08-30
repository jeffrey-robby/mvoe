<?php

namespace App\Http\Controllers\Api\Facilitateur;

use App\Enums\DifficulteFonctionnelle;
use App\Enums\GraviteSignalement;
use App\Enums\TypeActivite;
use App\Enums\TypeSignalement;
use App\Models\Activite;
use App\Models\Foyer;
use App\Models\GroupeSoutien;
use App\Models\Signalement;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Ce que le facilitateur RELIT de son travail de terrain.
 *
 * Rien ne s'écrit ici : activités, foyers, visites, groupes et signalements
 * arrivent tous par la file d'événements, parce qu'ils se saisissent sans
 * réseau. Ce contrôleur ne sert qu'à relire ce qui est remonté, et surtout à
 * lire LA SUITE DONNÉE aux signalements.
 *
 * Cette dernière n'est pas un confort. Un signalement sans retour est un
 * signalement qu'on ne refait pas, et le suivant, le facilitateur le garde
 * pour lui.
 */
class TerrainController extends Controller
{
    /** Le vocabulaire du terrain, pour que le kit ne le code pas en dur. */
    public function referentiel(): JsonResponse
    {
        return response()->json([
            'types_activite' => $this->options(TypeActivite::cases()),
            'types_signalement' => $this->options(TypeSignalement::cases()),
            'gravites' => $this->options(GraviteSignalement::cases()),
            'difficultes_fonctionnelles' => $this->options(DifficulteFonctionnelle::cases()),
        ]);
    }

    public function index(Request $request): JsonResponse
    {
        $facilitateur = $request->user();

        $activites = Activite::where('facilitateur_id', $facilitateur->id)
            ->orderByDesc('date')
            ->get();

        return response()->json([
            'synthese' => [
                'activites' => $activites->count(),
                'parents_touches' => $activites->sum('nb_parents_touches'),
                'dont_hommes' => $activites->sum('nb_hommes'),
                'dont_femmes' => $activites->sum('nb_femmes'),
                // Le chiffre qui rend le critère mesurable plutôt que déclaratif.
                'dont_handicap' => $activites->sum('nb_participants_handicap'),
                'foyers_suivis' => Foyer::where('facilitateur_id', $facilitateur->id)->count(),
            ],
            'activites' => $activites->map(fn (Activite $a) => [
                'uuid' => $a->uuid,
                'type' => $a->type->value,
                'type_libelle' => $a->type->libelle(),
                'date' => $a->date->toDateString(),
                'lieu' => $a->lieu,
                'duree_minutes' => $a->duree_minutes,
                'nb_parents_touches' => $a->nb_parents_touches,
                'nb_hommes' => $a->nb_hommes,
                'nb_femmes' => $a->nb_femmes,
                'nb_participants_handicap' => $a->nb_participants_handicap,
                'sexe_non_renseigne' => $a->sexeNonRenseigne(),
            ])->values()->all(),
        ]);
    }

    /** Les foyers qu'il suit, sans un seul nom. */
    public function foyers(Request $request): JsonResponse
    {
        $foyers = Foyer::where('facilitateur_id', $request->user()->id)
            ->withCount('visites')
            ->with(['visites' => fn ($q) => $q->latest('date')->limit(1)])
            ->orderBy('localite')
            ->get();

        return response()->json([
            'foyers' => $foyers->map(fn (Foyer $f) => [
                'uuid' => $f->uuid,
                'localite' => $f->localite,
                'nb_adultes' => $f->nb_adultes,
                'nb_enfants' => $f->nb_enfants,
                'difficultes' => $f->difficultes()->map(fn ($d) => $d->libelle())->all(),
                'deja_suivi_programme' => $f->deja_suivi_programme,
                'visites' => $f->visites_count,
                'derniere_visite' => $f->visites->first()?->date->toDateString(),
                'suivi_prevu' => (bool) $f->visites->first()?->suivi_prevu,
            ])->values()->all(),
        ]);
    }

    public function groupes(Request $request): JsonResponse
    {
        $groupes = GroupeSoutien::where('facilitateur_id', $request->user()->id)
            ->withCount('membres')
            ->orderBy('libelle')
            ->get();

        return response()->json([
            'seuil_sans_reunion_jours' => config('mvoe.gsp.jours_sans_reunion'),
            'groupes' => $groupes->map(fn (GroupeSoutien $g) => [
                'uuid' => $g->uuid,
                'libelle' => $g->libelle,
                'taille' => $g->membres_count,
                'date_creation' => $g->date_creation->toDateString(),
                'derniere_reunion' => $g->derniere_reunion?->toDateString(),
                'jours_depuis_reunion' => $g->joursDepuisReunion(),
                'actif' => $g->estActif(),
            ])->values()->all(),
        ]);
    }

    /**
     * Ses signalements, ET la suite qui leur a été donnée.
     *
     * C'est l'écran qui décide si le facilitateur en fera un deuxième.
     */
    public function signalements(Request $request): JsonResponse
    {
        $signalements = Signalement::where('facilitateur_id', $request->user()->id)
            ->with('superviseur:id,name')
            ->orderByDesc('recue_a')
            ->get();

        return response()->json([
            'signalements' => $signalements->map(fn (Signalement $s) => [
                'uuid' => $s->uuid,
                'type_libelle' => $s->type->libelle(),
                'gravite_libelle' => $s->gravite->libelle(),
                'statut' => $s->statut->value,
                'statut_libelle' => $s->statut->libelle(),
                'ouvert' => $s->estOuvert(),
                'soumis_le' => $s->recue_a->toDateString(),
                'jours_attente' => $s->joursDattente(),
                'suite_donnee' => $s->suite_donnee,
                'date_traitement' => $s->date_traitement?->toDateString(),
            ])->values()->all(),
        ]);
    }

    /** @param array<int, \BackedEnum> $cas */
    private function options(array $cas): array
    {
        return array_map(
            fn ($c) => ['valeur' => $c->value, 'libelle' => $c->libelle()],
            $cas,
        );
    }
}
