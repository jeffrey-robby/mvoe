<?php

namespace Tests\Feature;

use App\Models\Arrondissement;
use App\Models\Facilitateur;
use Illuminate\Support\Facades\Hash;
use Tests\ApiTestCase;

/**
 * L'enregistrement d'un facilitateur par son superviseur.
 *
 * Le trou principal du système jusqu'ici : on pouvait se connecter comme
 * facilitateur, mais rien ne disait qui créait ce compte. Personne ne
 * s'auto-inscrit — à aucun niveau.
 */
class EnregistrementFacilitateurTest extends ApiTestCase
{
    private const NOUVEAU = [
        'nom' => 'Meyong Clarisse',
        'telephone' => '699 12 34 56',
        'type_juridique' => 'association_femmes',
        'organisation_rattachement' => "Association des femmes d'Ebolowa",
        'date_formation_initiale' => '2026-06-15',
    ];

    public function test_un_superviseur_enregistre_un_facilitateur_et_recoit_ses_identifiants(): void
    {
        $reponse = $this->postJson('/api/superviseur/facilitateurs', self::NOUVEAU,
            $this->entete($this->jetonSuperviseurArrondissement()))->assertStatus(201);

        $this->assertSame('Ebolowa II', $reponse->json('facilitateur.arrondissement'));

        // Les quatre valeurs à remettre en main propre.
        foreach (['telephone', 'code_appareil', 'email', 'mot_de_passe'] as $champ) {
            $this->assertNotEmpty($reponse->json("identifiants.$champ"));
        }

        $this->assertNotEmpty($reponse->json('avertissement'));
    }

    public function test_le_facilitateur_ouvre_son_kit_avec_les_identifiants_remis(): void
    {
        $identifiants = $this->postJson('/api/superviseur/facilitateurs', self::NOUVEAU,
            $this->entete($this->jetonSuperviseurArrondissement()))->json('identifiants');

        $this->app['auth']->forgetGuards();

        // Les deux voies mènent au même compte.
        $this->postJson('/api/facilitateur/session', [
            'telephone' => $identifiants['telephone'],
            'code_appareil' => $identifiants['code_appareil'],
        ])->assertOk()->assertJsonPath('facilitateur.nom', 'Meyong Clarisse');

        $this->postJson('/api/facilitateur/session', [
            'email' => $identifiants['email'],
            'password' => $identifiants['mot_de_passe'],
        ])->assertOk()->assertJsonPath('facilitateur.nom', 'Meyong Clarisse');
    }

    public function test_larrondissement_ne_vient_jamais_de_la_requete(): void
    {
        $kribi = Arrondissement::where('libelle', 'Kribi I')->firstOrFail();

        // On tente de forger la requête pour créer ailleurs. C'est le point de
        // sécurité de tout cet écran : l'arrondissement vient du compte.
        $reponse = $this->postJson('/api/superviseur/facilitateurs',
            [...self::NOUVEAU, 'arrondissement_id' => $kribi->id, 'superviseur_id' => 999],
            $this->entete($this->jetonSuperviseurArrondissement()))->assertStatus(201);

        $this->assertSame('Ebolowa II', $reponse->json('facilitateur.arrondissement'));

        $facilitateur = Facilitateur::where('nom', 'Meyong Clarisse')->firstOrFail();
        $this->assertNotSame($kribi->id, $facilitateur->arrondissement_id);
    }

    public function test_seul_un_superviseur_darrondissement_enregistre(): void
    {
        // La chaîne est explicite : c'est le superviseur qui enregistre les
        // facilitateurs, pas sa hiérarchie.
        foreach ([
            'national' => fn () => $this->jetonNational(),
            'region' => fn () => $this->jetonRegional(),
            'departement' => fn () => $this->jetonSuperviseur(),
        ] as $niveau => $jeton) {
            $this->app['auth']->forgetGuards();

            $this->postJson('/api/superviseur/facilitateurs', self::NOUVEAU, $this->entete($jeton()))
                ->assertStatus(403);
        }
    }

    public function test_le_facilitateur_est_rattache_au_superviseur_qui_la_enregistre(): void
    {
        $this->postJson('/api/superviseur/facilitateurs', self::NOUVEAU,
            $this->entete($this->jetonSuperviseurArrondissement()))->assertStatus(201);

        $facilitateur = Facilitateur::where('nom', 'Meyong Clarisse')->firstOrFail();

        $this->assertSame('ebolowa-ii@mvoe.test', $facilitateur->superviseur->email);
        $this->assertSame('arrondissement', $facilitateur->superviseur->niveau);
    }

    public function test_un_facilitateur_enregistre_nest_pas_actif(): void
    {
        $this->postJson('/api/superviseur/facilitateurs', self::NOUVEAU,
            $this->entete($this->jetonSuperviseurArrondissement()))->assertStatus(201);

        $facilitateur = Facilitateur::where('nom', 'Meyong Clarisse')->firstOrFail();

        // Il n'a encore rien fait, et le registre doit le dire. Le contraire
        // gonflerait artificiellement le nombre de facilitateurs actifs — soit
        // exactement le problème que ce registre existe pour révéler.
        $this->assertNull($facilitateur->derniere_activite);
        $this->assertFalse($facilitateur->estActif());
    }

    public function test_les_identifiants_ne_sont_jamais_relisibles(): void
    {
        $identifiants = $this->postJson('/api/superviseur/facilitateurs', self::NOUVEAU,
            $this->entete($this->jetonSuperviseurArrondissement()))->json('identifiants');

        $facilitateur = Facilitateur::where('nom', 'Meyong Clarisse')->firstOrFail();

        // En base, tout est haché : ni l'API ni le superviseur ne les reliront.
        $this->assertNotSame($identifiants['code_appareil'], $facilitateur->code_appareil);
        $this->assertTrue(Hash::check($identifiants['code_appareil'], $facilitateur->code_appareil));
        $this->assertTrue(Hash::check($identifiants['mot_de_passe'], $facilitateur->password));

        $this->app['auth']->forgetGuards();

        $registre = $this->getJson('/api/superviseur/facilitateurs',
            $this->entete($this->jetonSuperviseurArrondissement()))->getContent();

        $this->assertStringNotContainsString($identifiants['code_appareil'], $registre);
        $this->assertStringNotContainsString($identifiants['mot_de_passe'], $registre);
    }

    public function test_regenerer_les_identifiants_revoque_les_anciens(): void
    {
        $jeton = $this->jetonSuperviseurArrondissement();

        $anciens = $this->postJson('/api/superviseur/facilitateurs', self::NOUVEAU,
            $this->entete($jeton))->json('identifiants');

        $facilitateur = Facilitateur::where('nom', 'Meyong Clarisse')->firstOrFail();

        $this->app['auth']->forgetGuards();

        // Le facilitateur a ouvert son kit : il a un jeton en cours.
        $jetonKit = $this->postJson('/api/facilitateur/session', [
            'telephone' => $anciens['telephone'],
            'code_appareil' => $anciens['code_appareil'],
        ])->json('jeton');

        $this->app['auth']->forgetGuards();

        $nouveaux = $this->postJson("/api/superviseur/facilitateurs/{$facilitateur->id}/identifiants",
            [], $this->entete($jeton))->assertOk()->json('identifiants');

        $this->assertNotSame($anciens['code_appareil'], $nouveaux['code_appareil']);

        $this->app['auth']->forgetGuards();

        // L'ancien code ne fonctionne plus…
        $this->postJson('/api/facilitateur/session', [
            'telephone' => $anciens['telephone'],
            'code_appareil' => $anciens['code_appareil'],
        ])->assertStatus(422);

        // …et l'appareil qui portait l'ancien jeton ne remonte plus rien.
        $this->getJson('/api/facilitateur/cohortes', $this->entete($jetonKit))->assertStatus(401);
    }

    public function test_un_superviseur_ne_regenere_pas_les_identifiants_dun_autre_arrondissement(): void
    {
        $kribi = Arrondissement::where('libelle', 'Kribi I')->firstOrFail();
        $etranger = Facilitateur::where('arrondissement_id', $kribi->id)->firstOrFail();

        $this->postJson("/api/superviseur/facilitateurs/{$etranger->id}/identifiants",
            [], $this->entete($this->jetonSuperviseurArrondissement()))->assertStatus(403);
    }

    public function test_le_telephone_ne_peut_pas_etre_attribue_deux_fois(): void
    {
        $jeton = $this->jetonSuperviseurArrondissement();

        $this->postJson('/api/superviseur/facilitateurs', self::NOUVEAU, $this->entete($jeton))
            ->assertStatus(201);

        $this->app['auth']->forgetGuards();

        $this->postJson('/api/superviseur/facilitateurs',
            [...self::NOUVEAU, 'nom' => 'Autre personne'], $this->entete($jeton))
            ->assertStatus(422)
            ->assertJsonValidationErrors('telephone');
    }

    public function test_aucun_ecran_dinscription_publique(): void
    {
        // « Aucun écran d'inscription publique, à aucun niveau. »
        $this->postJson('/api/superviseur/facilitateurs', self::NOUVEAU)->assertStatus(401);

        foreach (\Illuminate\Support\Facades\Route::getRoutes() as $route) {
            $uri = $route->uri();

            $this->assertStringNotContainsStringIgnoringCase('inscription', $uri);
            $this->assertStringNotContainsStringIgnoringCase('register', $uri);
        }
    }
}
