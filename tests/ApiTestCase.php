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

    /** Delegation departementale : elle lit les huit arrondissements. */
    protected function jetonSuperviseur(): string
    {
        return $this->postJson('/api/superviseur/session', [
            'email' => 'superviseur@mvoe.test',
            'password' => 'mvoe-demo',
        ])->json('jeton');
    }

    /** Delegation d'arrondissement : elle ne lit qu'Ebolowa II. */
    protected function jetonSuperviseurArrondissement(): string
    {
        return $this->postJson('/api/superviseur/session', [
            'email' => 'ebolowa2@mvoe.test',
            'password' => 'mvoe-demo',
        ])->json('jeton');
    }

    protected function entete(string $jeton): array
    {
        return ['Authorization' => 'Bearer '.$jeton];
    }
}
