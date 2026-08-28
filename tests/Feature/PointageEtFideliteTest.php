<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\File;
use Tests\TestCase;

/**
 * Le pointage et la fiche de fidélité.
 *
 * Ces deux écrans portent les règles les plus faciles à casser sans s'en
 * rendre compte, et les plus coûteuses à casser : l'un ne doit rien supposer
 * sur les parents, l'autre ne doit rien souffler au facilitateur.
 */
class PointageEtFideliteTest extends TestCase
{
    public function test_aucun_parent_ne_demarre_suppose_present(): void
    {
        $kit = File::get(resource_path('js/kit.js'));

        [, $pointage] = explode('export function pointage()', $kit, 2);

        // Chaque parent démarre « à pointer ». Un parent qu'on oublie reste
        // visiblement non pointé et n'est jamais remonté : mieux vaut un trou
        // déclaré qu'une présence inventée.
        $this->assertStringContainsString("?? 'a_pointer'", $pointage);
    }

    public function test_le_cycle_de_pointage_ne_revient_jamais_a_non_pointe(): void
    {
        $kit = File::get(resource_path('js/kit.js'));

        [, $pointage] = explode('export function pointage()', $kit, 2);
        [$ordre] = explode('etiquettes:', $pointage, 2);

        // On ne peut pas dé-pointer quelqu'un.
        $this->assertStringContainsString("ordre: ['present', 'absent', 'rattrape_binome']", $ordre);
        $this->assertStringNotContainsString("'a_pointer',", $ordre);
    }

    public function test_le_pointage_ecrit_levenement_avant_de_mettre_lecran_a_jour(): void
    {
        $kit = File::get(resource_path('js/kit.js'));

        [, $methode] = explode('pointer(codeParent) {', $kit, 2);
        [$corps] = explode('get pointes()', $methode, 2);

        $ecriture = strpos($corps, 'file.majPresence');
        $affichage = strpos($corps, 'this.statuts[codeParent] = suivant');

        $this->assertNotFalse($ecriture);
        $this->assertNotFalse($affichage);
        $this->assertLessThan($affichage, $ecriture,
            "Toute donnée est écrite en local avant d'être affichée à l'écran.");
    }

    public function test_les_appuis_intermediaires_ne_remplissent_pas_la_file(): void
    {
        $magasin = File::get(resource_path('js/magasin.js'));

        [, $maj] = explode('majPresence(seanceUuid, codeParent, statut) {', $magasin, 2);
        [$corps] = explode('presencesDe(seanceUuid)', $maj, 2);

        // Passer par « absent » pour atteindre « binôme » ne doit pas laisser
        // trace d'une décision qui n'a jamais existé : tant que l'événement
        // n'est pas parti, on le remplace au lieu d'en empiler un nouveau.
        $this->assertStringContainsString('enAttente.charge.statut = statut', $corps);
    }

    public function test_aucune_charge_devenement_ne_transporte_de_libelle_local(): void
    {
        $sources = File::get(resource_path('js/kit.js')).File::get(resource_path('js/magasin.js'));

        // `charge` est la seule partie d'un événement qui part vers le serveur.
        // Le libellé (« Odile, marché ») est une donnée nominative : il ne doit
        // apparaître dans aucune de ces charges, jamais.
        preg_match_all('/charge:\s*\{[^}]*\}/s', $sources, $charges);

        $this->assertNotEmpty($charges[0], 'Aucune charge trouvée : le test ne vérifie rien.');

        foreach ($charges[0] as $charge) {
            $this->assertStringNotContainsString('libelle', $charge,
                "Une charge d'événement transporte un libellé local : $charge");
        }
    }

    public function test_la_fiche_de_fidelite_ne_souffle_jamais_lobserve(): void
    {
        $vue = File::get(resource_path('views/kit/fidelite.blade.php'));

        // Montrer au facilitateur ce que l'outil a observé rendrait les deux
        // sources dépendantes l'une de l'autre, et l'écart déclaré/observé ne
        // mesurerait plus rien.
        foreach (['etats', 'ouverture', 'duree_reelle', 'observee', 'sequences_ouvertes'] as $fuite) {
            $this->assertStringNotContainsString($fuite, $vue,
                "La fiche ne doit rien révéler de l'observé (« $fuite »).");
        }
    }

    public function test_la_fiche_reste_fermee_tant_que_la_seance_nest_pas_terminee(): void
    {
        $kit = File::get(resource_path('js/kit.js'));
        $vue = File::get(resource_path('views/kit/fidelite.blade.php'));

        // « Après la séance uniquement, jamais pendant. »
        $this->assertStringContainsString('return this.seance?.terminee === true;', $kit);
        $this->assertStringContainsString("La fiche s'ouvre à la fin de la séance.", $vue);
    }

    public function test_une_sequence_non_realisee_ne_demande_pas_de_note(): void
    {
        $kit = File::get(resource_path('js/kit.js'));

        [, $repondre] = explode('repondre(sequenceId, realisee) {', $kit, 2);
        [$corps] = explode('noter(', $repondre, 2);

        // On ne demande pas de noter la qualité de ce qui n'a pas eu lieu.
        $this->assertStringContainsString('if (realisee === false)', $corps);
    }

    public function test_une_sequence_laissee_vide_nest_pas_remontee(): void
    {
        $kit = File::get(resource_path('js/kit.js'));

        [, $valider] = explode('valider() {', $kit, 2);

        // Le silence du facilitateur n'est pas une déclaration. On ne le
        // transforme pas en « non réalisée ».
        $this->assertStringContainsString('if (reponse.realisee === null) continue;', $valider);
    }

    public function test_letat_de_la_seance_survit_a_un_rechargement(): void
    {
        $magasin = File::get(resource_path('js/magasin.js'));
        $kit = File::get(resource_path('js/kit.js'));

        // Un téléphone qui se met en veille au milieu de quatre-vingt-dix
        // minutes ne doit pas faire perdre sa place au facilitateur.
        $this->assertStringContainsString("seance: 'seance-en-cours'", $magasin);
        $this->assertStringContainsString('reprendre()', $kit);
        $this->assertStringContainsString('this.lancerMinuteur();', $kit);
    }
}
