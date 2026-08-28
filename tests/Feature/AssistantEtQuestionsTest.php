<?php

namespace Tests\Feature;

use App\Models\Appariement;
use Illuminate\Support\Facades\File;
use Tests\ApiTestCase;

/**
 * L'assistant à corpus fermé et les questions de la semaine.
 *
 * Deux écrans, deux règles qu'aucune évolution ne doit éroder : l'assistant
 * RETROUVE et ne rédige jamais, et les questions n'affichent aucun verdict.
 */
class AssistantEtQuestionsTest extends ApiTestCase
{
    public function test_les_deux_ecrans_repondent(): void
    {
        $this->get('/parent/question')->assertOk();
        $this->get('/parent/questions')->assertOk();
    }

    /* ---------------------------------------------------------------- */
    /* L'assistant                                                       */
    /* ---------------------------------------------------------------- */

    public function test_une_situation_couverte_restitue_une_unite_mot_pour_mot(): void
    {
        $jeton = $this->jetonParent();

        $situation = collect($this->getJson('/api/parent/situations?langue=fr', $this->entete($jeton))
            ->json('situations'))
            ->firstWhere('libelle', "Mon enfant recommence dès que j'ai le dos tourné");

        $reponse = $this->postJson('/api/parent/assistant',
            ['situation_id' => $situation['id']], $this->entete($jeton))->assertOk();

        $this->assertTrue($reponse->json('trouve'));

        // Aucune phrase n'est composée : le texte lu existe tel quel en base.
        $this->assertDatabaseHas('unites_digitales', ['message_cle' => $reponse->json('reponse')]);
        $this->assertStringContainsString('Module 8', $reponse->json('reference'));
    }

    public function test_les_quatre_situations_hors_corpus_sont_toutes_refusees(): void
    {
        $jeton = $this->jetonParent();

        $situations = collect($this->getJson('/api/parent/situations?langue=fr', $this->entete($jeton))
            ->json('situations'));

        // Par construction du jeu de démonstration, les situations 9 à 12
        // relèvent de modules encore vides ou sortent du programme.
        foreach ($situations->slice(8) as $situation) {
            $reponse = $this->postJson('/api/parent/assistant',
                ['situation_id' => $situation['id']], $this->entete($jeton))->assertOk();

            $this->assertFalse($reponse->json('trouve'),
                "« {$situation['libelle']} » ne devrait pas trouver de réponse.");

            // Un refus n'est jamais une impasse.
            $this->assertNotEmpty($reponse->json('contacts'));
        }
    }

    public function test_le_refus_est_traite_comme_un_resultat_et_non_comme_une_erreur(): void
    {
        $vue = File::get(resource_path('views/parent/question.blade.php'));
        $balisage = preg_replace('/\{\{--.*?--\}\}/s', '', $vue);

        // Ni excuse, ni vocabulaire d'échec : sur un sujet de protection de
        // l'enfance, savoir dire qu'on ne sait pas est une fonctionnalité.
        foreach (['erreur de', 'désolé', 'échec', 'impossible de répondre'] as $interdit) {
            $this->assertStringNotContainsStringIgnoringCase($interdit, $balisage);
        }

        // Le refus affiche des contacts appelables.
        $this->assertStringContainsString('lienTelephone(c.telephone)', $balisage);
        $this->assertStringContainsString('Un facilitateur peut vous répondre', $balisage);
    }

    public function test_lassistant_journalise_sans_jamais_identifier_le_parent(): void
    {
        $jeton = $this->jetonParent();

        $this->postJson('/api/parent/assistant',
            ['texte' => 'mon mari rentre tard et crie sur les enfants'], $this->entete($jeton));

        $this->assertSame(1, Appariement::count());
        $this->assertNotContains('parent_id',
            \Illuminate\Support\Facades\Schema::getColumnListing('appariements'));

        // Un refus est enregistré comme tel : c'est ce qui permet d'améliorer
        // le corpus, et cela seulement.
        $this->assertNull(Appariement::first()->unite_id);
    }

    public function test_aucun_appel_a_un_modele_de_langage(): void
    {
        $service = File::get(app_path('Services/AppariementCorpus.php'));
        $js = File::get(resource_path('js/parent.js'));

        foreach (['openai', 'anthropic', 'gpt', 'llm', 'gemini', 'mistral'] as $interdit) {
            $this->assertStringNotContainsStringIgnoringCase($interdit, $service);
            $this->assertStringNotContainsStringIgnoringCase($interdit, $js);
        }

        // L'assistant compare des mots, il n'en génère aucun.
        $this->assertStringContainsString('message_cle', $service);
    }

    /* ---------------------------------------------------------------- */
    /* Les questions de la semaine                                       */
    /* ---------------------------------------------------------------- */

    public function test_repondre_ne_renvoie_ni_verdict_ni_score(): void
    {
        $jeton = $this->jetonParent();
        $question = $this->getJson('/api/parent/questions', $this->entete($jeton))->json('questions.0');

        // On choisit délibérément l'option que le programme ne recommande pas.
        $reponse = $this->postJson(
            '/api/parent/questions/'.$question['id'].'/reponse',
            ['option_id' => $question['options'][0]['id']],
            $this->entete($jeton),
        )->assertOk();

        $this->assertSame(['question_id', 'explication', 'reference'], array_keys($reponse->json()));
    }

    public function test_lexplication_est_la_meme_quel_que_soit_le_choix(): void
    {
        $jeton = $this->jetonParent();
        $question = $this->getJson('/api/parent/questions', $this->entete($jeton))->json('questions.0');

        $explications = collect($question['options'])->map(function (array $option) use ($jeton, $question) {
            $this->app['auth']->forgetGuards();

            return $this->postJson(
                '/api/parent/questions/'.$question['id'].'/reponse',
                ['option_id' => $option['id']],
                $this->entete($jeton),
            )->json('explication');
        });

        // C'est ce qui rend l'absence de verdict structurelle : le texte lu ne
        // peut pas varier selon la réponse, puisqu'il est porté par la question.
        $this->assertCount(1, $explications->unique());
    }

    public function test_lecran_des_questions_naffiche_aucun_total(): void
    {
        $vue = File::get(resource_path('views/parent/questions.blade.php'));
        $balisage = preg_replace('/\{\{--.*?--\}\}/s', '', $vue);

        foreach (['bonne réponse', 'mauvaise', 'juste', 'faux', 'total', 'résultat'] as $interdit) {
            $this->assertStringNotContainsStringIgnoringCase($interdit, $balisage,
                "« $interdit » n'a pas sa place dans les questions de la semaine.");
        }

        // La fin ne dresse aucun bilan.
        $this->assertStringContainsString("C'est tout pour cette semaine.", $balisage);
    }

    public function test_la_bonne_reponse_ne_sort_jamais_de_lapi(): void
    {
        $contenu = $this->getJson('/api/parent/questions', $this->entete($this->jetonParent()))
            ->getContent();

        $this->assertStringNotContainsString('est_attendue', $contenu);
    }

    public function test_les_questions_sont_accessibles_depuis_laccueil_et_ecoutables(): void
    {
        $accueil = File::get(resource_path('views/parent/accueil.blade.php'));

        // En lien discret et non en quatrième carte : le cahier des charges en
        // prescrit trois, et les questions figurent en deuxième position sur la
        // liste de ce qu'on coupe si le temps manque.
        $this->assertStringContainsString('/parent/questions', $accueil);
        $this->assertStringContainsString("audioCarte('questions')", $accueil);

        foreach (['fr', 'en', 'bulu'] as $langue) {
            $this->assertFileExists(public_path("audio/interface/accueil-questions-$langue.wav"));
        }
    }
}
