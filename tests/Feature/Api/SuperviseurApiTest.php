<?php

namespace Tests\Feature\Api;

use App\Models\Cohorte;
use Tests\ApiTestCase;

class SuperviseurApiTest extends ApiTestCase
{
    public function test_le_registre_distingue_les_facilitateurs_actifs_des_inactifs(): void
    {
        $reponse = $this->getJson('/api/superviseur/facilitateurs', $this->entete($this->jetonSuperviseur()))
            ->assertOk();

        $this->assertSame(14, $reponse->json('synthese.formes'));
        $this->assertSame(3, $reponse->json('synthese.jamais_actifs'));
        $this->assertSame(
            $reponse->json('synthese.formes'),
            $reponse->json('synthese.actifs') + $reponse->json('synthese.inactifs'),
        );
    }

    public function test_le_rapport_trimestriel_chiffre_lecart_declare_observe(): void
    {
        $reponse = $this->getJson('/api/superviseur/rapport?annee=2026&trimestre=3',
            $this->entete($this->jetonSuperviseur()))->assertOk();

        $this->assertSame(3, $reponse->json('synthese.seances_tenues'));

        // Une seule des trois séances de démonstration porte un écart, dans
        // les deux sens : déclarée jamais ouverte, et ouverte déclarée non faite.
        $this->assertSame(2, $reponse->json('synthese.ecarts_total'));

        $ligne = $reponse->json('facilitateurs.0');
        $this->assertSame(1, $ligne['declarees_jamais_ouvertes']);
        $this->assertSame(1, $ligne['ouvertes_declarees_non_faites']);
        $this->assertNotNull($ligne['delai_moyen_remontee_jours']);
    }

    public function test_la_dose_moyenne_compte_le_rattrapage_par_binome(): void
    {
        $reponse = $this->getJson('/api/superviseur/rapport?annee=2026&trimestre=3',
            $this->entete($this->jetonSuperviseur()))->assertOk();

        // 19 + 17 + 18 séances reçues sur 20 parents inscrits.
        $this->assertSame(2.7, $reponse->json('synthese.dose_moyenne_par_parent'));
    }

    public function test_le_ratio_dune_cohorte_se_change_sans_toucher_au_code(): void
    {
        $reponse = $this->patchJson('/api/superviseur/cohortes/1', ['ratio_max' => 10],
            $this->entete($this->jetonSuperviseur()))->assertOk();

        $this->assertSame(20, $reponse->json('modification.ratio_max.avant'));
        $this->assertSame(10, $reponse->json('modification.ratio_max.apres'));
        $this->assertSame(10, Cohorte::find(1)->ratio_max);

        // Baisser le plafond sous l'effectif ne supprime personne : on le signale.
        $this->assertSame(10, $reponse->json('cohorte.effectif_au_dela_du_plafond'));
    }

    public function test_aucun_nom_de_parent_ne_sort_du_rapport(): void
    {
        $reponse = $this->getJson('/api/superviseur/rapport?annee=2026&trimestre=3',
            $this->entete($this->jetonSuperviseur()))->assertOk();

        // Le rapport agrège des cohortes et des facilitateurs. Les seules
        // personnes nommées du système sont les facilitateurs, agents publics.
        $this->assertStringNotContainsString('code_parent', $reponse->getContent());
    }
}
