<?php

namespace Database\Seeders;

use App\Models\Langue;
use Illuminate\Database\Seeder;

/**
 * Les langues enregistrées par le ministère.
 *
 * Trois pour la démonstration, mais rien dans le code ne les limite à trois :
 * ajouter le fulfulde ou l'ewondo est une ligne en base et des réalisations à
 * charger, pas un déploiement.
 *
 * L'endonyme — le nom de la langue DANS cette langue — est ce qu'on affiche
 * dans le sélecteur. Personne ne cherche « Bulu » écrit en français quand il ne
 * lit pas le français.
 */
class LangueSeeder extends Seeder
{
    private const LANGUES = [
        ['fr', 'Français', 'Français', 1],
        ['bulu', 'Bulu', 'Bulu', 2],
        ['en', 'Anglais', 'English', 3],
    ];

    public function run(): void
    {
        foreach (self::LANGUES as [$code, $libelle, $endonyme, $ordre]) {
            Langue::create([
                'code' => $code,
                'libelle' => $libelle,
                'endonyme' => $endonyme,
                'actif' => true,
                'ordre' => $ordre,
            ]);
        }
    }
}
