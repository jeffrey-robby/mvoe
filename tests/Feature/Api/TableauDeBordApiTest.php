<?php

namespace Tests\Feature\Api;

use App\Models\Arrondissement;
use App\Models\Departement;
use App\Models\Region;
use Tests\ApiTestCase;

/**
 * Le tableau de bord unique, aux cinq portées.
 *
 * Le brief est explicite : ne pas construire cinq tableaux de bord, en
 * construire un et le filtrer. Ces tests vérifient les deux moitiés de cette
 * phrase — que c'est bien LE MÊME (mêmes indicateurs partout, agrégation vraie)
 * et qu'il est bien FILTRÉ (une région n'en voit jamais une autre).
 */
class TableauDeBordApiTest extends ApiTestCase
{
    /** Les indicateurs que tout niveau doit rendre, sans exception. */
    private const INDICATEURS = [
        'facilitateurs_formes', 'facilitateurs_actifs', 'facilitateurs_jamais_actifs',
        'cohortes', 'parents_inscrits', 'seances_tenues',
        'dose_moyenne_par_parent', 'ecarts_releves', 'delai_moyen_remontee_jours',
        // Le terrain : sans ces lignes, un tableau de bord ne montrerait que
        // les séances de cohorte.
        'activites', 'parents_touches', 'dont_hommes', 'dont_femmes',
        'participants_handicap',
        'foyers_suivis', 'foyers_avec_difficulte',
        'groupes_soutien', 'groupes_actifs',
        'signalements', 'signalements_a_traiter',
        // La formation continue : rouvrir un module, c'est rester actif.
        'modules_formation_ouverts', 'facilitateurs_en_formation',
    ];

    public function test_les_cinq_portees_rendent_exactement_les_memes_indicateurs(): void
    {
        // C'est la démonstration qu'il n'y a pas cinq tableaux de bord mais un
        // seul : si un niveau rendait un indicateur de plus ou de moins, c'est
        // qu'on aurait recommencé à en écrire un deuxième.
        $niveaux = [
            'national' => $this->jetonNational(),
            'region' => $this->jetonRegional(),
            'departement' => $this->jetonSuperviseur(),
            'arrondissement' => $this->jetonSuperviseurArrondissement(),
        ];

        foreach ($niveaux as $niveau => $jeton) {
            $this->app['auth']->forgetGuards();

            $reponse = $this->getJson('/api/superviseur/tableau-de-bord', $this->entete($jeton))
                ->assertOk();

            $this->assertSame($niveau, $reponse->json('portee.niveau'));
            $this->assertSame(self::INDICATEURS, array_keys($reponse->json('indicateurs')),
                "Le niveau « $niveau » ne rend pas les mêmes indicateurs que les autres.");
        }

        // Et le cinquième : le facilitateur, par sa propre porte.
        $this->app['auth']->forgetGuards();

        $facilitateur = $this->getJson('/api/facilitateur/tableau-de-bord',
            $this->entete($this->jetonFacilitateur()))->assertOk();

        $this->assertSame('facilitateur', $facilitateur->json('portee.niveau'));
        $this->assertSame(self::INDICATEURS, array_keys($facilitateur->json('indicateurs')));
    }

    public function test_les_indicateurs_du_decoupage_font_la_somme_du_total(): void
    {
        // L'agrégation n'est pas une convention d'affichage : c'est le même
        // code appliqué au total et à chaque ligne. Si la somme des quatre
        // départements ne faisait pas la région, l'un des deux mentirait.
        $reponse = $this->getJson('/api/superviseur/tableau-de-bord',
            $this->entete($this->jetonRegional()))->assertOk();

        $lignes = collect($reponse->json('decoupage.lignes'));

        $this->assertSame(4, $lignes->count(), 'Le Sud a quatre départements.');

        foreach (['cohortes', 'parents_inscrits', 'seances_tenues', 'ecarts_releves',
            'facilitateurs_formes', 'activites', 'parents_touches', 'participants_handicap',
            'foyers_suivis', 'groupes_soutien', 'signalements',
            'modules_formation_ouverts', 'facilitateurs_en_formation'] as $indicateur) {
            $this->assertSame(
                $reponse->json("indicateurs.$indicateur"),
                $lignes->sum($indicateur),
                "La somme des départements doit faire la région, pour « $indicateur ».",
            );
        }
    }

    public function test_le_national_montre_les_dix_regions_dont_neuf_non_peuplees(): void
    {
        // Les neuf autres régions existent en libellé seul. Les taire donnerait
        // à croire que le programme n'est déployé que dans le Sud ; les montrer
        // à zéro dit exactement ce qui est vrai.
        $lignes = collect($this->getJson('/api/superviseur/tableau-de-bord',
            $this->entete($this->jetonNational()))->assertOk()->json('decoupage.lignes'));

        $this->assertCount(10, $lignes);
        $this->assertCount(1, $lignes->where('peuplee', true));
        $this->assertSame('Sud', $lignes->firstWhere('peuplee', true)['libelle']);

        foreach ($lignes->where('peuplee', false) as $region) {
            $this->assertSame(0, $region['cohortes']);
            $this->assertSame(0, $region['facilitateurs_formes']);
        }
    }

    public function test_une_delegation_ne_voit_rien_dune_autre_portee(): void
    {
        $mvila = collect($this->getJson('/api/superviseur/tableau-de-bord',
            $this->entete($this->jetonSuperviseur()))->assertOk()->json('decoupage.lignes'))
            ->pluck('libelle');

        // Les huit arrondissements de la Mvila, et aucun de l'Océan.
        $this->assertCount(8, $mvila);
        $this->assertContains('Ebolowa II', $mvila);
        $this->assertNotContains('Kribi I', $mvila);
    }

    public function test_la_descente_sarrete_aux_frontieres_de_la_portee(): void
    {
        $jeton = $this->jetonSuperviseur();
        $mvila = Departement::where('libelle', 'Mvila')->firstOrFail();
        $ocean = Departement::where('libelle', 'Océan')->firstOrFail();

        // Son propre département : autorisé.
        $this->getJson("/api/superviseur/tableau-de-bord?niveau=departement&entite={$mvila->id}",
            $this->entete($jeton))->assertOk();

        // Celui d'à côté : refusé. Ce n'est pas un raisonnement hiérarchique,
        // c'est une comparaison de listes d'arrondissements.
        $this->getJson("/api/superviseur/tableau-de-bord?niveau=departement&entite={$ocean->id}",
            $this->entete($jeton))->assertForbidden();

        // Et un arrondissement de l'Océan, atteint directement : refusé aussi.
        $kribi = Arrondissement::where('libelle', 'Kribi I')->firstOrFail();

        $this->getJson("/api/superviseur/tableau-de-bord?niveau=arrondissement&entite={$kribi->id}",
            $this->entete($jeton))->assertForbidden();
    }

    public function test_une_delegation_regionale_descend_jusquau_facilitateur(): void
    {
        // Le critère d'acceptation n° 2 du brief : « je vois 4 départements, je
        // descends jusqu'à un facilitateur ».
        $jeton = $this->jetonRegional();
        $ebolowa2 = Arrondissement::where('libelle', 'Ebolowa II')->firstOrFail();

        $reponse = $this->getJson(
            "/api/superviseur/tableau-de-bord?niveau=arrondissement&entite={$ebolowa2->id}",
            $this->entete($jeton))->assertOk();

        $this->assertSame('facilitateur', $reponse->json('decoupage.niveau'));
        $this->assertContains('Ndzana Étienne',
            collect($reponse->json('decoupage.lignes'))->pluck('libelle'));

        // Le fil part de sa propre portée, jamais du national : au-dessus, il
        // n'a rien à voir, et lui proposer un lien serait une porte fermée.
        $fil = collect($reponse->json('fil'));

        $this->assertSame('Sud', $fil->first()['libelle']);
        $this->assertNull($fil->first()['niveau']);
        $this->assertSame(['Sud', 'Mvila', 'Ebolowa II'], $fil->pluck('libelle')->all());
    }

    public function test_le_national_peut_descendre_partout(): void
    {
        $sud = Region::where('libelle', 'Sud')->firstOrFail();

        $reponse = $this->getJson("/api/superviseur/tableau-de-bord?niveau=region&entite={$sud->id}",
            $this->entete($this->jetonNational()))->assertOk();

        $this->assertSame('departement', $reponse->json('decoupage.niveau'));
        $this->assertCount(4, $reponse->json('decoupage.lignes'));
    }

    public function test_le_facilitateur_ne_lit_que_ses_propres_chiffres(): void
    {
        $sien = $this->getJson('/api/facilitateur/tableau-de-bord',
            $this->entete($this->jetonFacilitateur()))->assertOk();

        $this->app['auth']->forgetGuards();

        $arrondissement = $this->getJson('/api/superviseur/tableau-de-bord',
            $this->entete($this->jetonSuperviseurArrondissement()))->assertOk();

        // Il est seul dans son propre tableau de bord, alors que son
        // arrondissement en compte plusieurs.
        $this->assertSame(1, $sien->json('indicateurs.facilitateurs_formes'));
        $this->assertGreaterThan(1, $arrondissement->json('indicateurs.facilitateurs_formes'));

        // Et il ne descend nulle part : sous un facilitateur, il n'y a plus de
        // territoire.
        $this->assertNull($sien->json('decoupage'));
    }

    public function test_un_jeton_facilitateur_nouvre_pas_le_tableau_de_bord_des_delegations(): void
    {
        $this->getJson('/api/superviseur/tableau-de-bord',
            $this->entete($this->jetonFacilitateur()))->assertForbidden();
    }

    public function test_un_jeton_parent_nouvre_aucun_tableau_de_bord(): void
    {
        $jeton = $this->jetonParent();

        $this->getJson('/api/superviseur/tableau-de-bord', $this->entete($jeton))
            ->assertForbidden();

        $this->app['auth']->forgetGuards();

        $this->getJson('/api/facilitateur/tableau-de-bord', $this->entete($jeton))
            ->assertForbidden();
    }
}
