<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\File;
use Tests\ApiTestCase;

/**
 * Les écrans de la délégation d'arrondissement.
 *
 * Deux exigences du cahier des charges y sont vérifiées : le livrable du
 * superviseur est un DOCUMENT et non un tableau de bord, et le plafond d'une
 * cohorte est une donnée qu'on change sans toucher au code.
 */
class DelegationTest extends ApiTestCase
{
    public function test_les_quatre_ecrans_repondent(): void
    {
        foreach ([
            '/superviseur',
            '/superviseur/connexion',
            '/superviseur/rapport',
            '/superviseur/parametres',
        ] as $route) {
            $this->get($route)->assertOk();
        }
    }

    public function test_aucune_donnee_nest_rendue_cote_serveur(): void
    {
        // Même règle que pour le kit : le client Blade n'a aucun privilège que
        // l'application Flutter n'aurait pas. Tout passe par l'API et un jeton.
        foreach (['/superviseur', '/superviseur/rapport', '/superviseur/parametres'] as $route) {
            $contenu = $this->get($route)->getContent();

            $this->assertStringNotContainsString('Ndzana', $contenu);
            $this->assertStringNotContainsString('Ebolowa II — groupe du mardi', $contenu);
        }
    }

    public function test_la_liste_des_cohortes_expose_le_plafond_et_le_depassement(): void
    {
        $reponse = $this->getJson('/api/superviseur/cohortes', $this->entete($this->jetonSuperviseur()))
            ->assertOk();

        $cohorte = $reponse->json('cohortes.0');

        $this->assertSame(20, $cohorte['ratio_max']);
        $this->assertSame(20, $cohorte['effectif']);
        $this->assertSame(0, $cohorte['effectif_au_dela_du_plafond']);
    }

    public function test_baisser_le_plafond_ne_retire_personne(): void
    {
        $jeton = $this->jetonSuperviseur();

        $this->patchJson('/api/superviseur/cohortes/1', ['ratio_max' => 10], $this->entete($jeton))
            ->assertOk();

        $cohorte = $this->getJson('/api/superviseur/cohortes', $this->entete($jeton))
            ->json('cohortes.0');

        // Le programme ne supprime pas quelqu'un parce qu'un chiffre a changé :
        // le dépassement est signalé, il n'est pas « corrigé ».
        $this->assertSame(10, $cohorte['ratio_max']);
        $this->assertSame(20, $cohorte['effectif']);
        $this->assertSame(10, $cohorte['effectif_au_dela_du_plafond']);
    }

    public function test_un_jeton_facilitateur_nouvre_pas_les_cohortes_de_la_delegation(): void
    {
        $this->getJson('/api/superviseur/cohortes', $this->entete($this->jetonFacilitateur()))
            ->assertStatus(403);
    }

    public function test_une_delegation_darrondissement_ne_lit_que_le_sien(): void
    {
        $jeton = $this->jetonSuperviseurArrondissement();

        $registre = $this->getJson('/api/superviseur/facilitateurs', $this->entete($jeton))->assertOk();

        $this->assertSame('Ebolowa II', $registre->json('perimetre'));
        $this->assertSame(2, $registre->json('synthese.formes'));

        foreach ($registre->json('facilitateurs') as $f) {
            $this->assertSame('Ebolowa II', $f['arrondissement']);
        }
    }

    public function test_la_delegation_departementale_lit_les_huit_arrondissements(): void
    {
        $registre = $this->getJson('/api/superviseur/facilitateurs', $this->entete($this->jetonSuperviseur()))
            ->assertOk();

        $this->assertSame('Departement de la Mvila', str_replace('é', 'e', $registre->json('perimetre')));
        $this->assertSame(14, $registre->json('synthese.formes'));
    }

    public function test_le_perimetre_ne_selargit_pas_par_un_parametre_durl(): void
    {
        $jeton = $this->jetonSuperviseurArrondissement();

        // Le filtre de la requete ne peut que restreindre davantage.
        $reponse = $this->getJson('/api/superviseur/facilitateurs?arrondissement=Mvangan',
            $this->entete($jeton))->assertOk();

        $this->assertSame([], $reponse->json('facilitateurs'));
    }

    public function test_le_rapport_est_cloisonne_sur_le_perimetre_du_compte(): void
    {
        $arrondissement = $this->getJson('/api/superviseur/rapport?annee=2026&trimestre=3',
            $this->entete($this->jetonSuperviseurArrondissement()))->assertOk();

        // En test, deux requetes successives partagent la meme instance
        // d'application et le garde a deja resolu l'utilisateur. En production
        // chaque requete repart de zero ; on reproduit ca ici.
        $this->app['auth']->forgetGuards();

        $departement = $this->getJson('/api/superviseur/rapport?annee=2026&trimestre=3',
            $this->entete($this->jetonSuperviseur()))->assertOk();

        // Le dispositif compte varie ; les seances d'Ebolowa II sont les memes.
        $this->assertSame(2, $arrondissement->json('synthese.facilitateurs_formes'));
        $this->assertSame(14, $departement->json('synthese.facilitateurs_formes'));
        $this->assertSame('Ebolowa II', $arrondissement->json('perimetre'));
    }

    public function test_le_rapport_est_un_document_pas_un_tableau_de_bord(): void
    {
        $vue = File::get(resource_path('views/superviseur/rapport.blade.php'));

        // On inspecte le balisage, pas les commentaires : ceux-ci parlent
        // justement de ce qu'on s'interdit.
        $balisage = preg_replace('/\{\{--.*?--\}\}/s', '', $vue);

        // « Aucun tableau de bord temps réel. Le livrable du superviseur est un
        // document, pas un écran de graphiques. »
        foreach (['<canvas', '<svg', 'chart', 'setInterval', 'setTimeout'] as $interdit) {
            $this->assertStringNotContainsStringIgnoringCase($interdit, $balisage,
                "Le rapport ne doit pas devenir un tableau de bord (« $interdit »).");
        }

        // Il s'imprime, et le sélecteur de période ne s'imprime pas avec lui.
        $this->assertStringContainsString('sans-impression', $vue);
        $this->assertStringContainsString('Exporter en PDF', $vue);
    }

    public function test_la_feuille_dimpression_efface_le_jaune(): void
    {
        $css = File::get(resource_path('css/app.css'));

        [, $impression] = explode('@media print {', $css, 2);
        [$corps] = explode("\n}\n", $impression, 2);

        // Sur une imprimante monochrome de délégation, le jaune sortirait en
        // gris pâle illisible. Les surfaces deviennent des encadrés au trait.
        $this->assertStringContainsString('.sans-impression', $corps);
        $this->assertStringContainsString('background: transparent !important', $corps);
    }

    public function test_les_ecrans_de_la_delegation_ne_sont_pas_mis_en_cache_hors_ligne(): void
    {
        $sw = File::get(public_path('sw.js'));

        // Un rapport servi depuis un cache serait un rapport périmé — soit
        // exactement ce qu'un document ne doit pas être.
        $this->assertStringContainsString("url.pathname.startsWith('/superviseur')", $sw);
    }

    public function test_la_session_du_superviseur_ne_survit_pas_a_la_fermeture_de_longlet(): void
    {
        $js = File::get(resource_path('js/superviseur.js'));

        // Le poste de la délégation est souvent partagé entre plusieurs agents.
        $this->assertStringContainsString('sessionStorage.setItem', $js);
        $this->assertStringNotContainsString('localStorage', $js);
    }
}
