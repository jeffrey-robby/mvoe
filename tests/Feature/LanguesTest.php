<?php

namespace Tests\Feature;

use App\Models\Langue;
use App\Models\ParentProgramme;
use App\Models\Realisation;
use Illuminate\Support\Facades\File;
use Tests\ApiTestCase;

/**
 * Les langues sont des données, plus jamais du code.
 *
 * Le Cameroun compte plus de deux cents langues. Un enum PHP en fige trois et
 * exige un déploiement pour en ajouter une quatrième : ce serait l'équipe
 * technique qui déciderait alors dans quelle langue un parent peut écouter le
 * programme. Cette décision appartient au ministère, et elle se prend en
 * chargeant des réalisations.
 */
class LanguesTest extends ApiTestCase
{
    public function test_aucun_enum_de_langue_ne_subsiste(): void
    {
        $this->assertFileDoesNotExist(app_path('Enums/Langue.php'));

        // Ni dans le schéma : quatre colonnes portaient `enum('fr','en','bulu')`.
        foreach (['parents', 'realisations', 'feuilletons', 'situations_frequentes'] as $table) {
            $colonnes = \Illuminate\Support\Facades\Schema::getColumnListing($table);

            $this->assertContains('langue_id', $colonnes,
                "« $table » doit renvoyer vers `langues`.");
            $this->assertNotContains('langue', $colonnes);
            $this->assertNotContains('langue_pref', $colonnes);
        }
    }

    public function test_la_liste_des_langues_ne_vit_pas_dans_le_javascript(): void
    {
        // Une liste écrite dans le front voudrait dire que l'équipe technique
        // décide des langues du programme. Elle vient du serveur.
        $front = File::get(resource_path('js/parent.js'));

        $this->assertStringContainsString('api.langues()', $front);
        $this->assertStringNotContainsString("{ code: 'en', libelle:", $front);
        $this->assertStringNotContainsString("{ code: 'bulu', libelle:", $front);
    }

    public function test_les_langues_sont_lisibles_sans_aucun_compte(): void
    {
        // Le parent choisit sa langue AVANT de se connecter : on ne peut pas lui
        // demander de lire « choisissez votre langue » dans une langue qu'il n'a
        // pas encore choisie.
        $langues = $this->getJson('/api/langues')->assertOk()->json('langues');

        $this->assertCount(3, $langues);
        $this->assertSame(['fr', 'bulu', 'en'], array_column($langues, 'code'));

        // L'endonyme est ce qu'on affiche dans le sélecteur.
        $this->assertSame('English', collect($langues)->firstWhere('code', 'en')['nom']);
    }

    public function test_une_langue_desactivee_disparait_sans_rien_perdre(): void
    {
        $bulu = Langue::where('code', 'bulu')->firstOrFail();
        $realisations = Realisation::where('langue_id', $bulu->id)->count();

        $this->assertGreaterThan(0, $realisations);

        $bulu->update(['actif' => false]);

        $codes = array_column($this->getJson('/api/langues')->assertOk()->json('langues'), 'code');

        $this->assertNotContains('bulu', $codes);

        // On cesse de la proposer, on ne perd rien : les contenus restent.
        $this->assertSame($realisations, Realisation::where('langue_id', $bulu->id)->count());
    }

    public function test_la_langue_est_un_attribut_du_parent_pas_de_sa_region(): void
    {
        // Un locuteur bulu installé dans l'Océan reçoit du bulu. Le brief est
        // explicite là-dessus, et c'est ce que `parents.langue_id` garantit.
        $bulu = Langue::where('code', 'bulu')->firstOrFail();

        $ailleurs = ParentProgramme::whereHas('cohorte.arrondissement',
            fn ($q) => $q->whereHas('departement', fn ($d) => $d->where('libelle', 'Océan')))
            ->where('langue_id', $bulu->id)
            ->first();

        $this->assertNotNull($ailleurs,
            'Le jeu de démonstration doit contenir un locuteur bulu hors de la Mvila.');
    }

    public function test_le_catalogue_ne_propose_que_les_langues_reellement_chargees(): void
    {
        // « Si seul le français est chargé sur le module 3, elle l'affiche en
        // français et le dit. Elle ne prétend pas. »
        $reponse = $this->getJson('/api/parent/modules/8/unites',
            $this->entete($this->jetonParent()))->assertOk();

        $codes = array_column($reponse->json('langues_disponibles'), 'code');

        // Le module 8 est chargé en français et en bulu, pas en anglais.
        $this->assertEqualsCanonicalizing(['fr', 'bulu'], $codes);
        $this->assertNotContains('en', $codes);
    }

    public function test_un_repli_de_langue_est_toujours_annonce(): void
    {
        // Demander l'anglais sur une unité qui n'existe qu'en français et en
        // bulu : on sert le français, et on le DIT.
        $reponse = $this->getJson('/api/parent/unites/1?langue=en&modalite=texte_picto',
            $this->entete($this->jetonParent()))->assertOk();

        $this->assertSame('en', $reponse->json('langue_demandee.code'));
        $this->assertSame('fr', $reponse->json('langue_servie.code'));
        $this->assertTrue($reponse->json('langue_de_repli'));

        // Et la langue demandée n'est pas listée comme disponible.
        $this->assertNotContains('en', array_column($reponse->json('langues_disponibles'), 'code'));
    }

    public function test_aucun_repli_nest_annonce_quand_la_langue_existe(): void
    {
        $reponse = $this->getJson('/api/parent/unites/1?langue=bulu&modalite=audio',
            $this->entete($this->jetonParent()))->assertOk();

        $this->assertSame('bulu', $reponse->json('langue_servie.code'));
        $this->assertFalse($reponse->json('langue_de_repli'));
    }

    public function test_le_parent_est_servi_dans_sa_langue_sans_rien_demander(): void
    {
        // EB2-04 est enregistré en bulu : sans paramètre de langue, il reçoit
        // du bulu. C'est le sens de « la langue est un attribut du parent ».
        $reponse = $this->getJson('/api/parent/unites/1?modalite=audio',
            $this->entete($this->jetonParent('EB2-04')))->assertOk();

        $this->assertSame('bulu', $reponse->json('langue_demandee.code'));
        $this->assertSame('bulu', $reponse->json('langue_servie.code'));
    }

    public function test_une_langue_inconnue_ne_fait_pas_tomber_lecran(): void
    {
        // Un code qui n'existe pas retombe sur la langue du parent, sans erreur.
        $reponse = $this->getJson('/api/parent/unites/1?langue=klingon&modalite=audio',
            $this->entete($this->jetonParent('EB2-04')))->assertOk();

        $this->assertSame('bulu', $reponse->json('langue_demandee.code'));
    }
}
