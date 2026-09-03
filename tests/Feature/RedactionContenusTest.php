<?php

namespace Tests\Feature;

use App\Enums\StatutValidation;
use App\Models\Cohorte;
use App\Models\Langue;
use App\Models\Module;
use App\Models\ModuleFormation;
use App\Models\Realisation;
use App\Models\UniteDigitale;
use Tests\ApiTestCase;

/**
 * Le ministère écrit ses contenus depuis l'interface.
 *
 * Sans cet écran, le catalogue national venait entièrement des seeders : c'est
 * l'équipe technique qui produisait le curriculum. Le MINPROFF « produit et
 * valide LES CONTENUS » — les deux verbes, pas seulement le second.
 *
 * La règle qui traverse tous ces tests : **rien ne naît validé.** Créer et
 * valider sont deux gestes, et un contenu non validé n'atteint personne.
 */
class RedactionContenusTest extends ApiTestCase
{
    public function test_le_referentiel_vient_de_la_base_et_pas_du_javascript(): void
    {
        $reponse = $this->getJson('/api/superviseur/contenus/referentiel',
            $this->entete($this->jetonNational()))->assertOk();

        // Les modules du curriculum, avec leurs séquences : un formulaire ne
        // peut pas rattacher une unité à une séquence qu'il aurait inventée.
        $this->assertNotEmpty($reponse->json('modules'));
        $this->assertNotEmpty($reponse->json('langues'));
        $this->assertCount(3, $reponse->json('types_formation'));

        // Le module 8 est le seul complet dans les données de démonstration.
        $module8 = collect($reponse->json('modules'))->firstWhere('numero', 8);

        $this->assertNotNull($module8);
        $this->assertCount(5, $module8['sequences']);
        $this->assertTrue(collect($module8['sequences'])->contains('est_brise_glace', true));
    }

    public function test_seul_le_national_redige_les_contenus(): void
    {
        // Une délégation qui rédigerait ses propres unités produirait dix
        // curriculums différents, et le programme cesserait d'être national.
        foreach ([$this->jetonRegional(), $this->jetonSuperviseurArrondissement()] as $jeton) {
            $this->getJson('/api/superviseur/contenus/referentiel', $this->entete($jeton))
                ->assertForbidden();

            $this->postJson('/api/superviseur/contenus/unites', [
                'module_id' => Module::first()->id,
                'sequence_id' => Module::first()->sequences()->first()->id,
                'message_cle' => 'Un texte écrit par une délégation.',
            ], $this->entete($jeton))->assertForbidden();
        }
    }

    public function test_une_unite_se_redige_et_recoit_ses_realisations(): void
    {
        $jeton = $this->jetonNational();
        $module = Module::where('numero', 8)->firstOrFail();
        $sequence = $module->sequences()->first();

        $unite = $this->postJson('/api/superviseur/contenus/unites', [
            'module_id' => $module->id,
            'sequence_id' => $sequence->id,
            'message_cle' => 'Un enfant qui pleure dit ce qu il ne sait pas nommer.',
        ], $this->entete($jeton))->assertCreated()->json('unite');

        // La référence est ce qui rend une réponse d'assistant vérifiable.
        $this->assertStringContainsString('Module 8', $unite['reference']);
        $this->assertSame([], $unite['realisations']);

        $bulu = Langue::where('code', 'bulu')->firstOrFail();

        $reponse = $this->postJson("/api/superviseur/contenus/unites/{$unite['id']}/realisations", [
            'langue_id' => $bulu->id,
            'modalite' => 'audio',
            'titre' => 'Nge mon a yen',
            'fichier_audio' => 'audio/unites/m08-u99-bulu.wav',
        ], $this->entete($jeton))->assertCreated();

        // NAÎT EN BROUILLON. C'est toute la règle.
        $this->assertSame('brouillon', $reponse->json('realisation.statut_validation'));
        $this->assertFalse($reponse->json('realisation.diffusable'));
    }

    public function test_une_realisation_sans_texte_ni_audio_est_refusee(): void
    {
        $jeton = $this->jetonNational();
        $unite = UniteDigitale::first();

        // Elle gonflerait la couverture en laissant croire que la langue est
        // servie — pire que l'absence, parce que personne n'irait la chercher.
        $this->postJson("/api/superviseur/contenus/unites/{$unite->id}/realisations", [
            'langue_id' => Langue::where('code', 'en')->first()->id,
            'modalite' => 'audio',
            'titre' => 'Un titre seul',
        ], $this->entete($jeton))->assertStatus(422);
    }

    public function test_une_sequence_d_un_autre_module_est_refusee(): void
    {
        $jeton = $this->jetonNational();
        $module = Module::where('numero', 8)->firstOrFail();
        $autre = Module::where('id', '!=', $module->id)
            ->whereHas('sequences')->firstOrFail();

        $this->postJson('/api/superviseur/contenus/unites', [
            'module_id' => $module->id,
            'sequence_id' => $autre->sequences()->first()->id,
            'message_cle' => 'Une unité mal rattachée.',
        ], $this->entete($jeton))->assertStatus(422);
    }

    public function test_un_brouillon_n_atteint_ni_le_parent_ni_le_paquet(): void
    {
        $jeton = $this->jetonNational();
        $module = Module::where('numero', 8)->firstOrFail();
        $sequence = $module->sequences()->first();
        $francais = Langue::where('code', 'fr')->firstOrFail();

        $unite = $this->postJson('/api/superviseur/contenus/unites', [
            'module_id' => $module->id,
            'sequence_id' => $sequence->id,
            'message_cle' => 'Un texte qui ne doit pas encore sortir.',
        ], $this->entete($jeton))->json('unite');

        $this->postJson("/api/superviseur/contenus/unites/{$unite['id']}/realisations", [
            'langue_id' => $francais->id,
            'modalite' => 'texte_picto',
            'titre' => 'Brouillon en cours de relecture',
            'contenu_texte' => 'Ce texte est en cours de relecture.',
        ], $this->entete($jeton))->assertCreated();

        // Le paquet de cohorte part hors ligne dans un téléphone : un brouillon
        // qui y entre n'en ressort plus.
        $cohorte = Cohorte::whereHas('facilitateur')->firstOrFail();

        $paquet = $this->getJson("/api/facilitateur/cohortes/{$cohorte->id}/paquet",
            $this->entete($this->jetonFacilitateur()));

        if ($paquet->status() === 200) {
            $this->assertStringNotContainsString(
                'Brouillon en cours de relecture', $paquet->getContent(),
            );
        }

        // L'espace parent non plus.
        $vue = $this->getJson("/api/parent/unites/{$unite['id']}?langue=fr");

        $this->assertNull($vue->json('realisation'));
    }

    public function test_la_file_de_validation_montre_les_realisations_en_attente(): void
    {
        $jeton = $this->jetonNational();

        // Une réalisation en brouillon qui n'apparaîtrait nulle part resterait
        // en base sans jamais atteindre un parent.
        $realisation = Realisation::first();
        $realisation->update(['statut_validation' => StatutValidation::Brouillon]);

        $file = $this->getJson('/api/superviseur/bibliotheque', $this->entete($jeton))
            ->assertOk()->json('realisations_en_attente');

        $this->assertCount(1, $file);
        $this->assertSame($realisation->id, $file[0]['id']);

        // Et elle se valide depuis là.
        $this->patchJson("/api/superviseur/contenus/realisations/{$realisation->id}", [
            'statut_validation' => 'valide',
        ], $this->entete($jeton))->assertOk()
            ->assertJsonPath('realisation.diffusable', true);

        $this->assertEmpty(
            $this->getJson('/api/superviseur/bibliotheque', $this->entete($jeton))
                ->json('realisations_en_attente'),
        );
    }

    public function test_un_module_de_formation_se_cree_puis_se_remplit(): void
    {
        $jeton = $this->jetonNational();

        $module = $this->postJson('/api/superviseur/contenus/modules-formation', [
            'code' => 'RN-99',
            'titre' => 'Recevoir une révélation sans la trahir',
            'type' => 'conduite_a_tenir',
            'objectif' => 'Savoir quoi dire dans l heure qui suit une révélation.',
            'duree_minutes' => 25,
        ], $this->entete($jeton))->assertCreated()->json('module');

        $this->assertSame('brouillon', $module['statut_validation']);
        $this->assertSame(0, $module['sections']);

        // Un module sans section n'atteint aucun facilitateur, même validé.
        $this->assertNotContains('RN-99', collect(
            $this->getJson('/api/facilitateur/formation', $this->entete($this->jetonFacilitateur()))
                ->json('modules') ?? [],
        )->pluck('code')->all());

        $section = $this->postJson('/api/superviseur/contenus/modules-formation/RN-99/sections', [
            'titre' => 'Ce qu il ne faut jamais promettre',
            'contenu_texte' => 'Ne promettez pas le secret. Vous ne pourrez pas le tenir.',
            'duree_minutes' => 8,
        ], $this->entete($jeton))->assertCreated();

        $this->assertSame(1, $section->json('section.ordre'));
        // L'audio manque : l'interface reste utilisable, elle bascule au texte.
        $this->assertFalse($section->json('section.audio_disponible'));
        $this->assertSame(1, $section->json('module.sections'));
    }

    public function test_modifier_un_module_diffuse_le_renvoie_en_brouillon(): void
    {
        $jeton = $this->jetonNational();

        $module = ModuleFormation::where('statut_validation', StatutValidation::Valide->value)
            ->firstOrFail();

        $reponse = $this->postJson(
            "/api/superviseur/contenus/modules-formation/{$module->code}/sections", [
                'titre' => 'Une section ajoutée après coup',
                'contenu_texte' => 'Ce texte n a pas encore été relu.',
                'duree_minutes' => 5,
            ], $this->entete($jeton))->assertCreated();

        // Sans cela, la validation ne porterait que sur la première version.
        $this->assertFalse($reponse->json('module.diffusable'));
        $this->assertSame('brouillon', $reponse->json('module.statut_validation'));
    }

    public function test_un_code_de_module_ne_se_reutilise_pas(): void
    {
        $jeton = $this->jetonNational();
        $existant = ModuleFormation::first();

        $this->postJson('/api/superviseur/contenus/modules-formation', [
            'code' => $existant->code,
            'titre' => 'Un doublon',
            'type' => 'remise_a_niveau',
            'objectif' => 'Rien.',
            'duree_minutes' => 10,
        ], $this->entete($jeton))->assertStatus(422)->assertJsonValidationErrors('code');
    }
}
