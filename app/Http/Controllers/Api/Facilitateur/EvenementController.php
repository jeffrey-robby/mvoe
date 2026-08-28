<?php

namespace App\Http\Controllers\Api\Facilitateur;

use App\Http\Controllers\Controller;
use App\Models\Seance;
use App\Services\ReceptionEvenements;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class EvenementController extends Controller
{
    /**
     * La remontée. Le kit envoie sa file d'événements horodatés ; le serveur
     * ignore ceux qu'il a déjà reçus et répond, UUID par UUID, ce qu'il a fait.
     *
     * La réponse est ce qui permet au client de vider sa file en toute
     * sécurité : il ne supprime un événement local que s'il figure dans
     * `acceptes` ou dans `doublons`. Un envoi coupé au milieu peut donc être
     * rejoué entier sans rien dupliquer.
     */
    public function store(Request $request, ReceptionEvenements $reception): JsonResponse
    {
        $donnees = $request->validate([
            'evenements' => ['required', 'array', 'min:1', 'max:500'],
            'evenements.*.uuid' => ['required', 'uuid'],
            'evenements.*.type' => ['required', Rule::in(ReceptionEvenements::TYPES)],
            'evenements.*.seance_uuid' => ['nullable', 'uuid'],
            'evenements.*.emis_a' => ['required', 'date'],
            'evenements.*.charge' => ['required', 'array'],
        ]);

        $bilan = $reception->recevoir($donnees['evenements'], $request->user());

        return response()->json($bilan, 202);
    }

    /**
     * Relecture d'une séance remontée, par son UUID client. Sert au kit à
     * confirmer ce que le serveur a réellement enregistré, et au facilitateur
     * à revoir l'écart de sa propre séance.
     */
    public function show(Request $request, string $uuid): JsonResponse
    {
        $seance = Seance::where('uuid', $uuid)->firstOrFail();

        abort_unless($seance->facilitateur_id === $request->user()->id, 403);

        return response()->json([
            'uuid' => $seance->uuid,
            'date' => $seance->date->toDateString(),
            'module' => ['numero' => $seance->module->numero, 'titre' => $seance->module->titre],
            'recue_a' => $seance->recue_a?->toIso8601String(),
            'delai_remontee_jours' => $seance->delaiRemonteeJours(),
            'presences' => $seance->presences()
                ->with('parent:id,code_parent')
                ->get()
                ->map(fn ($p) => ['code_parent' => $p->parent->code_parent, 'statut' => $p->statut->value])
                ->all(),
            'ecarts' => $seance->ecarts()->map(fn (array $l) => [
                'sequence' => ['id' => $l['sequence']->id, 'ordre' => $l['sequence']->ordre, 'titre' => $l['sequence']->titre],
                'declaree' => $l['declaree'],
                'observee' => $l['observee'],
                'ecart' => $l['ecart'],
            ])->all(),
        ]);
    }
}
