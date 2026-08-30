<?php

namespace App\Services;

use App\Enums\DifficulteFonctionnelle;
use App\Enums\GraviteSignalement;
use App\Enums\TypeActivite;
use App\Enums\TypeSignalement;
use App\Models\Binome;
use App\Models\Cohorte;
use App\Models\Foyer;
use App\Models\GroupeSoutien;
use App\Models\ModuleFormation;
use App\Models\ProgressionFormation;
use App\Models\Module;
use App\Models\Realisation;
use App\Models\SectionFormation;
use App\Models\Sequence;
use App\Models\UniteDigitale;
use Illuminate\Support\Carbon;

/**
 * Le « paquet de cohorte » : tout ce dont le kit facilitateur a besoin pour
 * fonctionner ensuite hors ligne, sans exception, en un seul téléchargement.
 *
 * Il ne contient AUCUN nom : ni celui d'un parent, ni celui d'un enfant. Le
 * facilitateur reconnaît ses vingt parents grâce à un libellé local qu'il
 * saisit lui-même sur son appareil, gardé dans IndexedDB, exclu de la file
 * d'envoi et purgé en fin de cycle. Ce libellé n'entre jamais dans ce paquet
 * puisqu'il n'existe pas côté serveur.
 *
 * Il ne contient pas non plus les codes à 4 chiffres des parents : le kit n'en
 * a pas besoin, et un appareil perdu ne doit pas ouvrir vingt espaces parents.
 *
 * `audios` liste les fichiers que le service worker doit déposer dans la
 * Cache API. Cible de l'ensemble : moins de 10 Mo.
 */
class PaquetCohorte
{
    public function pour(Cohorte $cohorte): array
    {
        $cohorte->loadMissing('curriculumVersion', 'facilitateur', 'parents');

        $modules = Module::where('curriculum_version_id', $cohorte->curriculum_version_id)
            ->ordonnes()
            ->with(['sequences' => fn ($q) => $q->ordonnees(), 'sequences.unites.realisations'])
            ->get();

        return [
            'genere_a' => Carbon::now()->toIso8601String(),
            'cohorte' => [
                'id' => $cohorte->id,
                'libelle' => $cohorte->libelle,
                'arrondissement' => $cohorte->arrondissement?->libelle,
                'ratio_max' => $cohorte->ratio_max,
                'date_debut' => $cohorte->date_debut->toDateString(),
                'curriculum_version' => [
                    'id' => $cohorte->curriculumVersion->id,
                    'label' => $cohorte->curriculumVersion->label,
                ],
            ],
            'modules' => $modules->map(fn (Module $m) => $this->module($m))->all(),
            'parents' => $cohorte->parents->map(fn ($p) => [
                'code_parent' => $p->code_parent,
                'langue' => $p->langue?->code,
                'statut_matrimonial' => $p->statut_matrimonial,
                'revenu_regularite' => $p->revenu_regularite,
                'telephone_partage' => $p->telephone_partage,
            ])->values()->all(),
            'binomes' => $this->binomes($cohorte),

            /*
            | Le vocabulaire du terrain, embarqué avec le paquet.
            |
            | Sans lui, les écrans d'activité, de visite et de signalement
            | devraient recopier ces listes en dur : elles finiraient par
            | diverger des enums du serveur, et l'écart ne se verrait qu'en
            | mode avion, c'est-à-dire nulle part avant le terrain.
            */
            'referentiel' => [
                'types_activite' => $this->options(TypeActivite::cases()),
                'types_signalement' => $this->options(TypeSignalement::cases()),
                'gravites' => $this->options(GraviteSignalement::cases()),
                'difficultes_fonctionnelles' => $this->options(DifficulteFonctionnelle::cases()),
            ],

            // Ses foyers déjà suivis, pour qu'une seconde visite se rattache au
            // bon dossier sans réseau. Toujours aucun nom : une localité.
            'foyers' => $this->foyers($cohorte),
            'groupes_soutien' => $this->groupes($cohorte),

            /*
            | Ses modules de formation, texte compris.
            |
            | On révise dans un car, sur un banc, en attendant que la salle se
            | remplisse — c'est-à-dire sans réseau. Un catalogue de formation
            | qui exige une connexion ne sert qu'à ceux qui n'en ont pas besoin.
            |
            | Seuls les modules VALIDÉS y entrent : `diffusables()` est le seul
            | chemin par lequel un module atteint un facilitateur.
            */
            'formation' => $this->formation($cohorte->facilitateur_id),

            'audios' => $this->audios($modules),
        ];
    }

    /** @param array<int, \BackedEnum> $cas */
    private function options(array $cas): array
    {
        return array_map(fn ($c) => ['valeur' => $c->value, 'libelle' => $c->libelle()], $cas);
    }

    private function foyers(Cohorte $cohorte): array
    {
        if ($cohorte->facilitateur_id === null) {
            return [];
        }

        return Foyer::where('facilitateur_id', $cohorte->facilitateur_id)
            ->orderBy('localite')
            ->get()
            ->map(fn (Foyer $f) => [
                'uuid' => $f->uuid,
                'localite' => $f->localite,
                'nb_adultes' => $f->nb_adultes,
                'nb_enfants' => $f->nb_enfants,
                'difficultes' => $f->difficultes_fonctionnelles_foyer,
                'deja_suivi_programme' => $f->deja_suivi_programme,
            ])->values()->all();
    }

    private function formation(?int $facilitateurId): array
    {
        // Ce qu'il a déjà lu part avec le paquet : sans cela, un facilitateur
        // hors ligne rouvrirait un module terminé en croyant n'y avoir jamais
        // touché, et sa progression repartirait de zéro à chaque paquet.
        $vues = $facilitateurId === null
            ? collect()
            : ProgressionFormation::where('facilitateur_id', $facilitateurId)
                ->pluck('sections_vues', 'module_formation_id');

        return ModuleFormation::diffusables()->with('sections')->get()
            ->map(fn (ModuleFormation $m) => [
                'sections_vues' => $vues->get($m->id, []),
                'code' => $m->code,
                'titre' => $m->titre,
                'type' => $m->type->value,
                'type_libelle' => $m->type->libelle(),
                'objectif' => $m->objectif,
                'duree_minutes' => $m->duree_minutes,
                'sections' => $m->sections->map(fn (SectionFormation $s) => [
                    'ordre' => $s->ordre,
                    'titre' => $s->titre,
                    'contenu_texte' => $s->contenu_texte,
                    'duree_minutes' => $s->duree_minutes,
                    'fichier_audio' => $s->fichier_audio ? asset($s->fichier_audio) : null,
                ])->values()->all(),
            ])->values()->all();
    }

    private function groupes(Cohorte $cohorte): array
    {
        return GroupeSoutien::where('cohorte_id', $cohorte->id)
            ->orderBy('libelle')
            ->get()
            ->map(fn (GroupeSoutien $g) => [
                'uuid' => $g->uuid,
                'libelle' => $g->libelle,
                'derniere_reunion' => $g->derniere_reunion?->toDateString(),
            ])->values()->all();
    }

    private function module(Module $module): array
    {
        return [
            'id' => $module->id,
            'numero' => $module->numero,
            'titre' => $module->titre,
            'ordre' => $module->ordre,
            // Les modules annoncés mais vides apparaissent quand même : le kit
            // doit montrer l'architecture du programme sans laisser croire
            // qu'un module est prêt alors qu'il ne l'est pas.
            'renseigne' => $module->sequences->isNotEmpty(),
            'duree_totale_minutes' => $module->sequences->sum('duree_minutes'),
            'sequences' => $module->sequences->map(fn (Sequence $s) => $this->sequence($s))->all(),
        ];
    }

    private function sequence(Sequence $sequence): array
    {
        return [
            'id' => $sequence->id,
            'titre' => $sequence->titre,
            'ordre' => $sequence->ordre,
            'duree_minutes' => $sequence->duree_minutes,
            'type' => $sequence->type->value,
            'consigne' => $sequence->consigne,
            'est_brise_glace' => $sequence->est_brise_glace,
            'unites' => $sequence->unites->map(fn (UniteDigitale $u) => $this->unite($u))->all(),
        ];
    }

    private function unite(UniteDigitale $unite): array
    {
        return [
            'id' => $unite->id,
            'message_cle' => $unite->message_cle,
            'realisations' => $unite->realisations->map(fn (Realisation $r) => $this->realisation($r))->all(),
        ];
    }

    private function realisation(Realisation $realisation): array
    {
        return [
            'langue' => $realisation->langue?->code,
            'modalite' => $realisation->modalite->value,
            'titre' => $realisation->titre,
            'contenu_texte' => $realisation->contenu_texte,
            // Peut être null : l'enregistrement n'existe pas encore. Le client
            // doit rester utilisable et basculer sur la version texte.
            'fichier_audio' => $realisation->fichier_audio
                ? asset($realisation->fichier_audio)
                : null,
            'pictogrammes' => $realisation->pictogrammes,
        ];
    }

    /**
     * Les binômes sont donnés en codes parents, jamais en identifiants
     * internes : le kit ne manipule que des codes.
     */
    private function binomes(Cohorte $cohorte): array
    {
        return Binome::with('parentA:id,code_parent', 'parentB:id,code_parent')
            ->whereIn('parent_a_id', $cohorte->parents->pluck('id'))
            ->get()
            ->map(fn (Binome $b) => [$b->parentA->code_parent, $b->parentB->code_parent])
            ->all();
    }

    /**
     * @return array<int, string>
     */
    private function audios($modules): array
    {
        return $modules
            ->flatMap->sequences
            ->flatMap->unites
            ->flatMap->realisations
            ->pluck('fichier_audio')
            ->filter()
            ->unique()
            ->map(fn (string $chemin) => asset($chemin))
            ->values()
            ->all();
    }
}
