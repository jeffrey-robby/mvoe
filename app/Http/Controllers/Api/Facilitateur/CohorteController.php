<?php

namespace App\Http\Controllers\Api\Facilitateur;

use App\Http\Controllers\Controller;
use App\Models\Cohorte;
use App\Models\Module;
use App\Models\Seance;
use App\Services\PaquetCohorte;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CohorteController extends Controller
{
    /**
     * Écran d'accueil du facilitateur : ses cohortes et la prochaine séance.
     */
    public function index(Request $request): JsonResponse
    {
        $facilitateur = $request->user();

        $cohortes = Cohorte::where('facilitateur_id', $facilitateur->id)
            ->withCount('parents')
            ->with('curriculumVersion')
            ->get();

        return response()->json([
            'cohortes' => $cohortes->map(fn (Cohorte $c) => [
                'id' => $c->id,
                'libelle' => $c->libelle,
                'arrondissement' => $c->arrondissement,
                'effectif' => $c->parents_count,
                'ratio_max' => $c->ratio_max,
                'places_restantes' => $c->placesRestantes(),
                'date_debut' => $c->date_debut->toDateString(),
                'seances_tenues' => $c->seances()->count(),
                'prochaine_seance' => $this->prochaineSeance($c),
            ])->all(),
        ]);
    }

    /**
     * Le paquet de cohorte, téléchargé UNE fois. Ensuite tout fonctionne hors
     * ligne, sans exception.
     */
    public function paquet(Request $request, Cohorte $cohorte, PaquetCohorte $paquet): JsonResponse
    {
        abort_unless($cohorte->facilitateur_id === $request->user()->id, 403);

        return response()->json($paquet->pour($cohorte));
    }

    /**
     * Estimation, pas un calendrier : il n'existe pas de table de planning et
     * je n'en ai pas inventé une.
     *
     * Le programme se suit dans l'ordre : le prochain module est celui qui
     * suit le plus avancé de ceux déjà tenus. On ne renvoie donc pas la
     * cohorte à un module d'un rang inférieur qu'elle aurait sauté.
     */
    private function prochaineSeance(Cohorte $cohorte): ?array
    {
        $ordreAtteint = Module::whereIn('id', Seance::where('cohorte_id', $cohorte->id)->pluck('module_id'))
            ->max('ordre');

        $module = Module::where('curriculum_version_id', $cohorte->curriculum_version_id)
            ->when($ordreAtteint !== null, fn ($q) => $q->where('ordre', '>', $ordreAtteint))
            ->ordonnes()
            ->first();

        if ($module === null) {
            return null;
        }

        $derniere = Seance::where('cohorte_id', $cohorte->id)->max('date');

        return [
            'module' => [
                'id' => $module->id,
                'numero' => $module->numero,
                'titre' => $module->titre,
                'renseigne' => $module->estRenseigne(),
            ],
            'date_estimee' => $derniere
                ? \Illuminate\Support\Carbon::parse($derniere)->addWeek()->toDateString()
                : $cohorte->date_debut->toDateString(),
        ];
    }
}
