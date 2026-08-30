<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\File;
use Tests\TestCase;

/**
 * Le kit facilitateur.
 *
 * Ce que ces tests protègent n'est pas l'apparence des écrans, mais leur
 * architecture : le kit ne doit RIEN recevoir du serveur en dehors de l'API.
 * Si une donnée se met à être rendue côté serveur, le client Blade gagne un
 * privilège que l'application Flutter n'aura pas, et le fonctionnement hors
 * ligne tombe avec.
 */
class KitFacilitateurTest extends TestCase
{
    /** Toutes les coquilles du kit, y compris celles précachées par le SW. */
    private const ECRANS = [
        '/kit',
        '/kit/connexion',
        '/kit/seance?module=8',
        '/kit/pointage',
        '/kit/fidelite',
        '/kit/inscrire',
        '/kit/tableau-de-bord',
        '/kit/activite',
        '/kit/visite',
        '/kit/signaler',
        '/kit/formation',
    ];

    public function test_les_ecrans_du_kit_repondent_sans_authentification_serveur(): void
    {
        // Les routes ne servent que des coquilles : c'est le JavaScript qui
        // redirige vers la connexion s'il n'a pas de jeton en local.
        foreach (self::ECRANS as $ecran) {
            $this->get($ecran)->assertOk();
        }
    }

    public function test_le_service_worker_precache_toutes_les_coquilles_du_kit(): void
    {
        // Une coquille oubliée ici ne se voit qu'en mode avion, c'est-à-dire
        // en séance, là où personne ne peut la réparer.
        $modele = File::get(resource_path('sw/modele.js'));

        preg_match('/const PAGES = \[(.*?)\];/s', $modele, $trouve);

        foreach (self::ECRANS as $ecran) {
            $chemin = explode('?', $ecran)[0];

            $this->assertStringContainsString("'$chemin'", $trouve[1] ?? '',
                "« $chemin » doit être précachée : sans elle, l'écran est blanc hors ligne.");
        }
    }

    public function test_aucune_donnee_metier_nest_rendue_cote_serveur(): void
    {
        // La base est vide dans ce test : si un écran affichait quand même une
        // cohorte ou un module, c'est qu'il les rendrait côté serveur.
        foreach (self::ECRANS as $ecran) {
            $contenu = $this->get($ecran)->getContent();

            $this->assertStringNotContainsString('Discipline positive', $contenu);
            $this->assertStringNotContainsString('Ebolowa', $contenu);
            $this->assertStringNotContainsString('EB2-', $contenu);
        }
    }

    public function test_la_racine_mene_au_kit(): void
    {
        $this->get('/')->assertRedirect('/kit');
    }

    public function test_les_reperes_locaux_sont_purges_au_changement_de_cohorte(): void
    {
        // Changer de cohorte, c'est changer de salle : les repères écrits pour
        // les parents de l'ancienne (« Odile, marché ») ne désignent plus
        // personne. C'est la fin de cycle dont parle le cahier des charges, et
        // le seul moment où elle se produit vraiment sur l'appareil.
        $kit = File::get(resource_path('js/kit.js'));

        $this->assertStringContainsString('await libelles.purger()', $kit);
        $this->assertStringContainsString('changeDeCohorte', $kit);

        // Et re-télécharger LA MÊME cohorte ne purge rien : ce serait perdre
        // ses repères pour une simple mise à jour du paquet.
        $this->assertStringContainsString('this.cohorte.id !== cohorteId', $kit);
    }

    public function test_le_libelle_local_des_parents_nest_jamais_mis_dans_la_file_denvoi(): void
    {
        $magasin = File::get(resource_path('js/magasin.js'));

        // Le libellé local (« Odile, marché ») est une donnée nominative. Il
        // vit sous sa propre clé, et aucune fonction ne doit le recopier dans
        // un événement : ce serait remonter au serveur ce que la loi
        // n° 2024/017 nous interdit de connaître.
        $this->assertStringContainsString("libelles: 'libelles-locaux'", $magasin);

        [, $apresFile] = explode('export const file = {', $magasin, 2);
        $this->assertStringNotContainsString('libelles', $apresFile,
            "La file d'envoi ne doit jamais toucher aux libellés locaux.");
    }

    public function test_la_deconnexion_ne_detruit_ni_le_paquet_ni_la_file(): void
    {
        $magasin = File::get(resource_path('js/magasin.js'));

        [, $fermer] = explode('fermer() {', $magasin, 2);
        [$corps] = explode('},', $fermer, 2);

        // Des séances non remontées survivent à une déconnexion : les perdre
        // serait perdre le travail d'une après-midi entière.
        $this->assertStringNotContainsString('CLES.file', $corps);
        $this->assertStringNotContainsString('CLES.paquet', $corps);
        $this->assertStringNotContainsString('localStorage.clear', $magasin);
    }

    public function test_lecran_de_seance_ecrit_la_trace_douverture_avant_daffichage(): void
    {
        $kit = File::get(resource_path('js/kit.js'));

        [, $ouvrir] = explode('ouvrir(index) {', $kit, 2);
        [$corps] = explode('fermerSequenceCourante() {', $ouvrir, 2);

        // L'ordre compte : on écrit l'événement, PUIS on met l'écran à jour.
        $positionEcriture = strpos($corps, 'file.ajouter');
        $positionAffichage = strpos($corps, 'this.indexCourant = index');

        $this->assertNotFalse($positionEcriture);
        $this->assertNotFalse($positionAffichage);
        $this->assertLessThan($positionAffichage, $positionEcriture,
            "Toute donnée est écrite en local avant d'être affichée à l'écran.");
    }

    public function test_le_brise_glace_naffiche_ni_chronometre_ni_controle(): void
    {
        $vue = File::get(resource_path('views/kit/seance.blade.php'));

        [, $briseGlace] = explode('sequenceCourante.est_brise_glace">', $vue, 2);
        [$bande] = explode('</div>', $briseGlace, 2);

        // C'est le moment où l'outil se retire et où la salle prend le relais.
        $this->assertStringNotContainsString('chrono', $bande);
        $this->assertStringNotContainsString('<button', $bande);
    }
}
