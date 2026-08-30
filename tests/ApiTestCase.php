<?php

namespace Tests;

use Database\Seeders\CohorteSeeder;
use Database\Seeders\FacilitateurSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

/**
 * Base des tests d'API : la base de démonstration complète, et de quoi ouvrir
 * une session dans chacun des trois rôles.
 *
 * Les tests passent par HTTP comme le fera l'application Flutter. Aucun test
 * ne prend de raccourci en écrivant directement en base : ce que les tests
 * peuvent faire, un client peut le faire, et réciproquement.
 */
abstract class ApiTestCase extends TestCase
{
    use RefreshDatabase;

    protected bool $seed = true;

    protected function jetonFacilitateur(): string
    {
        return $this->postJson('/api/facilitateur/session', [
            'telephone' => FacilitateurSeeder::COMPTE_DEMO['telephone'],
            'code_appareil' => FacilitateurSeeder::COMPTE_DEMO['code_appareil'],
        ])->json('jeton');
    }

    protected function jetonParent(string $codeParent = 'EB2-04'): string
    {
        return $this->postJson('/api/parent/session', [
            'code_parent' => $codeParent,
            'code_acces' => CohorteSeeder::COMPTES_DEMO[$codeParent],
            'majeur' => true,
        ])->json('jeton');
    }

    /** Les quatre niveaux de la chaine administrative. */
    protected function jetonDelegation(string $email): string
    {
        return $this->postJson('/api/superviseur/session', [
            'email' => $email,
            'password' => \Database\Seeders\ComptesSeeder::MOT_DE_PASSE,
        ])->json('jeton');
    }

    /** MINPROFF : les 10 regions, aucun filtre. */
    protected function jetonNational(): string
    {
        return $this->jetonDelegation('minproff@mvoe.test');
    }

    /** Delegation regionale du Sud : 4 departements, 29 arrondissements. */
    protected function jetonRegional(): string
    {
        return $this->jetonDelegation('sud@mvoe.test');
    }

    /** Delegation departementale de la Mvila : 8 arrondissements. */
    protected function jetonSuperviseur(): string
    {
        return $this->jetonDelegation('mvila@mvoe.test');
    }

    /** Superviseur d'arrondissement : Ebolowa II seulement. */
    protected function jetonSuperviseurArrondissement(): string
    {
        return $this->jetonDelegation('ebolowa-ii@mvoe.test');
    }

    protected function entete(string $jeton): array
    {
        return ['Authorization' => 'Bearer '.$jeton];
    }
}
