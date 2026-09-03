<?php

namespace App\Http\Controllers\Api\Minproff;

use App\Enums\Modalite;
use App\Enums\StatutValidation;
use App\Enums\TypeFormation;
use App\Http\Controllers\Controller;
use App\Models\Langue;
use App\Models\Module;
use App\Models\ModuleFormation;
use App\Models\Realisation;
use App\Models\Sequence;
use App\Models\UniteDigitale;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/**
 * L'écriture des contenus, côté ministère.
 *
 * La bibliothèque savait valider et retirer ; elle ne savait pas écrire. Le
 * catalogue venait donc entièrement des seeders, ce qui revient à dire que
 * l'équipe technique produisait le curriculum national. Cette prérogative
 * appartient au MINPROFF, et elle s'exerce ici.
 *
 * **Rien ne naît validé.** Créer et valider sont deux gestes, et souvent deux
 * personnes : celui qui rédige une unité n'est pas celui qui engage le
 * programme national en la diffusant. Tout ce que ce contrôleur crée entre en
 * `brouillon` et ressort par la file de validation de la bibliothèque.
 *
 * Réservé au national, pour la même raison que la validation : une délégation
 * qui rédigerait ses propres unités produirait dix curriculums différents.
 */
class RedactionController extends Controller
{
    /**
     * Ce dont un formulaire de saisie a besoin pour ne rien inventer.
     *
     * Les modules, les séquences et les langues viennent de la BASE. Une liste
     * écrite dans le JavaScript voudrait dire que l'équipe technique décide de
     * la structure du curriculum.
     */
    public function referentiel(Request $request): JsonResponse
    {
        $this->exigerLeNational($request);

        return response()->json([
            // Le curriculum parent : où une nouvelle unité peut se rattacher.
            'modules' => Module::ordonnes()
                ->with(['sequences' => fn ($q) => $q->ordonnees()])
                ->get()
                ->map(fn (Module $m) => [
                    'id' => $m->id,
                    'numero' => $m->numero,
                    'titre' => $m->titre,
                    // Un module sans séquence ne peut pas encore accueillir
                    // d'unité : l'interface le dit, plutôt que de proposer une
                    // liste vide sans expliquer pourquoi.
                    'sequences' => $m->sequences->map(fn (Sequence $s) => [
                        'id' => $s->id,
                        'ordre' => $s->ordre,
                        'titre' => $s->titre,
                        'est_brise_glace' => $s->est_brise_glace,
                    ])->all(),
                ])->all(),

            // Seules les langues ACTIVES. Charger une réalisation dans une
            // langue retirée de l'interface produirait un contenu que personne
            // ne peut atteindre.
            'langues' => Langue::actives()->get()->map(fn (Langue $l) => [
                'id' => $l->id,
                'code' => $l->code,
                'nom' => $l->nom(),
            ])->all(),

            'modalites' => collect(Modalite::cases())->map(fn (Modalite $m) => [
                'valeur' => $m->value,
                'libelle' => $m === Modalite::Audio ? 'Audio' : 'Texte + pictogrammes',
            ])->all(),

            'types_formation' => collect(TypeFormation::cases())->map(fn (TypeFormation $t) => [
                'valeur' => $t->value,
                'libelle' => $t->libelle(),
                'description' => $t->description(),
            ])->all(),

            // Le prochain rang libre, proposé au rédacteur. Il reste
            // modifiable : c'est le ministère qui décide de la séquence.
            'ordre_suivant' => (int) ModuleFormation::max('ordre') + 1,
        ]);
    }

    /* --- Le catalogue destiné au FACILITATEUR ----------------------------- */

    /**
     * Enregistrer un module de formation.
     *
     * Il naît sans section et en brouillon : un module vide n'atteint personne,
     * et c'est exactement ce qu'on veut d'un contenu qu'on vient d'ouvrir.
     */
    public function moduleFormation(Request $request): JsonResponse
    {
        $this->exigerLeNational($request);

        $donnees = $request->validate([
            'code' => ['required', 'string', 'max:40', 'unique:modules_formation,code'],
            'titre' => ['required', 'string', 'max:160'],
            'type' => ['required', Rule::enum(TypeFormation::class)],
            'objectif' => ['required', 'string', 'max:500'],
            'ordre' => ['nullable', 'integer', 'min:1', 'max:999'],
            'duree_minutes' => ['required', 'integer', 'min:1', 'max:600'],
        ]);

        $module = ModuleFormation::create([
            ...$donnees,
            'ordre' => $donnees['ordre'] ?? (int) ModuleFormation::max('ordre') + 1,
            'statut_validation' => StatutValidation::Brouillon,
        ]);

        return response()->json(['module' => $this->rendreLeModule($module)], 201);
    }

    /**
     * Ajouter une section à un module de formation.
     *
     * L'audio est un CHEMIN, pas un téléversement : les enregistrements sont
     * produits en studio, pas depuis un formulaire web. Il reste facultatif, et
     * le lecteur du facilitateur bascule sur le texte quand il manque.
     */
    public function sectionFormation(Request $request, string $code): JsonResponse
    {
        $this->exigerLeNational($request);

        $module = ModuleFormation::where('code', $code)->firstOrFail();

        $donnees = $request->validate([
            'titre' => ['required', 'string', 'max:160'],
            'contenu_texte' => ['required', 'string'],
            'fichier_audio' => ['nullable', 'string', 'max:255'],
            'duree_minutes' => ['required', 'integer', 'min:1', 'max:240'],
            'ordre' => ['nullable', 'integer', 'min:1', 'max:99'],
        ]);

        $section = $module->sections()->create([
            ...$donnees,
            'ordre' => $donnees['ordre'] ?? (int) $module->sections()->max('ordre') + 1,
        ]);

        /*
         * Modifier un module DIFFUSÉ le renvoie en brouillon.
         *
         * Sans cela, on ajouterait une section à un module déjà validé et elle
         * partirait sans relecture — la validation ne porterait plus que sur la
         * première version. Le ministère revalide, et c'est peu de travail.
         */
        if ($module->statut_validation->estDiffusable()) {
            $module->update(['statut_validation' => StatutValidation::Brouillon]);
        }

        return response()->json([
            'section' => [
                'id' => $section->id,
                'ordre' => $section->ordre,
                'titre' => $section->titre,
                'duree_minutes' => $section->duree_minutes,
                'audio_disponible' => $section->aUnAudio(),
            ],
            'module' => $this->rendreLeModule($module->fresh()),
        ], 201);
    }

    /* --- Le catalogue destiné aux PARENTS --------------------------------- */

    /**
     * Enregistrer une unité du curriculum parent.
     *
     * Le rattachement au module ET à la séquence est obligatoire des deux
     * côtés : sans lui, l'assistant ne peut pas citer sa référence, donc il ne
     * peut pas servir cette unité. Une unité orpheline est une unité morte.
     */
    public function unite(Request $request): JsonResponse
    {
        $this->exigerLeNational($request);

        $donnees = $request->validate([
            'module_id' => ['required', 'integer', 'exists:modules,id'],
            'sequence_id' => ['required', 'integer', 'exists:sequences,id'],
            // Le texte que l'assistant compare à la question du parent, puis
            // restitue MOT POUR MOT. Il est donc rédigé, pas résumé.
            'message_cle' => ['required', 'string', 'max:2000'],
        ]);

        $sequence = Sequence::findOrFail($donnees['sequence_id']);

        abort_unless($sequence->module_id === (int) $donnees['module_id'], 422,
            'Cette séquence appartient à un autre module.');

        $unite = UniteDigitale::create($donnees);

        return response()->json([
            'unite' => $this->rendreLUnite($unite->load('module', 'sequence', 'realisations.langue')),
        ], 201);
    }

    /**
     * Charger une réalisation : une unité, dans UNE langue, dans UNE modalité.
     *
     * C'est le geste qui rend une langue réellement disponible. Enregistrer la
     * langue dans la bibliothèque ne fait que la nommer ; tant qu'aucune
     * réalisation ne la porte, l'interface parent ne la proposera pas — et elle
     * a raison de ne pas la proposer.
     */
    public function realisation(Request $request, UniteDigitale $unite): JsonResponse
    {
        $this->exigerLeNational($request);

        $donnees = $request->validate([
            'langue_id' => ['required', 'integer', 'exists:langues,id'],
            'modalite' => ['required', Rule::enum(Modalite::class)],
            'titre' => ['nullable', 'string', 'max:160'],
            'contenu_texte' => ['nullable', 'string'],
            'fichier_audio' => ['nullable', 'string', 'max:255'],
            'pictogrammes' => ['nullable', 'array'],
            'pictogrammes.*' => ['string', 'max:60'],
        ]);

        $modalite = Modalite::from($donnees['modalite']);

        /*
         * Une réalisation qui ne porte NI texte NI audio ne dit rien. Elle
         * gonflerait le compteur de couverture en laissant croire que la langue
         * est servie, ce qui est pire que l'absence : personne n'irait la
         * chercher.
         */
        abort_if(
            blank($donnees['contenu_texte'] ?? null) && blank($donnees['fichier_audio'] ?? null),
            422,
            'Une réalisation porte un texte, un audio, ou les deux. Celle-ci ne porte rien.',
        );

        // L'unicité est en base ; on la dit ici en français plutôt que de
        // laisser remonter une erreur SQL.
        abort_if(
            $unite->realisations()
                ->where('langue_id', $donnees['langue_id'])
                ->where('modalite', $modalite->value)
                ->exists(),
            422,
            'Cette unité existe déjà dans cette langue et cette modalité.',
        );

        $realisation = $unite->realisations()->create([
            ...$donnees,
            'statut_validation' => StatutValidation::Brouillon,
        ]);

        return response()->json([
            'realisation' => $this->rendreLaRealisation($realisation->load('langue')),
            'unite' => $this->rendreLUnite(
                $unite->fresh()->load('module', 'sequence', 'realisations.langue'),
            ),
        ], 201);
    }

    /**
     * Valider ou renvoyer une réalisation.
     *
     * Le pendant exact de la validation des modules de formation. Sans lui, une
     * réalisation créée en brouillon n'aurait aucune sortie : elle resterait en
     * base sans jamais atteindre un parent, et personne ne saurait pourquoi.
     */
    public function validerLaRealisation(Request $request, Realisation $realisation): JsonResponse
    {
        $this->exigerLeNational($request);

        $donnees = $request->validate([
            'statut_validation' => ['required', Rule::enum(StatutValidation::class)],
        ]);

        $realisation->update($donnees);

        return response()->json([
            'realisation' => $this->rendreLaRealisation($realisation->fresh()->load('langue')),
        ]);
    }

    /* ---------------------------------------------------------------------- */

    private function rendreLeModule(ModuleFormation $module): array
    {
        return [
            'code' => $module->code,
            'titre' => $module->titre,
            'type' => $module->type->value,
            'type_libelle' => $module->type->libelle(),
            'objectif' => $module->objectif,
            'ordre' => $module->ordre,
            'duree_minutes' => $module->duree_minutes,
            'sections' => $module->sections()->count(),
            'statut_validation' => $module->statut_validation->value,
            'statut_libelle' => $module->statut_validation->libelle(),
            'diffusable' => $module->statut_validation->estDiffusable(),
        ];
    }

    private function rendreLUnite(UniteDigitale $unite): array
    {
        return [
            'id' => $unite->id,
            'message_cle' => $unite->message_cle,
            'reference' => $unite->reference(),
            'realisations' => $unite->realisations
                ->map(fn (Realisation $r) => $this->rendreLaRealisation($r))->all(),
        ];
    }

    private function rendreLaRealisation(Realisation $realisation): array
    {
        return [
            'id' => $realisation->id,
            'langue' => $realisation->langue?->nom(),
            'langue_code' => $realisation->langue?->code,
            'modalite' => $realisation->modalite->value,
            'titre' => $realisation->titre,
            'audio_disponible' => $realisation->aUnAudio(),
            'statut_validation' => $realisation->statut_validation->value,
            'statut_libelle' => $realisation->statut_validation->libelle(),
            'diffusable' => $realisation->estDiffusable(),
        ];
    }

    private function exigerLeNational(Request $request): void
    {
        abort_unless($request->user()->niveau === 'national', 403,
            'La rédaction des contenus est une prérogative du ministère.');
    }
}
