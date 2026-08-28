<?php

namespace Tests\Feature\Api;

use Tests\ApiTestCase;

/**
 * Le paquet de cohorte : un seul téléchargement, puis tout fonctionne hors
 * ligne. Ces tests protègent surtout ce qui NE DOIT PAS s'y trouver.
 */
class PaquetCohorteApiTest extends ApiTestCase
{
    public function test_le_paquet_contient_tout_le_curriculum_et_les_codes_parents(): void
    {
        $paquet = $this->getJson('/api/facilitateur/cohortes/1/paquet', $this->entete($this->jetonFacilitateur()))
            ->assertOk()
            ->json();

        $this->assertCount(10, $paquet['modules']);
        $this->assertCount(20, $paquet['parents']);
        $this->assertCount(4, $paquet['binomes']);

        $module8 = collect($paquet['modules'])->firstWhere('numero', 8);
        $this->assertCount(5, $module8['sequences']);
        $this->assertSame(90, $module8['duree_totale_minutes']);

        $unites = collect($module8['sequences'])->flatMap->unites;
        $this->assertCount(6, $unites);
        $this->assertCount(4, $unites->first()['realisations']);
    }

    public function test_le_paquet_ne_contient_aucun_code_dacces_de_parent(): void
    {
        $contenu = $this->getJson('/api/facilitateur/cohortes/1/paquet', $this->entete($this->jetonFacilitateur()))
            ->assertOk()
            ->getContent();

        // Un appareil perdu ne doit pas ouvrir vingt espaces parents.
        $this->assertStringNotContainsString('code_acces', $contenu);
    }

    public function test_le_paquet_signale_les_modules_encore_vides_sans_les_cacher(): void
    {
        $paquet = $this->getJson('/api/facilitateur/cohortes/1/paquet', $this->entete($this->jetonFacilitateur()))
            ->json();

        $renseignes = collect($paquet['modules'])->where('renseigne', true);

        $this->assertCount(1, $renseignes);
        $this->assertSame(8, $renseignes->first()['numero']);
    }

    public function test_le_paquet_designe_le_brise_glace_explicitement(): void
    {
        $paquet = $this->getJson('/api/facilitateur/cohortes/1/paquet', $this->entete($this->jetonFacilitateur()))
            ->json();

        $briseGlace = collect($paquet['modules'])
            ->flatMap->sequences
            ->where('est_brise_glace', true);

        // L'écran de séance rend ce moment sans chronomètre ni contrôle : il
        // doit le reconnaître par ce drapeau, pas en lisant un titre.
        $this->assertCount(1, $briseGlace);
        $this->assertSame('consigne_animation', $briseGlace->first()['type']);
        $this->assertNotNull($briseGlace->first()['consigne']);
    }

    public function test_le_paquet_liste_les_audios_a_mettre_en_cache(): void
    {
        $paquet = $this->getJson('/api/facilitateur/cohortes/1/paquet', $this->entete($this->jetonFacilitateur()))
            ->json();

        // 6 unités × 2 langues, en modalité audio.
        $this->assertCount(12, $paquet['audios']);
    }

    public function test_un_facilitateur_ne_telecharge_pas_le_paquet_dune_cohorte_qui_nest_pas_la_sienne(): void
    {
        $cohorte = \App\Models\Cohorte::create([
            'libelle' => 'Cohorte d\'un autre',
            'arrondissement' => 'Mvangan',
            'ratio_max' => 20,
            'curriculum_version_id' => 1,
            'facilitateur_id' => \App\Models\Facilitateur::where('arrondissement', 'Mvangan')->value('id'),
            'date_debut' => '2026-01-01',
        ]);

        $this->getJson('/api/facilitateur/cohortes/'.$cohorte->id.'/paquet',
            $this->entete($this->jetonFacilitateur()))->assertStatus(403);
    }
}
