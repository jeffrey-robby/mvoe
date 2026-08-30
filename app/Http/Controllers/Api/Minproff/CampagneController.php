<?php

namespace App\Http\Controllers\Api\Minproff;

use App\Enums\StatutCampagne;
use App\Http\Controllers\Controller;
use App\Models\Campagne;
use App\Models\CampagneAffectation;
use App\Models\Langue;
use App\Models\ModuleFormation;
use App\Models\Region;
use App\Services\DeclenchementCampagne;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Les campagnes, côté ministère.
 *
 * Déclencher une campagne crée **toutes** les affectations d'un coup, à tous
 * les niveaux. Il n'y a pas de propagation asynchrone : dans la vraie vie, la
 * cascade administrative n'est pas un processus, c'est un fait. Le ministère
 * décide, et tous les échelons sont concernés au même instant.
 *
 * Ce qui avance dans le temps, c'est la **prise de connaissance** de chaque
 * échelon — et c'est cela que l'écran montre.
 */
class CampagneController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $portee = $request->user()->portee();

        $campagnes = Campagne::with('affectations', 'auteur:id,name')
            ->orderByDesc('date_debut')
            ->get();

        // Une délégation ne voit que les campagnes qui la concernent : celles
        // dont une affectation touche l'un de ses arrondissements.
        if (! $portee->estNationale()) {
            $siens = $portee->arrondissements() ?? collect();

            $campagnes = $campagnes->filter(
                fn (Campagne $c) => $c->affectations
                    ->where('niveau', 'arrondissement')
                    ->pluck('entite_id')
                    ->intersect($siens)
                    ->isNotEmpty(),
            )->values();
        }

        return response()->json([
            'portee' => ['niveau' => $portee->niveau, 'libelle' => $portee->libelle],
            'peut_creer' => $portee->estNationale(),
            'campagnes' => $campagnes->map(fn (Campagne $c) => $this->rendre($c))->all(),

            // De quoi composer une campagne : seuls les contenus DIFFUSABLES
            // sont proposés. On ne lance pas une campagne sur un brouillon.
            'modules_disponibles' => ModuleFormation::diffusables()->get()
                ->map(fn (ModuleFormation $m) => [
                    'id' => $m->id, 'code' => $m->code, 'titre' => $m->titre,
                ])->all(),
            'langues_disponibles' => Langue::actives()->get()
                ->map(fn (Langue $l) => ['id' => $l->id, 'code' => $l->code, 'nom' => $l->nom()])
                ->all(),
            'regions' => Region::orderByDesc('peuplee')->orderBy('libelle')->get()
                ->map(fn (Region $r) => [
                    'id' => $r->id, 'libelle' => $r->libelle, 'peuplee' => (bool) $r->peuplee,
                ])->all(),
        ]);
    }

    public function store(Request $request, DeclenchementCampagne $declenchement): JsonResponse
    {
        abort_unless($request->user()->niveau === 'national', 403,
            'Seul le ministère déclenche une campagne.');

        $donnees = $request->validate([
            'titre' => ['required', 'string', 'max:160'],
            'objet' => ['nullable', 'string', 'max:1000'],
            'module_ids' => ['required', 'array', 'min:1'],
            'module_ids.*' => ['integer', 'exists:modules_formation,id'],
            'langue_ids' => ['required', 'array', 'min:1'],
            'langue_ids.*' => ['integer', 'exists:langues,id'],
            'region_ids' => ['required', 'array', 'min:1'],
            'region_ids.*' => ['integer', 'exists:regions,id'],
            'date_debut' => ['required', 'date'],
            'date_fin' => ['required', 'date', 'after_or_equal:date_debut'],
        ]);

        // Un module non diffusable ne part pas en campagne. Le contrôle est
        // ici, pas seulement dans l'écran : une requête directe le contournerait.
        $diffusables = ModuleFormation::diffusables()->pluck('id');

        abort_if(collect($donnees['module_ids'])->diff($diffusables)->isNotEmpty(), 422,
            'Un module non validé ne peut pas partir en campagne.');

        $campagne = Campagne::create([
            'titre' => $donnees['titre'],
            'objet' => $donnees['objet'] ?? null,
            'module_ids' => $donnees['module_ids'],
            'langue_ids' => $donnees['langue_ids'],
            'date_debut' => $donnees['date_debut'],
            'date_fin' => $donnees['date_fin'],
            'statut' => StatutCampagne::Brouillon,
            'creee_par_id' => $request->user()->id,
        ]);

        $affectations = $declenchement->declencher($campagne, $donnees['region_ids']);

        return response()->json([
            'campagne' => $this->rendre($campagne->fresh('affectations')),
            'affectations_creees' => $affectations,
        ], 201);
    }

    /**
     * Un échelon prend connaissance de la campagne.
     *
     * Geste manuel, et il le reste : cocher automatiquement à l'ouverture d'un
     * écran ferait passer une consultation pour une décision.
     */
    public function accuser(Request $request, Campagne $campagne, DeclenchementCampagne $declenchement): JsonResponse
    {
        $portee = $request->user()->portee();

        abort_if($portee->estNationale(), 422,
            'Le ministère déclenche les campagnes, il ne les reçoit pas.');

        $entiteId = match ($portee->niveau) {
            'region' => $portee->regionId,
            'departement' => $portee->departementId,
            'arrondissement' => $portee->arrondissementId,
        };

        $marquee = $declenchement->marquerRecue($campagne, $portee->niveau, $entiteId);

        return response()->json([
            'recue' => $marquee,
            'campagne' => $this->rendre($campagne->fresh('affectations')),
        ]);
    }

    private function rendre(Campagne $campagne): array
    {
        return [
            'id' => $campagne->id,
            'titre' => $campagne->titre,
            'objet' => $campagne->objet,
            'statut' => $campagne->statut->value,
            'statut_libelle' => $campagne->statut->libelle(),
            'date_debut' => $campagne->date_debut->toDateString(),
            'date_fin' => $campagne->date_fin->toDateString(),
            'modules' => ModuleFormation::whereIn('id', $campagne->module_ids)
                ->pluck('titre')->all(),
            'langues' => Langue::whereIn('id', $campagne->langue_ids)
                ->get()->map(fn (Langue $l) => $l->nom())->all(),
            'regions' => $campagne->affectations->where('niveau', 'region')
                ->map(fn (CampagneAffectation $a) => $a->libelleEntite())
                ->filter()->values()->all(),

            // L'avancement de la cascade : combien d'échelons ont pris
            // connaissance, à chaque niveau. Ce n'est PAS un pourcentage
            // d'exécution du programme.
            'avancement' => $campagne->avancement(),
            'creee_par' => $campagne->auteur?->name,
        ];
    }
}
