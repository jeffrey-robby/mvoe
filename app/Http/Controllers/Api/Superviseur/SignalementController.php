<?php

namespace App\Http\Controllers\Api\Superviseur;

use App\Enums\GraviteSignalement;
use App\Enums\StatutSignalement;
use App\Http\Controllers\Controller;
use App\Models\Signalement;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/**
 * La file des signalements.
 *
 * Le système ne notifie JAMAIS une autorité. Il n'y a pas de canal de sortie
 * dans ce contrôleur : un signalement arrive ici, dans la file d'un humain, et
 * c'est cet humain qui juge et décide. Une alerte automatique de maltraitance
 * ferait courir un risque à l'enfant qu'elle prétend protéger — elle prévient
 * avant que quiconque ait vérifié, et parfois elle prévient l'agresseur.
 *
 * Rien de ce qui sort d'ici ne porte l'identité d'un enfant, d'un parent ou
 * d'un foyer : il n'y a pas de colonne où en mettre une.
 */
class SignalementController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $portee = $request->user()->portee();

        $signalements = Signalement::dansLaPortee($portee)
            ->with('facilitateur:id,nom', 'arrondissement:id,libelle', 'superviseur:id,name')
            ->orderByRaw("FIELD(statut, 'soumis', 'examine', 'oriente', 'clos')")
            ->orderByDesc('gravite')
            ->orderBy('recue_a')
            ->get();

        return response()->json([
            'portee' => ['niveau' => $portee->niveau, 'libelle' => $portee->libelle],
            'synthese' => [
                'total' => $signalements->count(),
                // Ce qui attend quelqu'un. C'est le seul chiffre qui appelle
                // une action, et le seul qui doive sauter aux yeux.
                'a_traiter' => $signalements->filter->estOuvert()->count(),
                'graves_non_traites' => $signalements->filter->estOuvert()
                    ->where('gravite', GraviteSignalement::Elevee)->count(),
                'delai_moyen_traitement_jours' => $this->delaiMoyen($signalements),
            ],
            'signalements' => $signalements->map(fn (Signalement $s) => [
                'id' => $s->id,
                'uuid' => $s->uuid,
                'type' => $s->type->value,
                'type_libelle' => $s->type->libelle(),
                'gravite' => $s->gravite->value,
                'gravite_libelle' => $s->gravite->libelle(),
                'statut' => $s->statut->value,
                'statut_libelle' => $s->statut->libelle(),
                'ouvert' => $s->estOuvert(),
                'arrondissement' => $s->arrondissement->libelle,
                // Le facilitateur qui a signalé, parce que c'est avec lui que
                // le superviseur va en parler. Personne d'autre n'est nommé.
                'facilitateur' => $s->facilitateur->nom,
                'soumis_le' => $s->recue_a->toDateString(),
                'jours_attente' => $s->joursDattente(),
                'traite_par' => $s->superviseur?->name,
                'date_traitement' => $s->date_traitement?->toDateString(),
                'suite_donnee' => $s->suite_donnee,
            ])->values()->all(),
        ]);
    }

    /**
     * Traiter un signalement.
     *
     * `suite_donnee` est obligatoire dès qu'on ferme ou qu'on oriente : c'est
     * ce que le facilitateur lira, et c'est la seule raison pour laquelle il en
     * fera un deuxième. Un signalement sans retour est un signalement qu'on ne
     * refait pas — et le suivant, on le garde pour soi.
     */
    public function update(Request $request, Signalement $signalement): JsonResponse
    {
        $portee = $request->user()->portee();

        // Un superviseur ne traite que ce qui est dans sa portée. La règle est
        // vérifiée ici parce qu'un identifiant d'URL n'est pas une autorisation.
        abort_unless($portee->couvre($signalement->arrondissement_id), 403,
            'Ce signalement n\'est pas dans votre portée.');

        $donnees = $request->validate([
            'statut' => ['required', Rule::enum(StatutSignalement::class)],
            'suite_donnee' => [
                Rule::requiredIf(fn () => in_array(
                    $request->input('statut'),
                    [StatutSignalement::Oriente->value, StatutSignalement::Clos->value],
                    true,
                )),
                'nullable', 'string', 'max:2000',
            ],
        ]);

        $signalement->update([
            'statut' => StatutSignalement::from($donnees['statut']),
            'suite_donnee' => $donnees['suite_donnee'] ?? $signalement->suite_donnee,
            'traite_par_superviseur_id' => $request->user()->id,
            'date_traitement' => now(),
        ]);

        return response()->json([
            'signalement' => [
                'id' => $signalement->id,
                'statut' => $signalement->statut->value,
                'statut_libelle' => $signalement->statut->libelle(),
                'suite_donnee' => $signalement->suite_donnee,
                'date_traitement' => $signalement->date_traitement->toDateString(),
            ],
            // Dit explicitement, parce que c'est une décision d'architecture et
            // non un oubli : rien n'est parti vers personne.
            'aucune_notification_envoyee' => true,
        ]);
    }

    /** Combien de jours un signalement attend avant d'être regardé. */
    private function delaiMoyen($signalements): ?float
    {
        $delais = $signalements->filter(fn (Signalement $s) => $s->date_traitement !== null)
            ->map(fn (Signalement $s) => $s->joursDattente());

        return $delais->isEmpty() ? null : round($delais->avg(), 1);
    }
}
