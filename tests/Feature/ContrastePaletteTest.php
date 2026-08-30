<?php

namespace Tests\Feature;

use Tests\TestCase;

/**
 * Contrastes des deux palettes, calculés selon la formule WCAG 2.1.
 *
 * Le projet fait cohabiter deux systèmes : le template Vristo pour
 * l'administration, la palette Mvoé pour le terrain. La contrainte « trois
 * couleurs et pas une de plus » est tombée — celle du contraste, non.
 *
 * Les ratios sont calculés ici plutôt que recopiés : un chiffre écrit de
 * mémoire finit toujours par être faux, et l'accessibilité est un critère noté
 * du concours.
 */
class ContrastePaletteTest extends TestCase
{
    /* Le terrain — kit de séance et espace parent. */
    private const NOIR = '#121212';

    private const JAUNE = '#F5C518';

    private const JAUNE_SOURD = '#FDF3D0';

    private const GRIS_TEXTE = '#6B6B6B';

    /* L'administration — template Vristo. */
    private const BLANC = '#FFFFFF';

    private const FOND = '#FAFAFA';

    private const BLACK = '#0E1726';

    private const PRIMARY = '#4361EE';

    private const WHITE_DARK = '#667292';

    private const SUCCES_TEXTE = '#007A3D';

    private const ALERTE_TEXTE = '#8A5A00';

    private const DANGER_TEXTE = '#C02730';

    /* Les couleurs pleines : des surfaces, jamais du texte. */
    private const SURFACES = [
        'success' => '#00AB55',
        'warning' => '#E2A03F',
        'danger' => '#E7515A',
        'jaune' => self::JAUNE,
    ];

    public function test_le_texte_du_terrain_atteint_le_niveau_AAA(): void
    {
        // AAA pour du texte normal : 7:1. Cet écran est lu en plein soleil.
        $this->assertGreaterThanOrEqual(7.0, $this->contraste(self::NOIR, self::BLANC));
        $this->assertGreaterThanOrEqual(7.0, $this->contraste(self::NOIR, self::JAUNE));
        $this->assertGreaterThanOrEqual(7.0, $this->contraste(self::NOIR, self::JAUNE_SOURD));
        $this->assertGreaterThanOrEqual(7.0, $this->contraste(self::BLANC, self::NOIR));
    }

    public function test_le_texte_de_ladministration_atteint_le_niveau_AA(): void
    {
        // AA pour du texte normal : 4.5:1. Chacune de ces couleurs sert
        // effectivement de couleur de texte quelque part dans l'interface.
        foreach ([
            'black' => self::BLACK,
            'primary' => self::PRIMARY,
            'white-dark' => self::WHITE_DARK,
            'success-texte' => self::SUCCES_TEXTE,
            'warning-texte' => self::ALERTE_TEXTE,
            'danger-texte' => self::DANGER_TEXTE,
        ] as $nom => $couleur) {
            $this->assertGreaterThanOrEqual(4.5, $this->contraste($couleur, self::BLANC),
                "« $nom » sert de couleur de texte et doit passer AA sur blanc.");

            // Le fond de l'administration n'est pas blanc pur.
            $this->assertGreaterThanOrEqual(4.5, $this->contraste($couleur, self::FOND),
                "« $nom » doit aussi passer AA sur le fond de l'administration.");
        }
    }

    public function test_le_gris_secondaire_de_vristo_ne_serait_pas_passe(): void
    {
        // #888ea8, le gris de texte d'origine du template, ne fait que 3,2:1.
        // Ce test documente pourquoi nous l'avons assombri, et empêche qu'on
        // le rétablisse par mégarde en recopiant une valeur du template.
        $this->assertLessThan(4.5, $this->contraste('#888EA8', self::BLANC));
    }

    public function test_les_couleurs_pleines_sont_des_surfaces_et_jamais_du_texte(): void
    {
        // La démonstration chiffrée de la règle, dans les deux palettes :
        // ces couleurs échouent 4.5:1, et `warning` comme le jaune échouent
        // même 3:1, le seuil le plus permissif de la norme.
        foreach (self::SURFACES as $nom => $couleur) {
            $this->assertLessThan(4.5, $this->contraste($couleur, self::BLANC),
                "Si « $nom » passait AA, cette règle n'aurait plus de raison d'être.");
        }

        $this->assertLessThan(3.0, $this->contraste(self::SURFACES['warning'], self::BLANC));
        $this->assertLessThan(3.0, $this->contraste(self::JAUNE, self::BLANC));
    }

    public function test_le_texte_pose_sur_ces_surfaces_reste_lisible(): void
    {
        // Ce qui rend ces couleurs utilisables : du noir ou du blanc dessus.
        $this->assertGreaterThanOrEqual(4.5, $this->contraste(self::NOIR, self::JAUNE));
        $this->assertGreaterThanOrEqual(4.5, $this->contraste(self::BLANC, self::PRIMARY));
        $this->assertGreaterThanOrEqual(4.5, $this->contraste(self::BLANC, self::DANGER_TEXTE));
    }

    private function contraste(string $a, string $b): float
    {
        $la = $this->luminance($a);
        $lb = $this->luminance($b);

        return (max($la, $lb) + 0.05) / (min($la, $lb) + 0.05);
    }

    /** Luminance relative, formule WCAG 2.1. */
    private function luminance(string $hex): float
    {
        [$r, $g, $b] = array_map(
            fn (string $canal) => $this->canal(hexdec($canal) / 255),
            str_split(ltrim($hex, '#'), 2),
        );

        return 0.2126 * $r + 0.7152 * $g + 0.0722 * $b;
    }

    private function canal(float $valeur): float
    {
        return $valeur <= 0.03928
            ? $valeur / 12.92
            : (($valeur + 0.055) / 1.055) ** 2.4;
    }
}
