<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Vite;
use Tests\TestCase;

/**
 * Garde-fous du deploiement.
 *
 * Ce qui casse une mise en ligne ne casse presque jamais un test : ce sont des
 * fichiers presents chez le developpeur et absents du serveur, ou l'inverse.
 * Rien ne leve d'exception — le serveur repond 200 et la page arrive nue.
 */
class DeploiementTest extends TestCase
{
    public function test_le_mode_vite_en_direct_est_inatteignable_hors_du_poste_de_developpement(): void
    {
        /*
        | Un « npm run dev » lance sur le serveur depose `public/hot`, et
        | Laravel sert alors tout le CSS depuis 127.0.0.1:5174 — la machine du
        | VISITEUR. C'est arrive en production le 31 aout 2026 : les pages
        | s'affichaient sans une seule regle de style, sans une ligne de
        | journal.
        |
        | Les tests tournent sous APP_ENV=testing, donc hors « local » : le
        | drapeau doit deja pointer ailleurs que sur public/hot.
        */
        $this->assertNotSame(public_path('hot'), Vite::hotFile(),
            'Hors du poste de developpement, public/hot ne doit jamais etre consulte.');

        $this->assertFileDoesNotExist(Vite::hotFile(),
            'Le drapeau de secours doit designer un chemin qui n\'existe pas.');
    }

    public function test_les_dossiers_de_travail_de_storage_voyagent_avec_le_depot(): void
    {
        /*
        | Git ne transporte pas un dossier vide. Sans ces marqueurs, Blade
        | refuse de compiler sur un serveur neuf — « Please provide a valid
        | cache path » — et l'application ne demarre pas du tout.
        */
        foreach (['cache/data', 'sessions', 'views'] as $dossier) {
            $this->assertFileExists(storage_path("framework/{$dossier}/.gitignore"),
                "storage/framework/{$dossier} doit porter un marqueur suivi par git.");
        }
    }

    public function test_composer_declare_la_version_de_laravel_reellement_installee(): void
    {
        /*
        | Le collage du template Vristo avait remis un composer.json de Laravel
        | 10 par-dessus. En local rien ne bougeait — vendor/ datait d'avant —
        | mais le premier `composer install` du serveur aurait installe la 10
        | sous un code ecrit pour la 13.
        */
        $declare = json_decode(File::get(base_path('composer.json')), true);
        $verrou = json_decode(File::get(base_path('composer.lock')), true);

        $installe = app()->version();
        $majeure = explode('.', $installe)[0];

        $this->assertStringContainsString($majeure, $declare['require']['laravel/framework'],
            "composer.json declare {$declare['require']['laravel/framework']} alors que Laravel {$installe} tourne.");

        $verrouille = collect($verrou['packages'])->firstWhere('name', 'laravel/framework')['version'] ?? '';

        $this->assertStringStartsWith("v{$majeure}.", $verrouille,
            "composer.lock verrouille laravel/framework en {$verrouille} alors que Laravel {$installe} tourne.");
    }
}
