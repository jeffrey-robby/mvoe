<?php

namespace Tests\Feature;

use Tests\TestCase;

/**
 * Contrastes de la palette, calculés selon la formule WCAG 2.1.
 *
 * L'accessibilité est un critère noté du concours, et ces écrans seront lus en
 * plein soleil, dans une salle sans électricité. Les ratios sont calculés ici
 * plutôt que recopiés à la main : un chiffre écrit de mémoire finit toujours
 * par être faux.
 */
class ContrastePaletteTest extends TestCase
{
    private const NOIR = '#121212';

    private const JAUNE = '#F5C518';

    private const JAUNE_SOURD = '#FDF3D0';

    private const BLANC = '#FFFFFF';

    private const GRIS_TEXTE = '#6B6B6B';

    public function test_le_texte_noir_atteint_le_niveau_AAA_sur_toutes_les_surfaces(): void
    {
        // AAA pour du texte normal : 7:1.
        $this->assertGreaterThanOrEqual(7.0, $this->contraste(self::NOIR, self::BLANC));
        $this->assertGreaterThanOrEqual(7.0, $this->contraste(self::NOIR, self::JAUNE));
        $this->assertGreaterThanOrEqual(7.0, $this->contraste(self::NOIR, self::JAUNE_SOURD));
    }

    public function test_le_texte_blanc_atteint_le_niveau_AAA_sur_le_noir(): void
    {
        $this->assertGreaterThanOrEqual(7.0, $this->contraste(self::BLANC, self::NOIR));
    }

    public function test_le_gris_secondaire_atteint_le_niveau_AA_sur_le_blanc(): void
    {
        // AA pour du texte normal : 4.5:1. Le gris ne sert QU'au texte
        // secondaire, jamais à une information indispensable.
        $this->assertGreaterThanOrEqual(4.5, $this->contraste(self::GRIS_TEXTE, self::BLANC));
    }

    public function test_le_jaune_en_texte_sur_blanc_echoue_meme_le_seuil_le_plus_bas(): void
    {
        // La démonstration chiffrée de la règle : le jaune est une surface,
        // jamais une encre. 3:1 est le seuil des grands titres, le plus
        // permissif de la norme — et il n'est même pas atteint.
        $this->assertLessThan(3.0, $this->contraste(self::JAUNE, self::BLANC));
    }

    public function test_la_page_de_demonstration_affiche_les_ratios_reellement_calcules(): void
    {
        $page = $this->get('/design')->assertOk()->getContent();

        foreach ([
            [self::NOIR, self::JAUNE],
            [self::NOIR, self::BLANC],
            [self::GRIS_TEXTE, self::BLANC],
        ] as [$encre, $fond]) {
            $attendu = number_format($this->contraste($encre, $fond), 1, ',', '');

            $this->assertStringContainsString($attendu.':1', $page,
                "Le ratio $encre sur $fond affiché sur la page ne correspond pas au calcul.");
        }
    }

    private function contraste(string $a, string $b): float
    {
        $la = $this->luminance($a);
        $lb = $this->luminance($b);

        return (max($la, $lb) + 0.05) / (min($la, $lb) + 0.05);
    }

    /**
     * Luminance relative, formule WCAG 2.1.
     */
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
