<?php

namespace Tests\Feature\Api;

use App\Models\Appariement;
use Tests\ApiTestCase;

/**
 * L'espace parent, et surtout ce qu'il ne fait pas.
 *
 * Ces tests protègent des règles de conformité, pas des fonctionnalités :
 * aucun score, aucun verdict, aucune bonne réponse exposée, aucun profilage,
 * et un refus soigné quand le corpus ne couvre pas la question.
 */
class EspaceParentApiTest extends ApiTestCase
{
    public function test_lassistant_restitue_une_unite_validee_avec_sa_reference(): void
    {
        $reponse = $this->postJson('/api/parent/assistant', [
            'texte' => "mon enfant recommence dès que j'ai le dos tourné",
        ], $this->entete($this->jetonParent()))->assertOk();

        $this->assertTrue($reponse->json('trouve'));
        $this->assertStringContainsString('Module 8', $reponse->json('reference'));

        // Restitué MOT POUR MOT depuis le corpus : aucune phrase composée.
        $this->assertDatabaseHas('unites_digitales', ['message_cle' => $reponse->json('reponse')]);
    }

    public function test_lassistant_refuse_hors_corpus_et_propose_un_contact_humain(): void
    {
        $reponse = $this->postJson('/api/parent/assistant', [
            'texte' => 'mon enfant tousse depuis trois jours et ne mange plus',
        ], $this->entete($this->jetonParent()))->assertOk();

        // Un refus est un résultat, pas une panne : 200, pas 404.
        $this->assertFalse($reponse->json('trouve'));
        $this->assertLessThan($reponse->json('seuil'), $reponse->json('score'));
        $this->assertNotEmpty($reponse->json('contacts'));
        $this->assertArrayNotHasKey('reponse', $reponse->json());
    }

    public function test_le_refus_propose_toujours_au_moins_un_facilitateur(): void
    {
        $reponse = $this->postJson('/api/parent/assistant', [
            'texte' => 'je ne trouve pas de quoi payer la rentrée scolaire',
        ], $this->entete($this->jetonParent()))->assertOk();

        $this->assertFalse($reponse->json('trouve'));
        $this->assertNotEmpty($reponse->json('contacts'), 'Un refus sans contact serait une impasse.');
    }

    public function test_le_journal_de_lassistant_ne_porte_aucun_identifiant_de_parent(): void
    {
        $this->postJson('/api/parent/assistant', ['texte' => 'mon enfant a peur de moi'],
            $this->entete($this->jetonParent()));

        $colonnes = \Illuminate\Support\Facades\Schema::getColumnListing('appariements');

        $this->assertNotContains('parent_id', $colonnes);
        $this->assertSame(1, Appariement::count());
    }

    public function test_les_questions_de_la_semaine_nexposent_jamais_la_bonne_reponse(): void
    {
        $reponse = $this->getJson('/api/parent/questions', $this->entete($this->jetonParent()))->assertOk();

        $this->assertStringNotContainsString('est_attendue', $reponse->getContent());
    }

    public function test_repondre_a_une_question_ne_renvoie_ni_score_ni_verdict(): void
    {
        $jeton = $this->jetonParent();
        $question = $this->getJson('/api/parent/questions', $this->entete($jeton))->json('questions.0');

        $reponse = $this->postJson(
            '/api/parent/questions/'.$question['id'].'/reponse',
            ['option_id' => $question['options'][0]['id']],
            $this->entete($jeton),
        )->assertOk();

        $this->assertSame(['question_id', 'explication', 'reference'], array_keys($reponse->json()));
    }

    public function test_les_reponses_sont_comptees_sans_quon_sache_qui_a_repondu(): void
    {
        $jeton = $this->jetonParent();
        $question = $this->getJson('/api/parent/questions', $this->entete($jeton))->json('questions.0');
        $optionId = $question['options'][0]['id'];

        $avant = \App\Models\ReponseAgregee::where('option_id', $optionId)->value('compteur');

        $this->postJson('/api/parent/questions/'.$question['id'].'/reponse',
            ['option_id' => $optionId], $this->entete($jeton));

        $this->assertSame($avant + 1, \App\Models\ReponseAgregee::where('option_id', $optionId)->value('compteur'));
        $this->assertNotContains('parent_id',
            \Illuminate\Support\Facades\Schema::getColumnListing('reponses_agregees'));
    }

    public function test_une_unite_se_lit_en_bulu_puis_bascule_en_texte_et_pictogrammes(): void
    {
        $jeton = $this->jetonParent();

        $audio = $this->getJson('/api/parent/unites/1?langue=bulu&modalite=audio', $this->entete($jeton))->assertOk();

        // Une langue est désormais une donnée, pas un code figé dans le code :
        // l'API en rend le code ET le nom à afficher.
        $this->assertSame('bulu', $audio->json('langue_servie.code'));
        $this->assertFalse($audio->json('langue_de_repli'));
        $this->assertNotNull($audio->json('realisation.fichier_audio'));

        $texte = $this->getJson('/api/parent/unites/1?langue=bulu&modalite=texte_picto', $this->entete($jeton))->assertOk();
        $this->assertNotNull($texte->json('realisation.contenu_texte'));
        $this->assertNotEmpty($texte->json('realisation.pictogrammes'));
    }

    public function test_le_feuilleton_ne_renvoie_aucune_position_de_lecture(): void
    {
        $reponse = $this->getJson('/api/parent/feuilletons?langue=bulu', $this->entete($this->jetonParent()))
            ->assertOk();

        // La reprise vit dans le navigateur du parent : le serveur ne tient
        // aucun historique de consultation.
        $this->assertStringNotContainsString('position', $reponse->getContent());
        $this->assertCount(4, $reponse->json('feuilletons.0.episodes'));
    }

    public function test_lannuaire_est_accessible_sans_aucun_compte(): void
    {
        $this->getJson('/api/arrondissements')->assertOk();

        $this->getJson('/api/annuaire?arrondissement=Ebolowa+II')
            ->assertOk()
            ->assertJsonPath('repli_departement', false);
    }

    public function test_lannuaire_ne_renvoie_jamais_une_liste_vide(): void
    {
        // On construit la situation plutôt que de compter sur le jeu de
        // données : tous les facilitateurs de Mengong deviennent inactifs.
        $mengong = \App\Models\Arrondissement::where('libelle', 'Mengong')->firstOrFail();

        \App\Models\Facilitateur::where('arrondissement_id', $mengong->id)
            ->update(['derniere_activite' => null]);

        // Un arrondissement sans facilitateur joignable est exactement ce que
        // le registre doit révéler — et quelqu'un qui cherche de l'aide ne peut
        // pas être renvoyé à rien pour autant.
        $reponse = $this->getJson('/api/annuaire?arrondissement=Mengong')->assertOk();

        $this->assertTrue($reponse->json('repli_departement'));
        $this->assertNotEmpty($reponse->json('contacts'));
    }
}
