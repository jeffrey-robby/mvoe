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
    /*
    | NOS vues, et elles seules.
    |
    | Le template a ete colle dans `resources/views` avec ses quatre-vingt-dix
    | pages de demonstration — calendriers, boites mail, facturier. Les scanner
    | reviendrait a faire echouer nos garde-fous sur du code que nous n'ecrivons
    | pas et que nous ne livrons pas.
    */
    private const NOS_VUES = [
        'views/superviseur',
        'views/kit',
        'views/parent',
        'views/components/layouts',
        'views/components/mvoe',
        'views/components/common',
    ];

    /*
    | Les elements SIGNATURE, seuls survivants du systeme terrain.
    |
    | Le kit et l'espace parent sont passes sur la coquille et la palette du
    | template — decision assumee, pour que les trois espaces se ressemblent.
    | Ce qui n'a pas bouge, c'est ce que le cahier des charges protege nommement
    | et ce qu'on manipule debout, en salle.
    */
    private const SIGNATURE = ['bloc-sequence', 'bande-brise-glace', 'pastille'];

    public function test_le_jaune_nest_jamais_utilise_comme_couleur_de_texte(): void
    {
        // Jaune sur blanc fait 1,6:1. Le jaune est une surface ; le texte qui
        // s'y pose est noir. Cette règle-là n'est pas tombée avec la palette.
        $fautifs = $this->fichiersContenant('/\b(text|decoration)-jaune\b/', ['views']);

        $this->assertSame([], $fautifs,
            'Le jaune est une surface, jamais une encre : '.implode(', ', $fautifs));
    }

    public function test_une_couleur_ne_porte_jamais_seule_une_information(): void
    {
        /*
        | Le `warning` du template fait 2,2:1 sur blanc : illisible en corps de
        | texte. Nous l'employons quand meme, mais sur des chiffres et des
        | badges, jamais sans le mot a cote. C'est la regle qui reste
        | verifiable : partout ou un statut est colore, il est aussi ecrit.
        |
        | Les variantes `-texte` ont disparu avec le passage a la palette du
        | template. Ce garde-fou est donc ABAISSE en conscience : le contraste
        | des chiffres colores reste a verifier a l'oeil.
        */
        $registre = File::get(resource_path('views/superviseur/registre.blade.php'));

        $this->assertStringContainsString("f.actif ? 'Actif' : 'Inactif'", $registre,
            'Un statut colore doit toujours porter son mot.');

        $pastille = File::get(resource_path('views/components/mvoe/pastille-presence.blade.php'));

        foreach (['sent', 'Absent', 'me'] as $fragment) {
            $this->assertStringContainsString($fragment, $pastille,
                'Le pointage doit ecrire ses trois etats, pas seulement les colorer.');
        }
    }

    public function test_la_palette_terrain_ne_fuit_pas_dans_ladministration(): void
    {
        /*
        | La frontiere a change de sens.
        |
        | Le kit et l'espace parent utilisent desormais la palette du template :
        | decision assumee, pour que les trois espaces se ressemblent. Ce qui
        | reste interdit, c'est l'inverse — le jaune plein et le noir plein
        | appartiennent aux elements signature du terrain. Les voir dans un
        | ecran de delegation signifierait qu'on y a copie un bloc de seance.
        */
        $fautifs = $this->fichiersContenant(
            '/\b(bg|border)-(jaune|noir)\b/',
            ['views/superviseur'],
        );

        $this->assertSame([], $fautifs,
            'Palette de terrain dans un ecran de delegation : '.implode(', ', $fautifs));
    }

    public function test_les_ecrans_de_terrain_declarent_le_systeme_terrain(): void
    {
        // 17 px de corps et un contour de focus epais : un ecran tenu d'une
        // main en plein soleil ne se lit pas avec la densite d'un bureau.
        foreach (['kit', 'parent'] as $coquille) {
            $this->assertStringContainsString(':terrain="true"',
                File::get(resource_path("views/components/layouts/{$coquille}.blade.php")),
                "La coquille « $coquille » doit activer le systeme terrain.");
        }

        $this->assertStringContainsString('.terrain {',
            File::get(resource_path('css/kit.css')),
            'La feuille du kit doit definir le systeme terrain.');
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
        // `slate-400` fait exception : c'est le gris des badges neutres du
        // template, et la palette Mvoé n'a pas d'équivalent.
        $fautifs = $this->fichiersContenant(
            '/\b(bg|text|border)-(red|green|blue|orange|amber|emerald|rose|indigo|zinc|gray|grey|teal|cyan|lime|violet|fuchsia|pink)-\d/',
            self::NOS_VUES,
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
        // Les regles vivent dans la couche Mvoe, importee par les deux bundles.
        $css = File::get(resource_path('css/mvoe.css'));

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
