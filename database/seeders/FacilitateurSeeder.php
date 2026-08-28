<?php

namespace Database\Seeders;

use App\Models\Facilitateur;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

/**
 * Les quatorze facilitateurs formés du département de la Mvila, répartis sur
 * ses huit arrondissements.
 *
 * Les dates de dernière activité sont volontairement très inégales : c'est
 * exactement ce que le registre doit rendre visible. Aujourd'hui, personne ne
 * sait combien de facilitateurs formés sont encore actifs — trois d'entre eux
 * n'ont jamais tenu la moindre séance depuis leur formation, et le registre
 * doit le dire sans détour.
 */
class FacilitateurSeeder extends Seeder
{
    /**
     * `derniere_activite` à null = formé, jamais actif.
     * Les dates sont relatives à la date de la démonstration.
     */
    private const FACILITATEURS = [
        ['Ndzana Étienne', '699 41 27 08', 'Ebolowa II', '2024-03-12', '2026-08-21'],
        ['Ateba Marie-Claire', '677 30 55 14', 'Ebolowa II', '2024-03-12', '2026-08-14'],
        ['Nkoulou Jean-Pierre', '694 12 88 03', 'Ebolowa I', '2024-03-12', '2026-08-19'],
        ['Owona Bernadette', '655 74 20 91', 'Ebolowa I', '2024-11-05', '2026-07-30'],
        ['Mengue Solange', '698 03 46 77', 'Ngoulemakong', '2024-11-05', '2026-08-11'],
        ['Essomba Pascal', '677 61 09 32', 'Ngoulemakong', '2024-11-05', '2026-02-17'],
        ['Abega Thérèse', '691 88 14 60', 'Mvangan', '2025-02-18', '2026-01-09'],
        ['Bikoé Rodrigue', '676 25 73 45', 'Mvangan', '2025-02-18', null],
        ['Ondoua Célestine', '699 50 31 26', 'Biwong-Bane', '2025-02-18', '2026-06-03'],
        ['Zé Barnabé', '654 19 62 87', 'Biwong-Bulu', '2025-06-24', null],
        ['Amougou Léonie', '697 44 08 51', 'Biwong-Bulu', '2025-06-24', '2026-08-06'],
        ['Ngono Perpétue', '678 92 37 10', 'Mengong', '2025-06-24', '2025-11-22'],
        ['Mvondo Serge', '690 07 55 43', 'Efoulan', '2025-10-09', null],
        ['Belinga Antoinette', '672 36 81 04', 'Efoulan', '2025-10-09', '2026-07-15'],
    ];

    /**
     * Deux voies d'entrée pour le même compte, avec les mêmes droits :
     *
     *   - téléphone + code d'appareil à 6 chiffres, remis en main propre à la
     *     formation, pour ouvrir le kit sur le terrain ;
     *   - e-mail + mot de passe, pour un accès depuis un poste de la délégation.
     *
     * Seul le compte de la cohorte de démonstration est documenté ici. Les
     * identifiants des treize autres sont dérivés et ne sont écrits nulle part.
     */
    public const COMPTE_DEMO = [
        'telephone' => '699 41 27 08',
        'code_appareil' => '481207',
        'email' => 'ndzana.etienne@minproff.cm',
        'password' => 'mvoe-demo',
    ];

    public function run(): void
    {
        foreach (self::FACILITATEURS as $rang => [$nom, $telephone, $arrondissement, $formation, $activite]) {
            $estLeCompteDemo = $telephone === self::COMPTE_DEMO['telephone'];

            Facilitateur::create([
                'nom' => $nom,
                'telephone' => $telephone,
                'code_appareil' => $estLeCompteDemo
                    ? self::COMPTE_DEMO['code_appareil']
                    : str_pad((string) (100000 + ($rang * 7919) % 899999), 6, '0', STR_PAD_LEFT),
                'email' => $estLeCompteDemo
                    ? self::COMPTE_DEMO['email']
                    : Str::slug($nom, '.').'@minproff.cm',
                'password' => $estLeCompteDemo
                    ? self::COMPTE_DEMO['password']
                    : Str::slug($nom).'-'.$rang,
                'arrondissement' => $arrondissement,
                'date_formation' => $formation,
                'derniere_activite' => $activite,
            ]);
        }
    }
}
