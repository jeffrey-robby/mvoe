<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            // La hiérarchie administrative d'abord : tout s'y rattache.
            DecoupageAdministratifSeeder::class,
            ComptesSeeder::class,

            // Les langues avant tout contenu : réalisations, feuilletons,
            // situations et parents y renvoient tous.
            LangueSeeder::class,

            CurriculumSeeder::class,
            FacilitateurSeeder::class,
            CohorteSeeder::class,
            SeanceSeeder::class,

            // Le reste de la région : sans lui, les cinq portées du tableau de
            // bord affichent les mêmes chiffres et ne démontrent rien.
            ReseauDuSudSeeder::class,
            SeancesDuSudSeeder::class,

            // Le travail de terrain : activités, groupes de soutien, foyers,
            // visites, signalements. Il vient après les cohortes, dont il
            // dépend, et rejoue lui aussi la file d'un kit hors ligne.
            TerrainSeeder::class,

            // Le catalogue destiné au facilitateur, distinct de celui des
            // parents. Après les facilitateurs, dont il enregistre la
            // progression.
            FormationSeeder::class,

            // Les campagnes et les canaux : ils s'appuient sur les modules
            // validés, les langues et le découpage administratif.
            CampagneEtCanauxSeeder::class,
            EspaceParentSeeder::class,
        ]);

        $this->rappelDesAcces();
    }

    /**
     * Les comptes administratifs sont créés par ComptesSeeder, en respectant la
     * chaîne d'enregistrement : le MINPROFF crée la régionale, qui crée les
     * départementales, qui créent les superviseurs. On ne les recrée pas ici.
     */
    private function rappelDesAcces(): void
    {
        $this->command?->newLine();
        $this->command?->info('Accès de démonstration — mot de passe : '.ComptesSeeder::MOT_DE_PASSE);
        $this->command?->line('  MINPROFF      minproff@mvoe.test        (national, 10 régions)');
        $this->command?->line('  Régionale     sud@mvoe.test             (Sud, 4 départements, 29 arrondissements)');
        $this->command?->line('  Départementale mvila@mvoe.test          (Mvila, 8 arrondissements)');
        $this->command?->line('  Superviseur   ebolowa-ii@mvoe.test      (Ebolowa II seulement)');
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
