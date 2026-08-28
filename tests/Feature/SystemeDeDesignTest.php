<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\File;
use Tests\TestCase;

/**
 * Garde-fous du système de design.
 *
 * L'accessibilité est un critère noté du concours, et ces règles sont trop
 * faciles à casser par distraction : un `text-jaune` posé un soir de fatigue
 * ne se voit pas en relecture, mais il rend un écran illisible en plein soleil.
 * Ces tests le voient à notre place.
 */
class SystemeDeDesignTest extends TestCase
{
    public function test_le_jaune_nest_jamais_utilise_comme_couleur_de_texte(): void
    {
        // Jaune sur blanc échoue tous les seuils de contraste. Le jaune est
        // une surface ; le texte qui s'y pose est noir.
        $fautifs = $this->fichiersContenant('/\b(text|border-t|decoration)-jaune\b/');

        $this->assertSame([], $fautifs,
            'Le jaune est une surface, jamais une encre. Fichiers en cause : '.implode(', ', $fautifs));
    }

    public function test_aucune_quatrieme_couleur_ne_sest_glissee_dans_les_vues(): void
    {
        // Palette imposée : jaune, blanc, noir. Pas de rouge « erreur », pas de
        // vert « succès » : un état se dit par une forme et par un mot.
        $fautifs = $this->fichiersContenant(
            '/\b(bg|text|border)-(red|green|blue|orange|amber|emerald|rose|indigo|slate|zinc|gray|grey)-\d/'
        );

        $this->assertSame([], $fautifs,
            'Couleur hors palette dans : '.implode(', ', $fautifs));
    }

    public function test_la_page_de_demonstration_repond(): void
    {
        $this->get('/design')
            ->assertOk()
            ->assertSee('Système de design Mvoé')
            // La règle de contraste est écrite sur la page elle-même : elle
            // doit être lue par quiconque reprend le projet.
            ->assertSee("Le jaune n'est jamais une couleur de texte.", false);
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

    /**
     * @return array<int, string>
     */
    private function fichiersContenant(string $motif): array
    {
        $fautifs = [];

        foreach (File::allFiles(resource_path('views')) as $fichier) {
            if (preg_match($motif, $fichier->getContents())) {
                $fautifs[] = $fichier->getRelativePathname();
            }
        }

        return $fautifs;
    }
}
