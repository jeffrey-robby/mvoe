<?php

namespace Tests\Feature\Api;

use Database\Seeders\CohorteSeeder;
use Database\Seeders\FacilitateurSeeder;
use Tests\ApiTestCase;

/**
 * Ouverture de session pour les trois rôles, et cloisonnement des jetons.
 *
 * Le point important n'est pas qu'on puisse se connecter, c'est qu'un jeton
 * ne donne accès qu'à son propre espace : la future application Flutter
 * recevra exactement les mêmes jetons, avec exactement les mêmes limites.
 */
class SessionApiTest extends ApiTestCase
{
    public function test_le_facilitateur_ouvre_son_kit_avec_son_numero_et_son_code_appareil(): void
    {
        $this->postJson('/api/facilitateur/session', [
            'telephone' => FacilitateurSeeder::COMPTE_DEMO['telephone'],
            'code_appareil' => FacilitateurSeeder::COMPTE_DEMO['code_appareil'],
        ])
            ->assertOk()
            ->assertJsonStructure(['jeton', 'facilitateur' => ['id', 'nom', 'arrondissement']])
            // Le kit doit rester ouvert des jours sans réseau.
            ->assertJsonPath('expire_a', null);
    }

    public function test_le_facilitateur_se_connecte_aussi_avec_son_email_et_son_mot_de_passe(): void
    {
        $reponse = $this->postJson('/api/facilitateur/session', [
            'email' => FacilitateurSeeder::COMPTE_DEMO['email'],
            'password' => FacilitateurSeeder::COMPTE_DEMO['password'],
        ])->assertOk();

        // Même compte, mêmes droits : la voie d'entrée ne change rien.
        $this->assertSame('Ndzana Étienne', $reponse->json('facilitateur.nom'));

        $this->getJson('/api/facilitateur/cohortes', $this->entete($reponse->json('jeton')))
            ->assertOk();
    }

    public function test_un_mauvais_code_appareil_est_refuse(): void
    {
        $this->postJson('/api/facilitateur/session', [
            'telephone' => FacilitateurSeeder::COMPTE_DEMO['telephone'],
            'code_appareil' => '000000',
        ])->assertStatus(422);
    }

    public function test_un_mauvais_mot_de_passe_est_refuse(): void
    {
        $this->postJson('/api/facilitateur/session', [
            'email' => FacilitateurSeeder::COMPTE_DEMO['email'],
            'password' => 'pas-le-bon',
        ])->assertStatus(422);
    }

    public function test_une_demande_sans_aucun_identifiant_est_refusee(): void
    {
        $this->postJson('/api/facilitateur/session', [])->assertStatus(422);
    }

    public function test_le_mot_de_passe_du_facilitateur_nest_jamais_renvoye(): void
    {
        $reponse = $this->postJson('/api/facilitateur/session', [
            'email' => FacilitateurSeeder::COMPTE_DEMO['email'],
            'password' => FacilitateurSeeder::COMPTE_DEMO['password'],
        ])->assertOk();

        $this->assertStringNotContainsString('password', $reponse->getContent());
        $this->assertStringNotContainsString('code_appareil', $reponse->getContent());
    }

    public function test_le_parent_entre_son_code_parent_et_son_code_a_quatre_chiffres(): void
    {
        $reponse = $this->postJson('/api/parent/session', [
            'code_parent' => 'EB2-04',
            'code_acces' => CohorteSeeder::COMPTES_DEMO['EB2-04'],
            'majeur' => true,
        ])->assertOk();

        // Session courte : le téléphone est souvent partagé au sein du foyer.
        $this->assertNotNull($reponse->json('expire_a'));
    }

    public function test_un_parent_mineur_est_refuse_et_oriente_vers_son_facilitateur(): void
    {
        $this->postJson('/api/parent/session', [
            'code_parent' => 'EB2-04',
            'code_acces' => CohorteSeeder::COMPTES_DEMO['EB2-04'],
            'majeur' => false,
        ])
            ->assertStatus(403)
            ->assertJsonPath('orientation', 'facilitateur');
    }

    public function test_un_mauvais_code_a_quatre_chiffres_est_refuse(): void
    {
        $this->postJson('/api/parent/session', [
            'code_parent' => 'EB2-04',
            'code_acces' => '0000',
            'majeur' => true,
        ])->assertStatus(422);
    }

    public function test_un_jeton_parent_nouvre_ni_le_kit_ni_le_registre(): void
    {
        $jeton = $this->jetonParent();

        $this->getJson('/api/facilitateur/cohortes', $this->entete($jeton))->assertStatus(403);
        $this->getJson('/api/superviseur/facilitateurs', $this->entete($jeton))->assertStatus(403);
    }

    public function test_un_jeton_facilitateur_nouvre_pas_le_rapport_du_superviseur(): void
    {
        $jeton = $this->jetonFacilitateur();

        $this->getJson('/api/superviseur/rapport?annee=2026&trimestre=3', $this->entete($jeton))
            ->assertStatus(403);
    }

    public function test_sans_jeton_rien_de_personnel_nest_accessible(): void
    {
        $this->getJson('/api/facilitateur/cohortes')->assertStatus(401);
        $this->getJson('/api/superviseur/facilitateurs')->assertStatus(401);

        /*
        | L'espace parent fait exception EN LECTURE : les contenus du programme
        | sont produits par le ministere pour etre lus, et exiger un code
        | reviendrait a les reserver aux parents deja inscrits — ceux qui en ont
        | le moins besoin. Ce qui s'attache a une personne reste ferme.
        */
        $this->getJson('/api/parent/modules')->assertOk();
        $this->postJson('/api/parent/questions/1/reponse', ['option_id' => 1])->assertStatus(401);
    }

    public function test_la_deconnexion_revoque_le_jeton(): void
    {
        $jeton = $this->jetonParent();

        $this->deleteJson('/api/session', [], $this->entete($jeton))->assertOk();

        $this->assertDatabaseCount('personal_access_tokens', 0);

        // En test, deux requêtes successives partagent la même instance
        // d'application et le garde a déjà résolu l'utilisateur. En production
        // chaque requête repart de zéro ; on reproduit ça ici.
        $this->app['auth']->forgetGuards();

        // La sonde vise une route qui EXIGE un compte : les lectures de
        // l'espace parent repondent desormais avec ou sans jeton.
        $this->postJson('/api/parent/questions/1/reponse', ['option_id' => 1],
            $this->entete($jeton))->assertStatus(401);
    }

}
