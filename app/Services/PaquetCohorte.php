<?php

namespace App\Services;

use App\Models\Binome;
use App\Models\Cohorte;
use App\Models\Module;
use App\Models\Realisation;
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
                'arrondissement' => $cohorte->arrondissement,
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
                'langue_pref' => $p->langue_pref->value,
                'statut_matrimonial' => $p->statut_matrimonial,
                'revenu_regularite' => $p->revenu_regularite,
                'telephone_partage' => $p->telephone_partage,
            ])->values()->all(),
            'binomes' => $this->binomes($cohorte),
            'audios' => $this->audios($modules),
        ];
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
            'langue' => $realisation->langue->value,
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
