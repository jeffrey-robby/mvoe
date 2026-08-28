<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            CurriculumSeeder::class,
            FacilitateurSeeder::class,
            CohorteSeeder::class,
            SeanceSeeder::class,
            EspaceParentSeeder::class,
        ]);

        // Deux comptes de délégation, pour rendre le cloisonnement démontrable.
        //
        // `arrondissement` à null = délégation départementale : elle lit les
        // huit arrondissements de la Mvila. Avec un arrondissement, la
        // délégation ne lit que le sien — l'écart d'un facilitateur se lit
        // avec lui, et son supérieur direct est le seul à en avoir l'usage.
        User::updateOrCreate(
            ['email' => 'superviseur@mvoe.test'],
            [
                'name' => 'Délégation départementale de la Mvila',
                'arrondissement' => null,
                'password' => Hash::make('mvoe-demo'),
            ],
        );

        User::updateOrCreate(
            ['email' => 'ebolowa2@mvoe.test'],
            [
                'name' => 'Délégation d\'arrondissement — Ebolowa II',
                'arrondissement' => 'Ebolowa II',
                'password' => Hash::make('mvoe-demo'),
            ],
        );

        $this->rappelDesAcces();
    }

    private function rappelDesAcces(): void
    {
        $this->command?->newLine();
        $this->command?->info('Accès de démonstration');
        $this->command?->line('  Délégation    superviseur@mvoe.test / mvoe-demo  (département, 8 arrondissements)');
        $this->command?->line('  Délégation    ebolowa2@mvoe.test / mvoe-demo      (Ebolowa II seulement)');
        $this->command?->line(sprintf(
            '  Facilitateur  %s / %s   (kit, sur le terrain)',
            FacilitateurSeeder::COMPTE_DEMO['telephone'],
            FacilitateurSeeder::COMPTE_DEMO['code_appareil'],
        ));
        $this->command?->line(sprintf(
            '  Facilitateur  %s / %s   (poste de la délégation)',
            FacilitateurSeeder::COMPTE_DEMO['email'],
            FacilitateurSeeder::COMPTE_DEMO['password'],
        ));

        foreach (CohorteSeeder::COMPTES_DEMO as $codeParent => $codeAcces) {
            $this->command?->line(sprintf('  Parent        %s / %s', $codeParent, $codeAcces));
        }

        $this->command?->newLine();
    }
}
