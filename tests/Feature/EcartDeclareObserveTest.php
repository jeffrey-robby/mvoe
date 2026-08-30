<?php

namespace Tests\Feature;

use App\Enums\TypeSequence;
use App\Models\CurriculumVersion;
use App\Models\FicheFidelite;
use App\Models\Module;
use App\Models\Seance;
use App\Models\Sequence;
use App\Models\SequenceOuverte;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

/**
 * L'écart entre le DÉCLARÉ et l'OBSERVÉ est la fonctionnalité centrale du
 * projet. Ce test la verrouille : si le calcul change, il casse ici avant
 * de casser devant le jury.
 */
class EcartDeclareObserveTest extends TestCase
{
    use RefreshDatabase;

    private Module $module;

    private Seance $seance;

    protected function setUp(): void
    {
        parent::setUp();

        $version = CurriculumVersion::create(['label' => 'Test', 'active' => true]);

        $this->module = Module::create([
            'curriculum_version_id' => $version->id,
            'numero' => 8,
            'titre' => 'Discipline positive',
            'ordre' => 8,
        ]);

        foreach (range(1, 3) as $ordre) {
            Sequence::create([
                'module_id' => $this->module->id,
                'titre' => "Séquence $ordre",
                'ordre' => $ordre,
                'duree_minutes' => 20,
                'type' => TypeSequence::UniteDigitale,
            ]);
        }

        // La hiérarchie minimale : tout se rattache à un arrondissement, et un
        // facilitateur ne peut exister sans le superviseur qui l'a enregistré.
        //
        // Libellés propres à ce test : la base de test porte déjà le découpage
        // réel, et réutiliser « Sud » entrerait en collision avec lui.
        $region = \App\Models\Region::create([
            'code' => 'ZZ', 'libelle' => 'Région de test', 'peuplee' => true,
        ]);
        $departement = \App\Models\Departement::create([
            'region_id' => $region->id, 'libelle' => 'Département de test',
        ]);
        $arrondissement = \App\Models\Arrondissement::create([
            'departement_id' => $departement->id,
            'region_id' => $region->id,
            'libelle' => 'Arrondissement de test',
        ]);

        $superviseur = \App\Models\User::create([
            'name' => 'Superviseur de test',
            'email' => 'superviseur.test@mvoe.test',
            'password' => 'mot-de-passe-de-test',
            'niveau' => 'arrondissement',
            'arrondissement_id' => $arrondissement->id,
        ]);

        $facilitateur = \App\Models\Facilitateur::create([
            'nom' => 'Test',
            'telephone' => '600000000',
            'code_appareil' => '123456',
            'email' => 'test@minproff.cm',
            'password' => 'mot-de-passe-de-test',
            'arrondissement_id' => $arrondissement->id,
            'superviseur_id' => $superviseur->id,
            'type_juridique' => \App\Enums\TypeJuridique::AgentPublic,
            'date_formation_initiale' => '2025-01-01',
        ]);

        $cohorte = \App\Models\Cohorte::create([
            'libelle' => 'Test',
            'arrondissement_id' => $arrondissement->id,
            'ratio_max' => 20,
            'curriculum_version_id' => $version->id,
            'facilitateur_id' => $facilitateur->id,
            'date_debut' => '2026-01-01',
        ]);

        $this->seance = Seance::create([
            'uuid' => (string) Str::uuid(),
            'cohorte_id' => $cohorte->id,
            'module_id' => $this->module->id,
            'date' => '2026-02-03',
            'facilitateur_id' => $facilitateur->id,
        ]);
    }

    public function test_une_sequence_declaree_realisee_sans_trace_douverture_est_un_ecart(): void
    {
        $sequence = $this->sequence(1);

        $this->declarer($sequence, realisee: true);
        // Aucune ouverture enregistrée pour cette séquence.

        $ligne = $this->ligneEcart($sequence->id);

        $this->assertTrue($ligne['declaree']);
        $this->assertFalse($ligne['observee']);
        $this->assertSame('declaree_non_observee', $ligne['ecart']);
        $this->assertSame(1, $this->seance->nombreEcarts());
    }

    public function test_une_sequence_ouverte_mais_declaree_non_realisee_est_aussi_un_ecart(): void
    {
        $sequence = $this->sequence(2);

        $this->declarer($sequence, realisee: false);
        $this->ouvrir($sequence);

        $ligne = $this->ligneEcart($sequence->id);

        $this->assertSame('observee_non_declaree', $ligne['ecart']);
    }

    public function test_une_sequence_declaree_et_ouverte_ne_produit_aucun_ecart(): void
    {
        $sequence = $this->sequence(3);

        $this->declarer($sequence, realisee: true);
        $this->ouvrir($sequence);

        $this->assertNull($this->ligneEcart($sequence->id)['ecart']);
        $this->assertSame(0, $this->seance->nombreEcarts());
    }

    public function test_une_sequence_sans_declaration_ni_ouverture_nest_pas_comptee_comme_ecart(): void
    {
        // Le silence n'est pas une contradiction : on ne reproche rien
        // à un facilitateur qui n'a rien déclaré sur une séquence.
        $this->assertNull($this->ligneEcart($this->sequence(1)->id)['ecart']);
        $this->assertSame(0, $this->seance->nombreEcarts());
    }

    public function test_les_ecarts_couvrent_toutes_les_sequences_du_module(): void
    {
        $this->assertCount(3, $this->seance->ecarts());
    }

    private function sequence(int $ordre): Sequence
    {
        return $this->module->sequences()->where('ordre', $ordre)->firstOrFail();
    }

    private function declarer(Sequence $sequence, bool $realisee): void
    {
        FicheFidelite::create([
            'uuid' => (string) Str::uuid(),
            'seance_id' => $this->seance->id,
            'sequence_id' => $sequence->id,
            'realisee_bool' => $realisee,
        ]);
    }

    private function ouvrir(Sequence $sequence): void
    {
        SequenceOuverte::create([
            'uuid' => (string) Str::uuid(),
            'seance_id' => $this->seance->id,
            'sequence_id' => $sequence->id,
            'ouverte_a' => now(),
            'duree_reelle_secondes' => 1200,
        ]);
    }

    private function ligneEcart(int $sequenceId): array
    {
        return $this->seance->ecarts()
            ->firstWhere(fn (array $ligne) => $ligne['sequence']->id === $sequenceId);
    }
}
