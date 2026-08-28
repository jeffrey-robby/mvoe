<?php

namespace Tests\Feature\Api;

use App\Models\EvenementSync;
use App\Models\Module;
use App\Models\Presence;
use App\Models\Seance;
use Illuminate\Support\Str;
use Tests\ApiTestCase;

/**
 * La remontée du kit facilitateur.
 *
 * Le contrat que ces tests protègent : un kit hors ligne peut renvoyer sa file
 * autant de fois qu'il veut, dans n'importe quel ordre, après n'importe quelle
 * coupure, sans jamais dupliquer ni perdre quoi que ce soit.
 */
class RemonteeApiTest extends ApiTestCase
{
    public function test_une_file_denvoi_est_acceptee_puis_ignoree_si_elle_est_rejouee(): void
    {
        $jeton = $this->jetonFacilitateur();
        $file = $this->fileDUneSeance();

        $premier = $this->postJson('/api/facilitateur/evenements', ['evenements' => $file], $this->entete($jeton))
            ->assertStatus(202);

        $this->assertCount(count($file), $premier->json('acceptes'));
        $this->assertSame([], $premier->json('doublons'));
        $this->assertSame([], $premier->json('rejetes'));

        // Le même envoi, rejoué à l'identique : rien ne doit être créé deux fois.
        $second = $this->postJson('/api/facilitateur/evenements', ['evenements' => $file], $this->entete($jeton))
            ->assertStatus(202);

        $this->assertSame([], $second->json('acceptes'));
        $this->assertCount(count($file), $second->json('doublons'));

        $this->assertSame(1, Seance::where('uuid', $file[0]['uuid'])->count());
        $this->assertSame(2, Presence::where('seance_id', Seance::where('uuid', $file[0]['uuid'])->value('id'))->count());
    }

    public function test_les_evenements_arrivant_avant_leur_seance_sont_quand_meme_rattaches(): void
    {
        $jeton = $this->jetonFacilitateur();
        $file = $this->fileDUneSeance();

        // Le kit envoie sa file à l'envers : l'ouverture de séance en dernier.
        $this->postJson('/api/facilitateur/evenements', ['evenements' => array_reverse($file)], $this->entete($jeton))
            ->assertStatus(202)
            ->assertJsonPath('rejetes', []);

        $this->assertSame(1, Seance::where('uuid', $file[0]['uuid'])->count());
    }

    public function test_le_journal_conserve_chaque_evenement_recu(): void
    {
        $jeton = $this->jetonFacilitateur();
        $file = $this->fileDUneSeance();

        $avant = EvenementSync::count();

        $this->postJson('/api/facilitateur/evenements', ['evenements' => $file], $this->entete($jeton));

        $this->assertSame($avant + count($file), EvenementSync::count());
    }

    public function test_une_correction_de_pointage_met_a_jour_letat_sans_effacer_levenement_dorigine(): void
    {
        $jeton = $this->jetonFacilitateur();
        $file = $this->fileDUneSeance();

        $this->postJson('/api/facilitateur/evenements', ['evenements' => $file], $this->entete($jeton));

        $correction = [[
            'uuid' => (string) Str::uuid(),
            'type' => 'presence',
            'seance_uuid' => $file[0]['uuid'],
            'emis_a' => '2026-08-25T15:20:00+01:00',
            'charge' => ['code_parent' => 'EB2-01', 'statut' => 'rattrape_binome'],
        ]];

        $this->postJson('/api/facilitateur/evenements', ['evenements' => $correction], $this->entete($jeton))
            ->assertJsonPath('rejetes', []);

        $seanceId = Seance::where('uuid', $file[0]['uuid'])->value('id');

        // La projection porte le nouvel état...
        $this->assertSame('rattrape_binome', Presence::where('seance_id', $seanceId)
            ->whereRelation('parent', 'code_parent', 'EB2-01')->value('statut')->value);

        // ...et le journal garde les DEUX événements concernant ce parent :
        // le pointage initial et la correction. Rien n'est perdu.
        $this->assertSame(2, EvenementSync::where('type', 'presence')
            ->where('seance_uuid', $file[0]['uuid'])
            ->get()
            ->where('charge.code_parent', 'EB2-01')
            ->count());
    }

    public function test_un_facilitateur_ne_peut_pas_remonter_sur_la_cohorte_dun_autre(): void
    {
        $jeton = $this->jetonFacilitateur();

        $file = $this->fileDUneSeance();
        $file[0]['charge']['cohorte_id'] = 999;

        $reponse = $this->postJson('/api/facilitateur/evenements', ['evenements' => [$file[0]]], $this->entete($jeton));

        $this->assertSame([], $reponse->json('acceptes'));
        $this->assertCount(1, $reponse->json('rejetes'));
        $this->assertSame(0, Seance::where('uuid', $file[0]['uuid'])->count());
    }

    public function test_une_sequence_dun_autre_module_est_rejetee(): void
    {
        $jeton = $this->jetonFacilitateur();
        $file = $this->fileDUneSeance();

        $this->postJson('/api/facilitateur/evenements', ['evenements' => [$file[0]]], $this->entete($jeton));

        // Une séquence qui n'appartient pas au module de la séance fausserait
        // le calcul de l'écart : elle doit être refusée.
        $autreModule = Module::where('numero', 1)->value('id');
        $sequenceEtrangere = \App\Models\Sequence::create([
            'module_id' => $autreModule,
            'titre' => 'Séquence étrangère',
            'ordre' => 1,
            'duree_minutes' => 10,
            'type' => \App\Enums\TypeSequence::UniteDigitale,
        ]);

        $reponse = $this->postJson('/api/facilitateur/evenements', ['evenements' => [[
            'uuid' => (string) Str::uuid(),
            'type' => 'sequence_ouverte',
            'seance_uuid' => $file[0]['uuid'],
            'emis_a' => '2026-08-25T15:10:00+01:00',
            'charge' => [
                'sequence_id' => $sequenceEtrangere->id,
                'ouverte_a' => '2026-08-25T15:10:00+01:00',
                'duree_reelle_secondes' => 600,
            ],
        ]]], $this->entete($jeton));

        $this->assertCount(1, $reponse->json('rejetes'));
    }

    public function test_la_seance_remontee_expose_son_ecart_declare_observe(): void
    {
        $jeton = $this->jetonFacilitateur();
        $file = $this->fileDUneSeance();

        $this->postJson('/api/facilitateur/evenements', ['evenements' => $file], $this->entete($jeton));

        $reponse = $this->getJson('/api/facilitateur/seances/'.$file[0]['uuid'], $this->entete($jeton))
            ->assertOk();

        $ecarts = collect($reponse->json('ecarts'));

        // La séquence 2 est déclarée réalisée sans aucune trace d'ouverture.
        $this->assertSame(
            'declaree_non_observee',
            $ecarts->firstWhere('sequence.ordre', 2)['ecart'],
        );
        $this->assertNull($ecarts->firstWhere('sequence.ordre', 1)['ecart']);
    }

    /**
     * Une séance minimale : le brise-glace ouvert et déclaré, la séquence 2
     * déclarée réalisée mais jamais ouverte — donc un écart.
     *
     * @return array<int, array>
     */
    private function fileDUneSeance(): array
    {
        $module = Module::where('numero', 8)->firstOrFail();
        $sequences = $module->sequences()->ordonnees()->get()->keyBy('ordre');
        $seanceUuid = (string) Str::uuid();

        return [
            [
                'uuid' => $seanceUuid,
                'type' => 'seance',
                'seance_uuid' => null,
                'emis_a' => '2026-08-25T15:00:00+01:00',
                'charge' => ['cohorte_id' => 1, 'module_id' => $module->id, 'date' => '2026-08-25'],
            ],
            [
                'uuid' => (string) Str::uuid(),
                'type' => 'presence',
                'seance_uuid' => $seanceUuid,
                'emis_a' => '2026-08-25T15:06:00+01:00',
                'charge' => ['code_parent' => 'EB2-01', 'statut' => 'present'],
            ],
            [
                'uuid' => (string) Str::uuid(),
                'type' => 'presence',
                'seance_uuid' => $seanceUuid,
                'emis_a' => '2026-08-25T15:06:00+01:00',
                'charge' => ['code_parent' => 'EB2-02', 'statut' => 'absent'],
            ],
            [
                'uuid' => (string) Str::uuid(),
                'type' => 'sequence_ouverte',
                'seance_uuid' => $seanceUuid,
                'emis_a' => '2026-08-25T15:00:00+01:00',
                'charge' => [
                    'sequence_id' => $sequences[1]->id,
                    'ouverte_a' => '2026-08-25T15:00:00+01:00',
                    'duree_reelle_secondes' => 620,
                ],
            ],
            [
                'uuid' => (string) Str::uuid(),
                'type' => 'fiche_fidelite',
                'seance_uuid' => $seanceUuid,
                'emis_a' => '2026-08-25T16:40:00+01:00',
                'charge' => ['sequence_id' => $sequences[1]->id, 'realisee_bool' => true, 'note_qualite' => 3],
            ],
            [
                'uuid' => (string) Str::uuid(),
                'type' => 'fiche_fidelite',
                'seance_uuid' => $seanceUuid,
                'emis_a' => '2026-08-25T16:40:00+01:00',
                'charge' => ['sequence_id' => $sequences[2]->id, 'realisee_bool' => true, 'note_qualite' => 2],
            ],
            [
                'uuid' => (string) Str::uuid(),
                'type' => 'bilan_seance',
                'seance_uuid' => $seanceUuid,
                'emis_a' => '2026-08-25T16:40:00+01:00',
                'charge' => ['commentaire' => 'La pluie a couvert les voix.'],
            ],
        ];
    }
}
