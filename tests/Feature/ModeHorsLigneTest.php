<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\File;
use Tests\TestCase;

/**
 * La couche hors ligne.
 *
 * Le mode avion n'est pas une panne : c'est le mode de travail prévu. Ces
 * tests protègent les quelques règles qui font qu'une séance de quatre-vingt-dix
 * minutes sans réseau ne perd rien et n'affiche rien d'inquiétant.
 */
class ModeHorsLigneTest extends TestCase
{
    public function test_lapplication_est_installable(): void
    {
        $manifeste = json_decode(File::get(public_path('manifest.webmanifest')), true);

        $this->assertSame('/kit', $manifeste['start_url']);
        $this->assertSame('standalone', $manifeste['display']);
        $this->assertNotEmpty($manifeste['icons']);

        foreach ($manifeste['icons'] as $icone) {
            $this->assertFileExists(public_path(ltrim($icone['src'], '/')));
        }

        // Une icône masquable est nécessaire pour un rendu correct sur Android.
        $this->assertContains('maskable', array_column($manifeste['icons'], 'purpose'));
    }

    public function test_le_service_worker_precache_exactement_les_fichiers_du_build(): void
    {
        $sw = File::get(public_path('sw.js'));
        $manifeste = json_decode(File::get(public_path('build/manifest.json')), true);

        // C'est LE test qui compte : une liste de précache périmée laisserait
        // le kit sans style ni script en mode avion, c'est-à-dire précisément
        // là où personne ne peut le réparer.
        foreach ($manifeste as $entree) {
            $this->assertStringContainsString('/build/'.$entree['file'], $sw,
                "Le service worker ne précache pas {$entree['file']} — relancez `npm run build`.");

            foreach ($entree['css'] ?? [] as $css) {
                $this->assertStringContainsString('/build/'.$css, $sw);
            }
        }
    }

    public function test_le_service_worker_precache_tous_les_ecrans_du_kit(): void
    {
        // Ici on vérifie le fichier GÉNÉRÉ, pas le modèle : c'est celui-là que
        // le navigateur installe. `KitFacilitateurTest` vérifie le modèle.
        $sw = File::get(public_path('sw.js'));

        foreach (['/kit', '/kit/connexion', '/kit/seance', '/kit/pointage', '/kit/fidelite',
            '/kit/inscrire', '/kit/tableau-de-bord'] as $page) {
            $this->assertStringContainsString("'$page'", $sw);
        }
    }

    public function test_la_version_du_service_worker_couvre_son_propre_modele(): void
    {
        // La version déclenche la purge du cache sur les appareils déjà
        // installés. Si elle ne dépendait que de la liste des fichiers de build,
        // ajouter une page à précacher ne la changerait pas : le téléphone du
        // facilitateur garderait l'ancien cache et n'aurait jamais la nouvelle
        // page. C'est-à-dire un écran blanc en séance, sans réseau.
        $this->assertStringContainsString(
            '.update(modele)',
            File::get(base_path('scripts/generer-sw.mjs')),
        );
    }

    public function test_lapi_nest_jamais_mise_en_cache(): void
    {
        $sw = File::get(public_path('sw.js'));

        // Une réponse d'API périmée serait pire que pas de réponse : le kit
        // sait travailler hors ligne, il n'a pas besoin qu'on lui mente.
        $this->assertStringContainsString("url.pathname.startsWith('/api/')", $sw);
    }

    public function test_seules_les_reponses_completes_sont_mises_en_cache(): void
    {
        $sw = File::get(public_path('sw.js'));
        $kit = File::get(resource_path('js/kit.js'));

        // Un portail captif renvoie volontiers un 204 vide. Le mettre en cache
        // remplacerait un enregistrement par du silence, définitivement.
        $this->assertStringContainsString('reponse.status === 200', $sw);
        $this->assertStringContainsString('if (reponse.status !== 200) continue;', $kit);
        $this->assertStringContainsString('if (corps.size === 0) continue;', $kit);
    }

    public function test_les_ecritures_locales_sont_aplaties_avant_dentrer_en_base(): void
    {
        $idb = File::get(resource_path('js/idb.js'));

        // IndexedDB refuse les proxies réactifs d'Alpine. Sans cet aplatissement,
        // l'écriture échoue — donc le geste du facilitateur est perdu, en
        // silence et hors ligne, là où il ne peut pas s'en apercevoir.
        $this->assertStringContainsString('function aplatir(', $idb);
        $this->assertStringContainsString('s.put(aplatir(valeur))', $idb);
    }

    public function test_un_evenement_nest_retire_que_sil_a_ete_recu(): void
    {
        $sync = File::get(resource_path('js/synchronisation.js'));

        // Accepté OU doublon : tout le reste est renvoyé. Un envoi coupé au
        // milieu peut donc être rejoué entier sans rien perdre ni dupliquer.
        $this->assertStringContainsString('[...bilan.acceptes, ...bilan.doublons]', $sync);
    }

    public function test_une_session_refusee_renvoie_a_la_connexion_sans_vider_la_file(): void
    {
        // Un 401 n'est pas une panne de réseau : réessayer n'y changera rien, et
        // la file resterait pleine indéfiniment sans que personne ne comprenne.
        // C'est la seule exception à la règle du silence.
        $sync = File::get(resource_path('js/synchronisation.js'));

        $this->assertStringContainsString('e?.statut === 401', $sync);
        $this->assertStringContainsString('/kit/connexion?session=expiree', $sync);

        // Et la déconnexion ne touche jamais la file : c'est la session qui a
        // expiré, pas le travail qui est perdu.
        $magasin = File::get(resource_path('js/magasin.js'));

        $this->assertStringContainsString('jamais le paquet ni la file', $magasin);

        // L'écran de connexion le dit, au lieu de laisser deviner.
        $this->assertStringContainsString('Votre session a expir',
            File::get(resource_path('views/kit/connexion.blade.php')));
    }

    public function test_aucune_erreur_reseau_nest_montree_au_facilitateur(): void
    {
        $sync = File::get(resource_path('js/synchronisation.js'));

        // Les échecs d'envoi ne produisent qu'un état interne et une trace
        // console. Rien qui s'affiche, rien qui alerte.
        $this->assertStringNotContainsString('alert(', $sync);
        $this->assertStringContainsString("annoncer(e instanceof ErreurHorsLigne ? 'hors-ligne' : 'differe')", $sync);
    }

    public function test_la_synchronisation_part_seule_au_retour_du_reseau(): void
    {
        $sync = File::get(resource_path('js/synchronisation.js'));

        // Le facilitateur ne déclenche jamais la remontée, et n'a aucun bouton
        // pour le faire : il n'a pas à savoir ce qu'est une synchronisation.
        $this->assertStringContainsString("window.addEventListener('online'", $sync);
        $this->assertStringContainsString('INTERVALLE_MS', $sync);

        foreach (File::allFiles(resource_path('views/kit')) as $vue) {
            $this->assertStringNotContainsString('Synchroniser', $vue->getContents());
        }
    }

    public function test_le_magasin_est_charge_avant_le_montage_des_ecrans(): void
    {
        $app = File::get(resource_path('js/app.js'));

        // Sans cela, les composants liraient un magasin vide et afficheraient
        // un kit inexistant avant de se corriger sous les yeux du facilitateur.
        $position = [
            'chargement' => strpos($app, 'await ouvrirMagasin()'),
            'montage' => strpos($app, 'Alpine.start()'),
        ];

        $this->assertNotFalse($position['chargement']);
        $this->assertLessThan($position['montage'], $position['chargement']);
    }

    public function test_la_deconnexion_ne_detruit_pas_les_seances_en_attente(): void
    {
        $magasin = File::get(resource_path('js/magasin.js'));

        [, $fermer] = explode('async fermer() {', $magasin, 2);
        [$corps] = explode('};', $fermer, 2);

        // Des séances non remontées survivent à une déconnexion : les perdre
        // serait perdre le travail d'une après-midi entière.
        $this->assertStringNotContainsString('CLES.paquet', $corps);
        $this->assertStringNotContainsString('MAGASINS.file', $corps);
    }
}
