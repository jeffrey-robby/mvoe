<?php

namespace Database\Seeders;

use App\Enums\TypeJuridique;
use App\Models\Arrondissement;
use App\Models\Facilitateur;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

/**
 * Cinquante facilitateurs répartis sur les vingt-neuf arrondissements du Sud.
 *
 * Chacun est rattaché au SUPERVISEUR de son arrondissement : c'est lui qui l'a
 * enregistré et lui a remis ses identifiants. Un facilitateur sans superviseur
 * n'existe pas, et la colonne est obligatoire en base.
 *
 * Les dates de dernière activité sont volontairement très inégales, et six
 * facilitateurs n'ont jamais tenu la moindre séance depuis leur formation.
 * C'est exactement ce que le registre doit rendre visible : aujourd'hui,
 * personne ne sait combien de facilitateurs formés sont encore actifs.
 *
 * Les `type_juridique` sont variés à dessein. Savoir lequel tient le plus
 * longtemps est une question à laquelle personne ne peut répondre aujourd'hui.
 */
class FacilitateurSeeder extends Seeder
{
    /**
     * Le compte de démonstration. Deux voies d'entrée vers le même compte :
     * téléphone + code d'appareil sur le terrain, e-mail + mot de passe depuis
     * un poste de la délégation.
     */
    public const COMPTE_DEMO = [
        'telephone' => '699 41 27 08',
        'code_appareil' => '481207',
        'email' => 'ndzana.etienne@minproff.cm',
        'password' => 'mvoe-demo',
    ];

    private const NOMS = [
        'Ndzana', 'Ateba', 'Nkoulou', 'Owona', 'Mengue', 'Essomba', 'Abega',
        'Bikoé', 'Ondoua', 'Zé', 'Amougou', 'Ngono', 'Mvondo', 'Belinga',
        'Eyenga', 'Nkodo', 'Mbarga', 'Assiga', 'Bidzogo', 'Ekotto', 'Fouda',
        'Manga', 'Nnomo', 'Obama', 'Onana', 'Ottou', 'Tsala', 'Zibi', 'Ayissi',
        'Bekolo',
    ];

    private const PRENOMS = [
        'Étienne', 'Marie-Claire', 'Jean-Pierre', 'Bernadette', 'Solange',
        'Pascal', 'Thérèse', 'Rodrigue', 'Célestine', 'Barnabé', 'Léonie',
        'Perpétue', 'Serge', 'Antoinette', 'Clarisse', 'Emmanuel', 'Josiane',
        'Martin', 'Odile', 'Rachel', 'Sylvain', 'Véronique', 'Alphonse',
        'Brigitte', 'Cyrille',
    ];

    private const ORGANISATIONS = [
        null, 'École publique de groupe I', 'Plan International Cameroun',
        'Association des femmes de la Mvila', 'Paroisse Saint-Joseph',
        'Comité de santé communautaire', null,
    ];

    public function run(): void
    {
        $arrondissements = Arrondissement::orderBy('id')->get();
        $superviseurs = User::where('niveau', 'arrondissement')
            ->get()
            ->keyBy('arrondissement_id');

        // Les deux facilitateurs d'Ebolowa II, dont celui de la démonstration.
        $ebolowa2 = $arrondissements->firstWhere('libelle', ComptesSeeder::ARRONDISSEMENT_DEMO);

        $this->facilitateur(0, 'Ndzana Étienne', $ebolowa2, $superviseurs, self::COMPTE_DEMO);
        $this->facilitateur(1, 'Ateba Marie-Claire', $ebolowa2, $superviseurs);

        // Les quarante-huit autres, étalés sur toute la région.
        for ($rang = 2; $rang < 50; $rang++) {
            $nom = self::NOMS[$rang % count(self::NOMS)]
                .' '.self::PRENOMS[($rang * 7) % count(self::PRENOMS)];

            $this->facilitateur($rang, $nom, $arrondissements[$rang % $arrondissements->count()], $superviseurs);
        }
    }

    private function facilitateur(
        int $rang,
        string $nom,
        Arrondissement $arrondissement,
        $superviseurs,
        ?array $demo = null,
    ): void {
        Facilitateur::create([
            'nom' => $nom,
            'telephone' => $demo['telephone'] ?? $this->telephone($rang),
            'code_appareil' => $demo['code_appareil'] ?? str_pad((string) (100000 + ($rang * 7919) % 899999), 6, '0', STR_PAD_LEFT),
            'email' => $demo['email'] ?? Str::slug($nom, '.').'@minproff.cm',
            'password' => $demo['password'] ?? Str::slug($nom).'-'.$rang,
            'arrondissement_id' => $arrondissement->id,
            // Le superviseur de son arrondissement : celui qui l'a enregistré.
            'superviseur_id' => $superviseurs[$arrondissement->id]->id,
            'type_juridique' => TypeJuridique::cases()[$rang % count(TypeJuridique::cases())],
            'organisation_rattachement' => self::ORGANISATIONS[$rang % count(self::ORGANISATIONS)],
            'date_formation_initiale' => $this->formation($rang),
            'derniere_activite' => $this->derniereActivite($rang),
        ]);
    }

    private function telephone(int $rang): string
    {
        $prefixes = ['699', '677', '694', '655', '698', '691', '676', '654', '697', '678', '690', '672'];
        $numero = str_pad((string) (($rang * 104729) % 10000000), 7, '0', STR_PAD_LEFT);

        return sprintf('%s %s %s %s',
            $prefixes[$rang % count($prefixes)],
            substr($numero, 0, 2), substr($numero, 2, 2), substr($numero, 4, 2),
        );
    }

    /** Quatre sessions de formation étalées sur deux ans. */
    private function formation(int $rang): string
    {
        $sessions = ['2024-03-12', '2024-11-05', '2025-02-18', '2025-06-24', '2025-10-09'];

        return $sessions[$rang % count($sessions)];
    }

    /**
     * Six facilitateurs n'ont jamais été actifs, et un tiers ne l'a plus été
     * depuis des mois. C'est ce qui rend le registre parlant.
     */
    private function derniereActivite(int $rang): ?string
    {
        if ($rang % 8 === 3) {
            return null;
        }

        $jours = match ($rang % 5) {
            0 => 3 + $rang % 20,      // actif récemment
            1 => 12 + $rang % 30,
            2 => 40 + $rang % 25,
            3 => 95 + $rang % 90,     // silencieux depuis des mois
            4 => 200 + $rang % 160,
        };

        return Carbon::parse('2026-08-29')->subDays($jours)->toDateString();
    }
}
