<?php

namespace Database\Seeders;

use App\Enums\Langue;
use App\Models\Binome;
use App\Models\Cohorte;
use App\Models\CurriculumVersion;
use App\Models\Enfant;
use App\Models\Facilitateur;
use App\Models\ParentProgramme;
use Illuminate\Database\Seeder;

/**
 * Une cohorte de vingt parents à Ebolowa II, animée par Ndzana Étienne.
 *
 * Aucun parent n'a de nom ici, et n'en aura jamais côté serveur. Le
 * facilitateur les reconnaît par un libellé local qu'il saisit lui-même sur
 * son appareil (« Odile, marché ») : ce libellé vit dans IndexedDB, il est
 * exclu de la file de synchronisation et purgé en fin de cycle.
 *
 * Quatorze des vingt parents partagent leur téléphone avec le foyer. C'est la
 * raison d'être des sept règles de l'espace parent, et la raison pour laquelle
 * cet espace reste secondaire : la majorité de la cohorte n'y accédera jamais.
 */
class CohorteSeeder extends Seeder
{
    /**
     * Les deux comptes de démonstration, avec leur code à 4 chiffres en clair.
     * En base, ce code est haché : c'est la seule copie lisible qui existe.
     */
    public const COMPTES_DEMO = [
        'EB2-04' => '4821',
        'EB2-11' => '9137',
    ];

    /**
     * [code_parent, langue, statut matrimonial, régularité du revenu,
     *  téléphone partagé, enfants [tranche d'âge, sexe]]
     *
     * Aucune date de naissance d'enfant : une tranche d'âge suffit au
     * programme et ne permet pas de réidentifier un enfant.
     */
    private const PARENTS = [
        ['EB2-01', 'bulu', 'union', 'irregulier', true,  [['3_5', 'f'], ['6_11', 'm']]],
        ['EB2-02', 'fr',   'union', 'regulier',   false, [['0_2', 'm']]],
        ['EB2-03', 'bulu', 'seul',  'aucun',      true,  [['6_11', 'f'], ['12_17', 'f']]],
        ['EB2-04', 'bulu', 'union', 'irregulier', false, [['3_5', 'm'], ['6_11', 'm'], ['12_17', 'f']]],
        ['EB2-05', 'fr',   'union', 'regulier',   true,  [['0_2', 'f']]],
        ['EB2-06', 'bulu', 'seul',  'irregulier', true,  [['6_11', 'm']]],
        ['EB2-07', 'bulu', 'union', 'irregulier', true,  [['3_5', 'f'], ['3_5', 'm']]],
        ['EB2-08', 'fr',   'union', 'non_renseigne', true, [['12_17', 'm']]],
        ['EB2-09', 'bulu', 'seul',  'aucun',      true,  [['0_2', 'm'], ['6_11', 'f']]],
        ['EB2-10', 'bulu', 'union', 'regulier',   false, [['6_11', 'f']]],
        ['EB2-11', 'fr',   'union', 'irregulier', false, [['3_5', 'm'], ['12_17', 'f']]],
        ['EB2-12', 'bulu', 'union', 'irregulier', true,  [['0_2', 'f'], ['3_5', 'm']]],
        ['EB2-13', 'bulu', 'seul',  'aucun',      true,  [['12_17', 'm']]],
        ['EB2-14', 'fr',   'union', 'regulier',   true,  [['6_11', 'm'], ['6_11', 'f']]],
        ['EB2-15', 'bulu', 'union', 'irregulier', true,  [['3_5', 'f']]],
        ['EB2-16', 'bulu', 'non_renseigne', 'irregulier', true, [['0_2', 'm']]],
        ['EB2-17', 'fr',   'seul',  'irregulier', true,  [['6_11', 'f'], ['12_17', 'm']]],
        ['EB2-18', 'bulu', 'union', 'aucun',      true,  [['3_5', 'm']]],
        ['EB2-19', 'bulu', 'union', 'regulier',   false, [['0_2', 'f'], ['6_11', 'm']]],
        ['EB2-20', 'bulu', 'seul',  'irregulier', true,  [['12_17', 'f']]],
    ];

    /**
     * Quatre binômes constitués. Le soutien entre pairs passe par ce lien
     * physique : c'est lui qui permet le statut « rattrapé par binôme » au
     * pointage, et il n'existe aucun fil de discussion entre parents.
     */
    private const BINOMES = [
        ['EB2-01', 'EB2-07'],
        ['EB2-03', 'EB2-09'],
        ['EB2-06', 'EB2-13'],
        ['EB2-15', 'EB2-18'],
    ];

    public function run(): void
    {
        $cohorte = Cohorte::create([
            'libelle' => 'Ebolowa II — groupe du mardi',
            'arrondissement' => 'Ebolowa II',
            // Valeur de départ de la démonstration : elle sera passée à 10
            // depuis l'écran de paramètres, sans toucher au code.
            'ratio_max' => 20,
            'curriculum_version_id' => CurriculumVersion::where('active', true)->value('id'),
            'facilitateur_id' => Facilitateur::where('nom', 'Ndzana Étienne')->value('id'),
            'date_debut' => '2026-07-07',
        ]);

        $parents = [];

        foreach (self::PARENTS as $rang => [$code, $langue, $statut, $revenu, $partage, $enfants]) {
            $parent = ParentProgramme::create([
                'cohorte_id' => $cohorte->id,
                'code_parent' => $code,
                // Le cast `hashed` du modèle hache à l'écriture.
                'code_acces' => self::COMPTES_DEMO[$code] ?? $this->codeAcces($rang),
                'langue_pref' => Langue::from($langue),
                'statut_matrimonial' => $statut,
                'revenu_regularite' => $revenu,
                'telephone_partage' => $partage,
            ]);

            foreach ($enfants as [$tranche, $sexe]) {
                Enfant::create([
                    'parent_id' => $parent->id,
                    'tranche_age' => $tranche,
                    'sexe' => $sexe,
                ]);
            }

            $parents[$code] = $parent;
        }

        foreach (self::BINOMES as [$a, $b]) {
            Binome::create([
                'parent_a_id' => $parents[$a]->id,
                'parent_b_id' => $parents[$b]->id,
            ]);
        }
    }

    /**
     * Code à 4 chiffres remis en main propre par le facilitateur. Déterministe
     * pour que la base de démonstration soit reproductible, jamais consultable
     * ensuite : seuls les deux comptes de démonstration sont documentés.
     */
    private function codeAcces(int $rang): string
    {
        return str_pad((string) (1000 + ($rang * 373) % 8999), 4, '0', STR_PAD_LEFT);
    }
}
