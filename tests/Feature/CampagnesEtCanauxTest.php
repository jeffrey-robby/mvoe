<?php

namespace Tests\Feature;

use App\Canaux\Canaux;
use App\Enums\Canal;
use App\Models\Campagne;
use App\Models\CampagneAffectation;
use App\Models\Langue;
use App\Models\ModuleFormation;
use App\Models\Realisation;
use App\Models\Region;
use Illuminate\Support\Carbon;
use Tests\ApiTestCase;

/**
 * Les campagnes, la bibliothèque et les canaux.
 *
 * Trois règles du brief se vérifient ici, et ce sont les trois qui décident de
 * l'honnêteté du prototype : la cascade est un enregistrement et non une
 * simulation, un contenu non validé ne part jamais, et **aucune audience radio
 * n'est fabriquée**.
 */
class CampagnesEtCanauxTest extends ApiTestCase
{
    /* ---------------------------------------------------------------------- */
    /* Campagnes                                                              */
    /* ---------------------------------------------------------------------- */

    public function test_le_minproff_voit_les_dix_regions_et_cree_une_campagne_sur_le_sud(): void
    {
        // Critère d'acceptation n° 1 du brief.
        $jeton = $this->jetonNational();

        $vue = $this->getJson('/api/superviseur/campagnes', $this->entete($jeton))->assertOk();

        $this->assertTrue($vue->json('peut_creer'));
        $this->assertCount(10, $vue->json('regions'));

        $sud = Region::where('libelle', 'Sud')->firstOrFail();

        $reponse = $this->postJson('/api/superviseur/campagnes', [
            'titre' => 'Vacances — écouter son enfant',
            'module_ids' => ModuleFormation::diffusables()->pluck('id')->take(1)->all(),
            'langue_ids' => Langue::actives()->pluck('id')->all(),
            'region_ids' => [$sud->id],
            'date_debut' => now()->toDateString(),
            'date_fin' => now()->addDays(45)->toDateString(),
        ], $this->entete($jeton))->assertCreated();

        // 1 région + 4 départements + 29 arrondissements + 50 facilitateurs.
        $this->assertSame(84, $reponse->json('affectations_creees'));
        $this->assertSame(['Sud'], $reponse->json('campagne.regions'));
    }

    public function test_la_cascade_est_enregistree_dun_coup_et_non_propagee(): void
    {
        // « N'implémente pas de logique métier de propagation complexe : crée
        // les enregistrements d'affectation et affiche l'état d'avancement. »
        // Les quatre niveaux existent dès le déclenchement, et aucun n'est
        // marqué reçu : personne n'a encore ouvert.
        $campagne = $this->creerUneCampagne();

        $niveaux = CampagneAffectation::where('campagne_id', $campagne['id'])
            ->pluck('niveau')->unique()->sort()->values();

        $this->assertSame(
            ['arrondissement', 'departement', 'facilitateur', 'region'],
            $niveaux->all(),
        );

        $this->assertSame(0, CampagneAffectation::where('campagne_id', $campagne['id'])
            ->whereNotNull('date_reception')->count());
    }

    public function test_un_module_non_valide_ne_part_jamais_en_campagne(): void
    {
        $nonValide = ModuleFormation::where('code', 'RN-02')->firstOrFail();

        $this->postJson('/api/superviseur/campagnes', [
            'titre' => 'Campagne sur un brouillon',
            'module_ids' => [$nonValide->id],
            'langue_ids' => Langue::actives()->pluck('id')->take(1)->all(),
            'region_ids' => [Region::where('libelle', 'Sud')->value('id')],
            'date_debut' => now()->toDateString(),
            'date_fin' => now()->addDays(10)->toDateString(),
        ], $this->entete($this->jetonNational()))->assertStatus(422);
    }

    public function test_seul_le_ministere_declenche_une_campagne(): void
    {
        $this->postJson('/api/superviseur/campagnes', [
            'titre' => 'Campagne régionale',
            'module_ids' => ModuleFormation::diffusables()->pluck('id')->take(1)->all(),
            'langue_ids' => Langue::actives()->pluck('id')->take(1)->all(),
            'region_ids' => [Region::where('libelle', 'Sud')->value('id')],
            'date_debut' => now()->toDateString(),
            'date_fin' => now()->addDays(10)->toDateString(),
        ], $this->entete($this->jetonRegional()))->assertForbidden();
    }

    public function test_une_delegation_lit_les_campagnes_qui_la_concernent_et_en_accuse_reception(): void
    {
        $jeton = $this->jetonSuperviseur();

        $vue = $this->getJson('/api/superviseur/campagnes', $this->entete($jeton))->assertOk();

        $this->assertFalse($vue->json('peut_creer'));
        $this->assertNotEmpty($vue->json('campagnes'));

        // La campagne fraîche du jeu de démonstration : personne ne l'a ouverte.
        $fraiche = collect($vue->json('campagnes'))
            ->firstWhere('titre', 'Écoute et révélation');

        $this->assertNotNull($fraiche);

        $avant = collect($fraiche['avancement'])->firstWhere('niveau', 'departement');
        $this->assertSame(0, $avant['recues']);

        $this->app['auth']->forgetGuards();

        $apres = $this->postJson("/api/superviseur/campagnes/{$fraiche['id']}/reception", [],
            $this->entete($this->jetonSuperviseur()))->assertOk();

        $this->assertTrue($apres->json('recue'));

        $this->assertSame(1, collect($apres->json('campagne.avancement'))
            ->firstWhere('niveau', 'departement')['recues']);
    }

    public function test_le_ministere_ne_recoit_pas_ses_propres_campagnes(): void
    {
        $campagne = Campagne::firstOrFail();

        $this->postJson("/api/superviseur/campagnes/{$campagne->id}/reception", [],
            $this->entete($this->jetonNational()))->assertStatus(422);
    }

    /* ---------------------------------------------------------------------- */
    /* Bibliothèque                                                           */
    /* ---------------------------------------------------------------------- */

    public function test_la_bibliotheque_est_une_prerogative_du_ministere(): void
    {
        // Une délégation qui validerait ses propres contenus produirait dix
        // curriculums différents, et le programme cesserait d'être national.
        $this->getJson('/api/superviseur/bibliotheque', $this->entete($this->jetonRegional()))
            ->assertForbidden();

        $this->app['auth']->forgetGuards();

        $this->getJson('/api/superviseur/bibliotheque', $this->entete($this->jetonNational()))
            ->assertOk();
    }

    public function test_valider_un_module_le_rend_diffusable_et_le_renvoi_le_retire(): void
    {
        $jeton = $this->jetonNational();

        $file = $this->getJson('/api/superviseur/bibliotheque', $this->entete($jeton))
            ->assertOk()->json('file_de_validation');

        $this->assertSame(['RN-02'], array_column($file, 'code'));

        $this->patchJson('/api/superviseur/bibliotheque/modules/RN-02',
            ['statut_validation' => 'valide'], $this->entete($jeton))
            ->assertOk()->assertJsonPath('module.diffusable', true);

        $this->assertSame(4, ModuleFormation::diffusables()->count());

        // Renvoyer en brouillon le retire immédiatement : un module qu'on
        // découvre faux doit cesser d'être lu, pas attendre la version suivante.
        $this->patchJson('/api/superviseur/bibliotheque/modules/RN-02',
            ['statut_validation' => 'brouillon'], $this->entete($jeton))
            ->assertOk()->assertJsonPath('module.diffusable', false);

        $this->assertSame(3, ModuleFormation::diffusables()->count());
    }

    public function test_retirer_une_langue_ne_supprime_aucun_contenu(): void
    {
        $bulu = Langue::where('code', 'bulu')->firstOrFail();
        $avant = Realisation::where('langue_id', $bulu->id)->count();

        $this->assertGreaterThan(0, $avant);

        $reponse = $this->patchJson("/api/superviseur/bibliotheque/langues/{$bulu->id}",
            ['actif' => false], $this->entete($this->jetonNational()))->assertOk();

        $this->assertFalse($reponse->json('langue.actif'));
        $this->assertSame($avant, $reponse->json('realisations_conservees'));
        $this->assertSame($avant, Realisation::where('langue_id', $bulu->id)->count());
    }

    public function test_la_couverture_dit_ou_porter_leffort(): void
    {
        // Une unité chargée en français et pas en bulu n'atteint pas les
        // locuteurs bulu, quel que soit le nombre total de réalisations.
        $couverture = $this->getJson('/api/superviseur/bibliotheque',
            $this->entete($this->jetonNational()))->assertOk()
            ->json('contenus_parents.couverture');

        $anglais = collect($couverture)->firstWhere('langue', 'en');

        $this->assertSame(0, $anglais['unites_couvertes']);
        $this->assertGreaterThan(0, $anglais['manquantes']);
    }

    /* ---------------------------------------------------------------------- */
    /* Canaux                                                                 */
    /* ---------------------------------------------------------------------- */

    public function test_les_pilotes_disent_quils_sont_factices(): void
    {
        // Un prototype qui laisserait croire que des SMS partent vraiment
        // mentirait à son jury.
        $reponse = $this->getJson('/api/superviseur/canaux', $this->entete($this->jetonNational()))
            ->assertOk();

        $this->assertTrue($reponse->json('pilotes_factices'));

        foreach ($reponse->json('canaux') as $canal) {
            $this->assertTrue($canal['factice'], "« {$canal['libelle']} » doit se déclarer factice.");
        }
    }

    public function test_les_quatre_canaux_repondent_a_la_meme_interface(): void
    {
        // C'est l'argument de réplicabilité : le passage à une infrastructure
        // nationale ne change qu'un pilote.
        $canaux = app(Canaux::class);

        foreach (Canal::cases() as $canal) {
            $pilote = $canaux->pour($canal);

            $this->assertInstanceOf(\App\Canaux\PiloteDeCanal::class, $pilote);
            $this->assertSame($canal, $pilote->canal());

            $stats = $pilote->statistiques(now()->subYear(), now());

            foreach (['canal', 'libelle', 'volume', 'unite', 'aboutis'] as $cle) {
                $this->assertArrayHasKey($cle, $stats,
                    "« {$canal->value} » doit rendre « $cle » comme les autres.");
            }
        }
    }

    public function test_la_radio_ne_fabrique_aucune_audience(): void
    {
        // La règle la plus importante de ce lot. Une station qui annonce « deux
        // millions d'auditeurs » n'a compté personne.
        $radio = collect($this->getJson('/api/superviseur/canaux',
            $this->entete($this->jetonNational()))->assertOk()->json('canaux'))
            ->firstWhere('canal', 'radio');

        $this->assertNull($radio['audience']);
        $this->assertSame('surcroit_48h', $radio['mesure']);

        // Et le CODE ne contient aucune estimation de portée. On retire les
        // commentaires avant de chercher : c'est justement dans un commentaire
        // que le pilote explique pourquoi il ne compte pas d'auditeurs, et ce
        // texte-là doit rester.
        $code = preg_replace(
            ['#/\*.*?\*/#s', '#//.*$#m'],
            '',
            \Illuminate\Support\Facades\File::get(app_path('Canaux/PiloteRadio.php')),
        );

        foreach (['audience_estimee', 'portee_theorique', 'auditeurs', 'population'] as $interdit) {
            $this->assertStringNotContainsString($interdit, $code,
                "Le pilote radio ne doit rien calculer qui ressemble à « $interdit ».");
        }
    }

    public function test_la_radio_se_mesure_par_le_surcroit_a_48_heures(): void
    {
        $radio = collect($this->getJson('/api/superviseur/canaux',
            $this->entete($this->jetonNational()))->assertOk()->json('canaux'))
            ->firstWhere('canal', 'radio');

        $surcroit = $radio['surcroit'];

        $this->assertTrue($surcroit['mesurable']);
        $this->assertSame(48, $surcroit['fenetre_heures']);

        // Le jeu de démonstration met en scène un vrai pic : les appels et les
        // sessions triplent dans la fenêtre qui suit une diffusion attestée.
        $this->assertGreaterThan($surcroit['par_heure_ordinaire'], $surcroit['par_heure_apres']);
        $this->assertGreaterThan(100, $surcroit['surcroit_pourcent']);

        // La limite est rendue AVEC le chiffre, pas dans une annexe.
        $this->assertStringContainsString('Sous-estime', $surcroit['limite']);
    }

    public function test_sans_diffusion_attestee_la_mesure_se_declare_impossible(): void
    {
        // « Pas mesurable » et « aucun effet » ne veulent pas dire la même
        // chose : on ne remplace jamais l'un par l'autre.
        $pilote = app(Canaux::class)->pour(Canal::Radio);

        $stats = $pilote->statistiques(
            Carbon::parse('2020-01-01'), Carbon::parse('2020-01-31'),
        );

        $this->assertFalse($stats['surcroit']['mesurable']);
        $this->assertNotEmpty($stats['surcroit']['raison']);
        $this->assertNull($stats['audience']);
    }

    public function test_aucun_canal_ne_peut_atteindre_un_parent_identifie(): void
    {
        // La section 7 du brief interdit tout message sortant vers un parent ;
        // la section 2 demande un `SmsDriver`. Les deux tiennent parce que la
        // table `parents` n'a AUCUN numéro : il n'y a nulle part où lire à qui
        // envoyer. Une cible est collective, jamais une personne.
        $colonnes = \Illuminate\Support\Facades\Schema::getColumnListing('parents');

        foreach (['telephone', 'numero', 'msisdn', 'email'] as $interdit) {
            $this->assertNotContains($interdit, $colonnes,
                "Un parent ne doit porter aucun « $interdit » : sinon un canal pourrait le joindre.");
        }

        // Et ce que les diffusions visent est un libellé collectif.
        foreach (\App\Models\Diffusion::limit(20)->pluck('cible') as $cible) {
            $this->assertDoesNotMatchRegularExpression('/\+?237|6\d{8}/', $cible,
                'Une cible de diffusion ne doit jamais ressembler à un numéro.');
        }
    }

    public function test_les_canaux_sont_une_prerogative_du_ministere(): void
    {
        $this->getJson('/api/superviseur/canaux', $this->entete($this->jetonFacilitateur()))
            ->assertForbidden();
    }

    /* ---------------------------------------------------------------------- */

    private function creerUneCampagne(): array
    {
        return $this->postJson('/api/superviseur/campagnes', [
            'titre' => 'Campagne de test',
            'module_ids' => ModuleFormation::diffusables()->pluck('id')->take(1)->all(),
            'langue_ids' => Langue::actives()->pluck('id')->take(1)->all(),
            'region_ids' => [Region::where('libelle', 'Sud')->value('id')],
            'date_debut' => now()->toDateString(),
            'date_fin' => now()->addDays(30)->toDateString(),
        ], $this->entete($this->jetonNational()))->assertCreated()->json('campagne');
    }
}
