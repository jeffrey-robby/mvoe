<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\File;
use Tests\TestCase;

/**
 * Garde-fous du système de design.
 *
 * Deux systèmes cohabitent, et la frontière entre eux est la chose la plus
 * facile à effacer par distraction : une classe `bg-primary` copiée depuis un
 * écran d'administration vers l'écran de séance, et le kit perd sa lisibilité
 * en plein soleil sans que rien n'échoue.
 */
class SystemeDeDesignTest extends TestCase
{
    /** Le terrain : kit de séance et espace parent. */
    private const VUES_TERRAIN = ['views/kit', 'views/parent'];

    /** L'administration : le template s'y applique. */
    private const VUES_ADMINISTRATION = ['views/superviseur'];

    public function test_le_jaune_nest_jamais_utilise_comme_couleur_de_texte(): void
    {
        // Jaune sur blanc fait 1,6:1. Le jaune est une surface ; le texte qui
        // s'y pose est noir. Cette règle-là n'est pas tombée avec la palette.
        $fautifs = $this->fichiersContenant('/\b(text|decoration)-jaune\b/', ['views']);

        $this->assertSame([], $fautifs,
            'Le jaune est une surface, jamais une encre : '.implode(', ', $fautifs));
    }

    public function test_les_couleurs_pleines_ne_servent_jamais_de_couleur_de_texte(): void
    {
        // Même piège, côté administration : `warning` fait 2,2:1 sur blanc.
        // Les variantes `-texte` existent pour cela.
        $fautifs = $this->fichiersContenant('/\btext-(success|warning|danger)\b(?!-texte)/', ['views']);

        $this->assertSame([], $fautifs,
            'Utilisez les variantes `-texte` : '.implode(', ', $fautifs));
    }

    public function test_le_terrain_nutilise_pas_la_palette_du_template(): void
    {
        // Le kit et l'espace parent gardent le système Mvoé. Le template est
        // dessiné pour une souris et un grand écran ; ces écrans sont tenus
        // d'une main, en plein soleil, dans une salle sans électricité.
        $fautifs = $this->fichiersContenant(
            '/\b(bg|text|border)-(primary|secondary|success|danger|warning|info)\b/',
            self::VUES_TERRAIN,
        );

        $this->assertSame([], $fautifs,
            'Couleur du template dans une vue de terrain : '.implode(', ', $fautifs));
    }

    public function test_les_ecrans_de_terrain_declarent_le_systeme_terrain(): void
    {
        foreach (['kit', 'parent'] as $coquille) {
            $this->assertStringContainsString('terrain',
                File::get(resource_path("views/components/layouts/{$coquille}.blade.php")),
                "La coquille « $coquille » doit activer le système terrain.");
        }
    }

    public function test_ladministration_utilise_les_composants_du_template(): void
    {
        $registre = File::get(resource_path('views/superviseur/registre.blade.php'));

        foreach (['panel', 'tableau', 'badge', 'btn'] as $composant) {
            $this->assertStringContainsString($composant, $registre,
                "L'administration doit reprendre « $composant » du template.");
        }
    }

    public function test_aucune_couleur_hors_des_deux_palettes(): void
    {
        // Ni palette Tailwind par défaut, ni couleur inventée en chemin.
        $fautifs = $this->fichiersContenant(
            '/\b(bg|text|border)-(red|green|blue|orange|amber|emerald|rose|indigo|slate|zinc|gray|grey|teal|cyan|lime|violet|fuchsia|pink)-\d/',
            ['views'],
        );

        $this->assertSame([], $fautifs, 'Couleur hors palette dans : '.implode(', ', $fautifs));
    }

    public function test_aucune_expression_alpine_nest_cassee_par_une_apostrophe(): void
    {
        // Une apostrophe droite non échappée dans une chaîne JS simple-quotée
        // casse l'expression, et Alpine échoue EN SILENCE : le texte n'apparaît
        // jamais, sans la moindre erreur. C'est arrivé une fois ; ce test fait
        // que ça n'arrivera plus.
        $attribut = '/(?:x-text|x-bind:[\w-]+|x-show|x-on:[\w.]+)="([^"]*)"/';
        $apostropheNue = "/(?<!\\\\)'/";
        $suspects = [];

        foreach (File::allFiles(resource_path('views')) as $fichier) {
            preg_match_all($attribut, $fichier->getContents(), $expressions);

            foreach ($expressions[1] as $expression) {
                if (preg_match_all($apostropheNue, $expression) % 2 === 1) {
                    $suspects[] = $fichier->getRelativePathname().' : '.mb_substr($expression, 0, 60);
                }
            }
        }

        $this->assertSame([], $suspects,
            "Apostrophe non échappée dans une expression Alpine :\n".implode("\n", $suspects));
    }

    public function test_la_page_de_demonstration_repond(): void
    {
        $this->get('/design')->assertOk()->assertSee('Système de design');
    }

    public function test_les_trois_etats_du_pointage_portent_tous_un_libelle_ecrit(): void
    {
        $composant = File::get(resource_path('views/components/mvoe/pastille-presence.blade.php'));

        // Jamais la couleur seule comme porteuse d'information.
        $this->assertStringContainsString('Présent', $composant);
        $this->assertStringContainsString('Absent', $composant);
        $this->assertStringContainsString('Binôme', $composant);
    }

    public function test_le_brise_glace_na_ni_bouton_ni_chronometre(): void
    {
        $composant = File::get(resource_path('views/components/mvoe/bande-brise-glace.blade.php'));

        // C'est le moment où l'outil se retire et où la salle prend le relais.
        $this->assertStringNotContainsString('<button', $composant);
        $this->assertStringNotContainsString('x-mvoe.chrono', $composant);
        $this->assertStringNotContainsString('<input', $composant);
    }

    public function test_la_colonne_de_seance_survit_au_template(): void
    {
        $css = File::get(resource_path('css/app.css'));

        // L'élément signature, conservé tel quel : hauteur proportionnelle à la
        // durée, jaune plein en cours, noir passé, contour à venir.
        $this->assertStringContainsString('min-height: calc(var(--minutes', $css);
        $this->assertStringContainsString(".bloc-sequence[data-etat='en_cours']", $css);
        $this->assertStringContainsString('bande-brise-glace', $css);
    }

    /**
     * @param  array<int, string>  $dossiers
     * @return array<int, string>
     */
    private function fichiersContenant(string $motif, array $dossiers): array
    {
        $fautifs = [];

        foreach ($dossiers as $dossier) {
            foreach (File::allFiles(resource_path($dossier)) as $fichier) {
                // On inspecte le balisage, pas les commentaires : ceux-ci
                // parlent justement de ce qu'on s'interdit.
                $balisage = preg_replace('/\{\{--.*?--\}\}/s', '', $fichier->getContents());

                if (preg_match($motif, $balisage)) {
                    $fautifs[] = $fichier->getRelativePathname();
                }
            }
        }

        return $fautifs;
    }
}
