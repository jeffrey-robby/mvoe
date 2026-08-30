<?php

namespace App\Http\Controllers\Api\Superviseur;

use App\Http\Controllers\Controller;
use App\Models\Facilitateur;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Le registre des facilitateurs.
 *
 * Il répond à une question à laquelle personne ne sait répondre aujourd'hui :
 * combien de facilitateurs formés sont encore actifs ? Le statut n'est pas une
 * colonne en base, il se recalcule à chaque consultation à partir de la
 * dernière activité — un statut stocké se périmerait en silence.
 *
 * Le même écran sert les quatre niveaux : le MINPROFF y voit les 29
 * arrondissements, une délégation régionale les siens, un superviseur le sien.
 * Ce n'est pas quatre registres, c'est un registre et une portée.
 */
class RegistreController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $portee = $request->user()->portee();

        $facilitateurs = Facilitateur::query()
            // Le seul filtre de portée du système. Le paramètre de requête ne
            // peut que restreindre davantage, jamais élargir.
            ->dansLaPortee($portee)
            ->when($request->integer('arrondissement_id'),
                fn ($q, $id) => $q->where('arrondissement_id', $id))
            ->with('arrondissement.departement', 'progressions')
            ->withCount('seances')
            ->get()
            ->sortBy(fn (Facilitateur $f) => $f->arrondissement->libelle.$f->nom)
            ->values();

        return response()->json([
            'portee' => [
                'niveau' => $portee->niveau,
                'libelle' => $portee->libelle,
                'arrondissements' => $portee->arrondissements()?->count(),
            ],
            'synthese' => [
                'formes' => $facilitateurs->count(),
                'actifs' => $facilitateurs->filter->estActif()->count(),
                'inactifs' => $facilitateurs->reject->estActif()->count(),
                'jamais_actifs' => $facilitateurs->whereNull('derniere_activite')->count(),
                'modules_diffusables' => \App\Models\ModuleFormation::diffusables()->count(),
                'seuil_inactivite_jours' => config('mvoe.facilitateur.jours_inactivite'),
            ],
            'facilitateurs' => $facilitateurs->map(fn (Facilitateur $f) => [
                'id' => $f->id,
                'nom' => $f->nom,
                'telephone' => $f->telephone,
                'arrondissement' => $f->arrondissement->libelle,
                'departement' => $f->arrondissement->departement->libelle,
                'type_juridique' => $f->type_juridique->libelle(),
                'organisation_rattachement' => $f->organisation_rattachement,
                'date_formation_initiale' => $f->date_formation_initiale->toDateString(),
                'derniere_activite' => $f->derniere_activite?->toDateString(),
                'jours_depuis_activite' => $f->joursDepuisActivite(),
                'seances_animees' => $f->seances_count,
                'actif' => $f->estActif(),

                /*
                | Où il en est de sa formation.
                |
                | Ce n'est pas de la surveillance : c'est la seule façon de
                | repérer qui décroche avant qu'il ne disparaîsse du registre.
                | Un facilitateur qui rouvre ses modules est un facilitateur
                | qu'on peut encore rattraper.
                */
                'modules_ouverts' => $f->progressions->count(),
                'modules_termines' => $f->progressions->filter->estTermine()->count(),
                'derniere_formation' => $f->progressions
                    ->max('derniere_ouverture')?->toDateString(),
            ])->values()->all(),
        ]);
    }
}
