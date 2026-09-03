<?php

namespace App\Http\Controllers\Api\Minproff;

use App\Enums\Modalite;
use App\Enums\StatutValidation;
use App\Http\Controllers\Controller;
use App\Models\Langue;
use App\Models\ModuleFormation;
use App\Models\Realisation;
use App\Models\UniteDigitale;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/**
 * La bibliothèque de contenus et la file de validation, côté ministère.
 *
 * **Un contenu non validé ne peut pas être diffusé.** Cet écran est l'autre
 * moitié de cette règle : il faut bien un endroit où quelqu'un valide, sans
 * quoi la règle bloquerait tout et serait contournée dans les six mois.
 *
 * Réservé au national. La validation d'un contenu est une prérogative du
 * ministère : une délégation qui validerait ses propres contenus produirait
 * dix curriculums différents, et le programme cesserait d'être national.
 */
class BibliothequeController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $this->exigerLeNational($request);

        $unites = UniteDigitale::with('realisations.langue', 'module')->get();
        $modules = ModuleFormation::with('sections')->orderBy('ordre')->get();

        return response()->json([
            'langues' => Langue::orderBy('ordre')->get()->map(fn (Langue $l) => [
                'id' => $l->id,
                'code' => $l->code,
                'libelle' => $l->libelle,
                'nom' => $l->nom(),
                'actif' => $l->actif,
                // Ce que cette langue porte réellement. Une langue enregistrée
                // sans aucune réalisation est une promesse, pas un service.
                'realisations' => Realisation::where('langue_id', $l->id)->count(),
            ])->all(),

            /*
            | Les deux catalogues, côte à côte. Le ministère produit les deux,
            | mais ils ne servent pas le même public : mélanger leurs
            | statistiques ferait croire qu'un module de formation touche des
            | parents.
            */
            'contenus_parents' => [
                'unites' => $unites->count(),
                'realisations' => Realisation::count(),
                'par_statut' => $this->parStatut(Realisation::query()),
                'couverture' => $this->couverture($unites),
            ],
            'contenus_facilitateurs' => [
                'modules' => $modules->count(),
                'diffusables' => $modules->filter(
                    fn (ModuleFormation $m) => $m->statut_validation->estDiffusable(),
                )->count(),
                'par_statut' => $this->parStatut(ModuleFormation::query()),
            ],

            // La file : ce qui attend une décision humaine. Les DEUX
            // catalogues y figurent — une réalisation créée en brouillon qui
            // n'apparaîtrait nulle part resterait en base sans jamais atteindre
            // un parent, et personne ne saurait pourquoi.
            'file_de_validation' => $this->file($modules),
            'realisations_en_attente' => $this->realisationsEnAttente(),
        ]);
    }

    /**
     * Valider ou renvoyer un module de formation.
     *
     * Renvoyer en brouillon retire immédiatement le module de tous les kits au
     * prochain paquet : c'est voulu. Un module qu'on découvre faux doit cesser
     * d'être lu, pas attendre la version suivante.
     */
    public function valider(Request $request, string $code): JsonResponse
    {
        $this->exigerLeNational($request);

        $donnees = $request->validate([
            'statut_validation' => ['required', Rule::enum(StatutValidation::class)],
        ]);

        $module = ModuleFormation::where('code', $code)->firstOrFail();

        $module->update(['statut_validation' => $donnees['statut_validation']]);

        return response()->json([
            'module' => [
                'code' => $module->code,
                'titre' => $module->titre,
                'statut_validation' => $module->statut_validation->value,
                'statut_libelle' => $module->statut_validation->libelle(),
                'diffusable' => $module->statut_validation->estDiffusable(),
            ],
        ]);
    }

    /** Enregistrer une langue, ou la retirer de l'interface. */
    public function langue(Request $request, ?int $id = null): JsonResponse
    {
        $this->exigerLeNational($request);

        $donnees = $request->validate([
            'code' => ['required_without:actif', 'nullable', 'string', 'max:20',
                Rule::unique('langues', 'code')->ignore($id)],
            'libelle' => ['required_without:actif', 'nullable', 'string', 'max:80'],
            'endonyme' => ['nullable', 'string', 'max:80'],
            'actif' => ['nullable', 'boolean'],
        ]);

        $langue = $id === null
            ? Langue::create([...$donnees, 'ordre' => Langue::max('ordre') + 1])
            : tap(Langue::findOrFail($id))->update(array_filter(
                $donnees, fn ($v) => $v !== null,
            ));

        return response()->json([
            'langue' => [
                'id' => $langue->id,
                'code' => $langue->code,
                'libelle' => $langue->libelle,
                'nom' => $langue->nom(),
                'actif' => $langue->actif,
            ],
            // Dit explicitement : désactiver ne détruit rien.
            'realisations_conservees' => Realisation::where('langue_id', $langue->id)->count(),
        ]);
    }

    /* ---------------------------------------------------------------------- */

    /**
     * Ce qui manque, langue par langue.
     *
     * C'est le seul chiffre qui dise au ministère où porter l'effort : une
     * unité chargée en français et pas en bulu n'atteint pas les locuteurs
     * bulu, quoi qu'en dise le nombre total de réalisations.
     */
    private function couverture($unites): array
    {
        return Langue::actives()->get()->map(function (Langue $langue) use ($unites) {
            $couvertes = $unites->filter(
                fn (UniteDigitale $u) => $u->realisations->contains('langue_id', $langue->id),
            )->count();

            return [
                'langue' => $langue->code,
                'nom' => $langue->nom(),
                'unites_couvertes' => $couvertes,
                'unites_total' => $unites->count(),
                'manquantes' => $unites->count() - $couvertes,
            ];
        })->values()->all();
    }

    private function parStatut($requete): array
    {
        return collect(StatutValidation::cases())
            ->mapWithKeys(fn (StatutValidation $s) => [
                $s->value => (clone $requete)->where('statut_validation', $s->value)->count(),
            ])->all();
    }

    private function file($modules): array
    {
        return $modules
            ->reject(fn (ModuleFormation $m) => $m->statut_validation->estDiffusable())
            ->map(fn (ModuleFormation $m) => [
                'code' => $m->code,
                'titre' => $m->titre,
                'type_libelle' => $m->type->libelle(),
                'objectif' => $m->objectif,
                'sections' => $m->sections->count(),
                'statut_validation' => $m->statut_validation->value,
                'statut_libelle' => $m->statut_validation->libelle(),
            ])->values()->all();
    }

    /**
     * Les réalisations qui attendent une relecture.
     *
     * Elles ne sont pas mêlées aux modules de formation : les deux catalogues
     * ne servent pas le même public, et une réalisation se relit dans SA
     * langue — souvent par quelqu'un d'autre que le relecteur des modules.
     */
    private function realisationsEnAttente(): array
    {
        return Realisation::with('langue', 'unite.module', 'unite.sequence')
            ->where('statut_validation', '!=', StatutValidation::Valide->value)
            ->get()
            ->map(fn (Realisation $r) => [
                'id' => $r->id,
                'langue' => $r->langue?->nom(),
                'langue_code' => $r->langue?->code,
                'modalite' => $r->modalite->value,
                'modalite_libelle' => $r->modalite === Modalite::Audio
                    ? 'Audio' : 'Texte + pictogrammes',
                'titre' => $r->titre,
                'reference' => $r->unite?->reference(),
                'message_cle' => $r->unite?->message_cle,
                // Dit au relecteur ce qu'il va réellement pouvoir écouter.
                'audio_disponible' => $r->aUnAudio(),
                'statut_validation' => $r->statut_validation->value,
                'statut_libelle' => $r->statut_validation->libelle(),
            ])->values()->all();
    }

    private function exigerLeNational(Request $request): void
    {
        abort_unless($request->user()->niveau === 'national', 403,
            'La bibliothèque et la validation sont des prérogatives du ministère.');
    }
}
