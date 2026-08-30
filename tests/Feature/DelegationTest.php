<?php

namespace Tests\Feature;

use App\Models\Cohorte;
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
    /** La seule cohorte complète en données, celle qu'on ouvre en démonstration. */
    private const COHORTE_DEMO = 'Ebolowa II — groupe du mardi';


    public function test_les_ecrans_de_la_delegation_repondent(): void
    {
        foreach ([
            '/superviseur',
            '/superviseur/tableau-de-bord',
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
        foreach (['/superviseur', '/superviseur/tableau-de-bord', '/superviseur/rapport',
            '/superviseur/parametres'] as $route) {
            $contenu = $this->get($route)->getContent();

            $this->assertStringNotContainsString('Ndzana', $contenu);
            $this->assertStringNotContainsString('Ebolowa II — groupe du mardi', $contenu);
        }
    }

    public function test_la_liste_des_cohortes_expose_le_plafond_et_le_depassement(): void
    {
        $reponse = $this->getJson('/api/superviseur/cohortes', $this->entete($this->jetonSuperviseur()))
            ->assertOk();

        // La cohorte est retrouvée par son libellé, jamais par sa position :
        // depuis que la région entière est peuplée, « la première cohorte »
        // n'est plus celle de la démonstration.
        $cohorte = collect($reponse->json('cohortes'))
            ->firstWhere('libelle', self::COHORTE_DEMO);

        $this->assertNotNull($cohorte, 'La cohorte de démonstration doit être lisible ici.');
        $this->assertSame(20, $cohorte['ratio_max']);
        $this->assertSame(20, $cohorte['effectif']);
        $this->assertSame(0, $cohorte['effectif_au_dela_du_plafond']);
    }

    public function test_baisser_le_plafond_ne_retire_personne(): void
    {
        $jeton = $this->jetonSuperviseur();
        $id = Cohorte::where('libelle', self::COHORTE_DEMO)->value('id');

        $this->patchJson("/api/superviseur/cohortes/{$id}", ['ratio_max' => 10], $this->entete($jeton))
            ->assertOk();

        $cohorte = collect($this->getJson('/api/superviseur/cohortes', $this->entete($jeton))
            ->json('cohortes'))->firstWhere('libelle', self::COHORTE_DEMO);

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

    public function test_chaque_niveau_lit_exactement_sa_portee(): void
    {
        // Un seul registre, quatre portees. On n'assene pas des nombres tires
        // du jeu de donnees : on verifie la FORME de la portee, qui elle ne
        // depend pas du volume seede.
        $attendu = [
            'national' => null,   // aucun filtre
            'region' => 29,
            'departement' => 8,
            'arrondissement' => 1,
        ];

        foreach ([
            'national' => fn () => $this->jetonNational(),
            'region' => fn () => $this->jetonRegional(),
            'departement' => fn () => $this->jetonSuperviseur(),
            'arrondissement' => fn () => $this->jetonSuperviseurArrondissement(),
        ] as $niveau => $jeton) {
            $this->app['auth']->forgetGuards();

            $reponse = $this->getJson('/api/superviseur/facilitateurs', $this->entete($jeton()))
                ->assertOk();

            $this->assertSame($niveau, $reponse->json('portee.niveau'));
            $this->assertSame($attendu[$niveau], $reponse->json('portee.arrondissements'));
        }
    }

    public function test_une_delegation_darrondissement_ne_lit_que_le_sien(): void
    {
        $registre = $this->getJson('/api/superviseur/facilitateurs',
            $this->entete($this->jetonSuperviseurArrondissement()))->assertOk();

        $this->assertSame('Ebolowa II', $registre->json('portee.libelle'));
        $this->assertNotEmpty($registre->json('facilitateurs'));

        foreach ($registre->json('facilitateurs') as $f) {
            $this->assertSame('Ebolowa II', $f['arrondissement']);
        }
    }

    public function test_une_delegation_departementale_ne_voit_aucun_autre_departement(): void
    {
        $registre = $this->getJson('/api/superviseur/facilitateurs',
            $this->entete($this->jetonSuperviseur()))->assertOk();

        $this->assertSame('Mvila', $registre->json('portee.libelle'));

        foreach ($registre->json('facilitateurs') as $f) {
            $this->assertSame('Mvila', $f['departement']);
        }
    }

    public function test_le_national_voit_les_quatre_departements(): void
    {
        $registre = $this->getJson('/api/superviseur/facilitateurs',
            $this->entete($this->jetonNational()))->assertOk();

        $departements = collect($registre->json('facilitateurs'))->pluck('departement')->unique();

        $this->assertCount(4, $departements);
        $this->assertSame(50, $registre->json('synthese.formes'));
    }

    public function test_la_portee_ne_selargit_pas_par_un_parametre_durl(): void
    {
        // Un arrondissement d'un AUTRE departement que celui du compte.
        $kribi = \App\Models\Arrondissement::where('libelle', 'Kribi I')->value('id');

        $reponse = $this->getJson("/api/superviseur/facilitateurs?arrondissement_id={$kribi}",
            $this->entete($this->jetonSuperviseur()))->assertOk();

        // Le filtre de requete ne peut que restreindre davantage. Une fuite
        // ici serait une fuite de donnees entre departements.
        $this->assertSame([], $reponse->json('facilitateurs'));
    }

    public function test_le_rapport_est_cloisonne_sur_la_portee_du_compte(): void
    {
        $arrondissement = $this->getJson('/api/superviseur/rapport?annee=2026&trimestre=3',
            $this->entete($this->jetonSuperviseurArrondissement()))->assertOk();

        $this->app['auth']->forgetGuards();

        $national = $this->getJson('/api/superviseur/rapport?annee=2026&trimestre=3',
            $this->entete($this->jetonNational()))->assertOk();

        $this->assertSame('Ebolowa II', $arrondissement->json('portee.libelle'));
        $this->assertSame('national', $national->json('portee.niveau'));

        // Le dispositif compte varie avec la portee ; les seances d'Ebolowa II
        // sont les memes des deux cotes.
        $this->assertLessThan(
            $national->json('synthese.facilitateurs_formes'),
            $arrondissement->json('synthese.facilitateurs_formes'),
        );
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
