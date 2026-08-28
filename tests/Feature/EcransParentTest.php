<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\File;
use Tests\TestCase;

/**
 * Les écrans de l'espace parent.
 *
 * Ces tests ne protègent pas une apparence : ils protègent les sept règles.
 * Ce sont elles qui rendent cet espace acceptable sur un téléphone partagé,
 * et elles se cassent par petites touches, sans que personne ne le remarque.
 */
class EcransParentTest extends TestCase
{
    private const ECRANS = [
        '/parent',
        '/parent/accueil',
        '/parent/ecouter',
        '/parent/feuilleton',
        '/parent/question',
        '/parent/facilitateur',
    ];

    public function test_tous_les_ecrans_repondent(): void
    {
        foreach (self::ECRANS as $route) {
            $this->get($route)->assertOk();
        }
    }

    public function test_aucune_donnee_nest_rendue_cote_serveur(): void
    {
        foreach (self::ECRANS as $route) {
            $contenu = $this->get($route)->getContent();

            $this->assertStringNotContainsString('Discipline positive', $contenu);
            $this->assertStringNotContainsString('Mama Ngo', $contenu);
            // « EB2-00 » est un exemple de saisie, et le code de personne :
            // aucun code réel ne doit apparaître dans du HTML servi.
            foreach (['EB2-01', 'EB2-04', 'EB2-11', 'EB2-20'] as $codeReel) {
                $this->assertStringNotContainsString($codeReel, $contenu);
            }
        }
    }

    public function test_le_bouton_de_sortie_est_dans_la_coquille_et_non_dans_chaque_ecran(): void
    {
        $coquille = File::get(resource_path('views/components/layouts/parent.blade.php'));

        // Règle 3 : un bouton de sortie visible sur CHAQUE écran. Le placer
        // dans la coquille, c'est se retirer la possibilité d'en oublier un.
        $this->assertStringContainsString('sortir()', $coquille);
        $this->assertStringContainsString('Sortir', $coquille);

        // Et le sélecteur de langue, permanent lui aussi.
        $this->assertStringContainsString('changerLangue(l.code)', $coquille);
    }

    public function test_la_langue_se_choisit_avant_toute_autre_chose(): void
    {
        $js = File::get(resource_path('js/parent.js'));

        // On ne peut pas demander à quelqu'un de lire « Français » dans une
        // langue qu'il n'a pas encore choisie.
        $this->assertStringContainsString("etape: 'langue'", $js);
        $this->assertStringContainsString('ecouterLangue(code)', $js);
    }

    public function test_un_parent_peut_declarer_avoir_moins_de_18_ans(): void
    {
        $vue = File::get(resource_path('views/parent/entree.blade.php'));
        $js = File::get(resource_path('js/parent.js'));

        // Avec une simple case à cocher, déclarer sa minorité est impossible :
        // on ne peut que rester bloqué sans comprendre pourquoi. Or cette
        // déclaration doit ORIENTER vers un facilitateur, pas murer.
        $this->assertStringContainsString("declarerAge('mineur')", $vue);
        $this->assertStringContainsString("this.refusMineur = valeur === 'mineur';", $js);
        $this->assertStringNotContainsString('type="checkbox"', $vue);
    }

    public function test_le_refus_dun_mineur_nest_jamais_un_cul_de_sac(): void
    {
        $vue = File::get(resource_path('views/parent/entree.blade.php'));

        $this->assertStringContainsString('/parent/facilitateur', $vue);
        $this->assertStringContainsString('Trouver un facilitateur', $vue);
    }

    public function test_aucun_score_ni_serie_ni_classement_dans_lespace_parent(): void
    {
        // Règle 4. Ces mots ne doivent apparaître nulle part, pas même dans un
        // libellé anodin : c'est par là que la logique reviendrait.
        foreach (File::allFiles(resource_path('views/parent')) as $vue) {
            $balisage = preg_replace('/\{\{--.*?--\}\}/s', '', $vue->getContents());

            foreach (['score', 'points', 'badge', 'classement', 'félicitations', 'bravo'] as $interdit) {
                $this->assertStringNotContainsStringIgnoringCase($interdit, $balisage,
                    "« $interdit » n'a pas sa place dans l'espace parent ({$vue->getFilename()}).");
            }
        }
    }

    public function test_le_feuilleton_ne_mesure_pas_lassiduite(): void
    {
        $vue = File::get(resource_path('views/parent/feuilleton.blade.php'));
        $js = File::get(resource_path('js/parent.js'));

        // « Sans jamais lui reprocher son absence » : on dit « à reprendre »,
        // pas « 37 % », et surtout pas « vous avez manqué deux épisodes ».
        $this->assertStringContainsString('À reprendre', $vue);
        $this->assertStringNotContainsString('%', preg_replace('/\{\{--.*?--\}\}/s', '', $vue));
        // On inspecte le code, pas les commentaires : ceux-ci décrivent
        // justement ce qu'on s'interdit.
        $code = preg_replace('#/\*.*?\*/|//.*#s', '', $js);
        $this->assertStringNotContainsStringIgnoringCase('manqué', $code);
    }

    public function test_la_position_de_lecture_ne_quitte_jamais_lappareil(): void
    {
        $js = File::get(resource_path('js/parent.js'));

        [, $lecture] = explode('const lecture = {', $js, 2);
        [$corps] = explode('};', $lecture, 2);

        // Le serveur n'a pas à savoir où en est quelqu'un : ce serait un
        // historique de consultation, donc une trace sur un téléphone partagé.
        $this->assertStringContainsString('sessionStorage', $corps);
        $this->assertStringNotContainsString('api.', $corps);
        $this->assertStringNotContainsString('fetch', $corps);
    }

    public function test_la_session_parent_meurt_avec_longlet(): void
    {
        $js = File::get(resource_path('js/parent.js'));

        // Règle 3 : pas de « rester connecté ». Le téléphone est souvent
        // partagé au sein du foyer.
        $this->assertStringContainsString('sessionStorage.setItem', $js);
        $this->assertStringNotContainsString('localStorage', $js);
    }

    public function test_lannuaire_reste_accessible_sans_compte(): void
    {
        $vue = File::get(resource_path('views/parent/facilitateur.blade.php'));

        // Quelqu'un qui a besoin d'un contact humain ne doit pas d'abord se
        // connecter : l'écran n'exige aucune session.
        $this->assertStringContainsString(':barre="false"', $vue);
        $this->assertStringNotContainsString('exigerUneSession', $vue);

        // On borne la découpe au corps d'annuaireParent : les composants
        // suivants exigent une session, et c'est normal.
        $js = File::get(resource_path('js/parent.js'));
        [, $apres] = explode('export function annuaireParent()', $js, 2);
        [$annuaire] = explode('export function', $apres, 2);

        $this->assertStringNotContainsString('exigerUneSession', $annuaire);
    }

    public function test_chaque_libelle_de_laccueil_est_ecoutable(): void
    {
        $vue = File::get(resource_path('views/parent/accueil.blade.php'));
        $config = config('mvoe.audios_interface');

        // Règle 5 : aucun parcours ne dépend de la capacité à lire.
        $this->assertStringContainsString('ecouter(audioCarte(', $vue);

        foreach (['ecouter', 'feuilleton', 'question', 'facilitateur'] as $carte) {
            foreach (['fr', 'en', 'bulu'] as $langue) {
                $this->assertContains("accueil-$carte-$langue", $config);
                $this->assertFileExists(public_path("audio/interface/accueil-$carte-$langue.wav"));
            }
        }
    }
}
