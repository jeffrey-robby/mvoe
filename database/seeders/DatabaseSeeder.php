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

        // Le superviseur est le seul compte à mot de passe du système :
        // le facilitateur travaille sur son appareil, le parent entre avec un
        // code à 4 chiffres remis en main propre.
        User::updateOrCreate(
            ['email' => 'superviseur@mvoe.test'],
            [
                'name' => 'Délégation d\'arrondissement — Ebolowa II',
                'password' => Hash::make('mvoe-demo'),
            ],
        );

        $this->rappelDesAcces();
    }

    private function rappelDesAcces(): void
    {
        $this->command?->newLine();
        $this->command?->info('Accès de démonstration');
        $this->command?->line('  Superviseur   superviseur@mvoe.test / mvoe-demo');

        foreach (CohorteSeeder::COMPTES_DEMO as $codeParent => $codeAcces) {
            $this->command?->line(sprintf('  Parent        %s / %s', $codeParent, $codeAcces));
        }

        $this->command?->newLine();
    }
}
