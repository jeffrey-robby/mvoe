<?php

namespace Tests\Feature;

use App\Models\Activite;
use App\Models\Facilitateur;
use App\Models\Foyer;
use App\Models\GroupeSoutien;
use App\Models\Signalement;
use App\Models\Visite;
use Illuminate\Support\Str;
use Tests\ApiTestCase;

/**
 * Le travail de terrain : activités, visites à domicile, groupes de soutien,
 * signalements.
 *
 * Tout se saisit sans réseau et passe donc par la file d'événements. Ces tests
 * envoient exactement ce qu'un kit hors ligne aurait accumulé.
 *
 * Trois règles du brief sont vérifiées ici, et ce sont des règles absolues :
 * aucune identité dans un signalement, aucune notification automatique, et la
 * suite donnée toujours visible par celui qui a signalé.
 */
class TerrainTest extends ApiTestCase
{
    /* ---------------------------------------------------------------------- */
    /* Activités                                                              */
    /* ---------------------------------------------------------------------- */

    public function test_une_causerie_educative_enregistre_sexes_et_situations_de_handicap(): void
    {
        // Critère d'acceptation n° 6 du brief, au chiffre près.
        $uuid = (string) Str::uuid();

        $this->envoyer([$this->activite($uuid, [
            'type' => 'causerie_educative',
            'nb_parents_touches' => 35,
            'nb_hommes' => 12,
            'nb_femmes' => 23,
            'nb_participants_handicap' => 2,
        ])]);

        $activite = Activite::where('uuid', $uuid)->firstOrFail();

        $this->assertSame(35, $activite->nb_parents_touches);
        $this->assertSame(12, $activite->nb_hommes);
        $this->assertSame(23, $activite->nb_femmes);
        $this->assertSame(2, $activite->nb_participants_handicap);

        // C'est ce rapport qui rend le critère mesurable plutôt que déclaratif.
        $this->assertSame(5.7, $activite->partHandicap());
        $this->assertSame(0, $activite->sexeNonRenseigne());
    }

    public function test_une_repartition_par_sexe_incoherente_est_refusee(): void
    {
        // 12 + 23 sur 30 : une saisie est fausse. Deviner laquelle produirait un
        // chiffre que personne n'a jamais compté.
        $uuid = (string) Str::uuid();

        $reponse = $this->envoyer([$this->activite($uuid, [
            'nb_parents_touches' => 30,
            'nb_hommes' => 12,
            'nb_femmes' => 23,
        ])]);

        $this->assertSame([], $reponse->json('acceptes'));
        $this->assertNull(Activite::where('uuid', $uuid)->first());
    }

    public function test_le_sexe_non_renseigne_est_dit_et_non_comble(): void
    {
        $uuid = (string) Str::uuid();

        $this->envoyer([$this->activite($uuid, [
            'nb_parents_touches' => 40,
            'nb_hommes' => 10,
            'nb_femmes' => 25,
        ])]);

        $this->assertSame(5, Activite::where('uuid', $uuid)->firstOrFail()->sexeNonRenseigne());
    }

    public function test_larrondissement_dune_activite_vient_du_compte_jamais_de_la_requete(): void
    {
        $uuid = (string) Str::uuid();
        $evenement = $this->activite($uuid);

        // Une tentative de déposer l'activité ailleurs.
        $evenement['charge']['arrondissement_id'] = 99;

        $this->envoyer([$evenement]);

        $this->assertSame(
            $this->facilitateur()->arrondissement_id,
            Activite::where('uuid', $uuid)->firstOrFail()->arrondissement_id,
        );
    }

    public function test_une_activite_rend_le_facilitateur_actif(): void
    {
        // Un facilitateur qui ne fait que des causeries travaille. Ne compter
        // que les séances reviendrait à le déclarer inactif.
        $facilitateur = $this->facilitateur();
        $facilitateur->forceFill(['derniere_activite' => null])->save();

        $this->envoyer([$this->activite((string) Str::uuid(), ['date' => now()->toDateString()])]);

        $this->assertNotNull($facilitateur->fresh()->derniere_activite);
        $this->assertTrue($facilitateur->fresh()->estActif());
    }

    /* ---------------------------------------------------------------------- */
    /* Visites à domicile                                                     */
    /* ---------------------------------------------------------------------- */

    public function test_une_visite_a_domicile_enregistre_un_foyer_sans_identite(): void
    {
        // Critère d'acceptation n° 7 : localité, composition du foyer, une
        // difficulté fonctionnelle, déjà suivi le programme.
        $foyerUuid = (string) Str::uuid();
        $visiteUuid = (string) Str::uuid();

        $this->envoyer([
            // Volontairement dans le désordre : la visite avant le foyer.
            [
                'uuid' => $visiteUuid,
                'type' => 'visite',
                'seance_uuid' => null,
                'emis_a' => now()->toIso8601String(),
                'charge' => [
                    'foyer_uuid' => $foyerUuid,
                    'date' => now()->toDateString(),
                    'observations_structurees' => ['espace_de_jeu', 'routine_du_coucher'],
                    'suivi_prevu' => true,
                ],
            ],
            [
                'uuid' => $foyerUuid,
                'type' => 'foyer',
                'seance_uuid' => null,
                'emis_a' => now()->toIso8601String(),
                'charge' => [
                    'localite' => 'quartier Nko\'ovos',
                    'nb_adultes' => 2,
                    'nb_enfants' => 4,
                    'difficultes_fonctionnelles_foyer' => ['audition'],
                    'deja_suivi_programme' => true,
                ],
            ],
        ]);

        $foyer = Foyer::where('uuid', $foyerUuid)->firstOrFail();

        $this->assertSame('quartier Nko\'ovos', $foyer->localite);
        $this->assertSame(6, $foyer->taille());
        $this->assertTrue($foyer->deja_suivi_programme);
        $this->assertSame(['Entendre'], $foyer->difficultes()->map(fn ($d) => $d->libelle())->all());

        $visite = Visite::where('uuid', $visiteUuid)->firstOrFail();

        $this->assertSame($foyer->id, $visite->foyer_id);
        $this->assertTrue($visite->suivi_prevu);
    }

    public function test_un_foyer_ne_stocke_ni_nom_ni_adresse_ni_position(): void
    {
        $uuid = (string) Str::uuid();
        $evenement = $this->foyer($uuid);

        // Tout ce que le schéma refuse d'accueillir, envoyé quand même.
        $evenement['charge'] += [
            'nom' => 'Famille Mballa',
            'adresse' => '3e rue, derrière la station',
            'latitude' => 2.9,
            'longitude' => 11.15,
        ];

        $this->envoyer([$evenement]);

        $foyer = Foyer::where('uuid', $uuid)->firstOrFail();

        foreach (['nom', 'adresse', 'latitude', 'longitude'] as $interdit) {
            $this->assertFalse(array_key_exists($interdit, $foyer->getAttributes()),
                "Un foyer ne doit avoir aucune colonne « $interdit ».");
        }
    }

    public function test_un_facilitateur_ne_visite_pas_le_foyer_dun_autre(): void
    {
        $autre = Facilitateur::where('id', '!=', $this->facilitateur()->id)->firstOrFail();

        $foyer = Foyer::create([
            'uuid' => (string) Str::uuid(),
            'facilitateur_id' => $autre->id,
            'arrondissement_id' => $autre->arrondissement_id,
            'localite' => 'ailleurs',
            'nb_adultes' => 1,
            'nb_enfants' => 1,
            'difficultes_fonctionnelles_foyer' => [],
            'deja_suivi_programme' => false,
            'recue_a' => now(),
        ]);

        $uuid = (string) Str::uuid();

        $reponse = $this->envoyer([[
            'uuid' => $uuid,
            'type' => 'visite',
            'seance_uuid' => null,
            'emis_a' => now()->toIso8601String(),
            'charge' => [
                'foyer_uuid' => $foyer->uuid,
                'date' => now()->toDateString(),
                'observations_structurees' => [],
                'suivi_prevu' => false,
            ],
        ]]);

        $this->assertSame([], $reponse->json('acceptes'));
        $this->assertNull(Visite::where('uuid', $uuid)->first());
    }

    /* ---------------------------------------------------------------------- */
    /* Groupes de soutien                                                     */
    /* ---------------------------------------------------------------------- */

    public function test_une_reunion_fait_avancer_la_continuite_du_groupe(): void
    {
        $gspUuid = (string) Str::uuid();

        $this->envoyer([[
            'uuid' => $gspUuid,
            'type' => 'groupe_soutien',
            'seance_uuid' => null,
            'emis_a' => now()->subYear()->toIso8601String(),
            'charge' => [
                'libelle' => 'Groupe du mercredi',
                'date_creation' => now()->subYear()->toDateString(),
                'membres' => ['EB2-01', 'EB2-03', 'EB2-07'],
            ],
        ]]);

        $groupe = GroupeSoutien::where('uuid', $gspUuid)->firstOrFail();

        // Un groupe qui vient d'être créé ne s'est pas encore réuni : on le dit
        // au lieu d'inventer une date.
        $this->assertNull($groupe->derniere_reunion);
        $this->assertFalse($groupe->estActif());
        $this->assertSame(3, $groupe->membres()->count());

        $this->envoyer([$this->activite((string) Str::uuid(), [
            'type' => 'reunion_gsp',
            'date' => now()->subDays(3)->toDateString(),
            'gsp_uuid' => $gspUuid,
        ])]);

        $groupe->refresh();

        $this->assertSame(now()->subDays(3)->toDateString(), $groupe->derniere_reunion->toDateString());
        $this->assertTrue($groupe->estActif());
    }

    public function test_une_reunion_ancienne_ne_fait_pas_reculer_la_continuite(): void
    {
        $gspUuid = (string) Str::uuid();

        $this->envoyer([[
            'uuid' => $gspUuid,
            'type' => 'groupe_soutien',
            'seance_uuid' => null,
            'emis_a' => now()->toIso8601String(),
            'charge' => ['libelle' => 'G', 'date_creation' => now()->subYear()->toDateString()],
        ]]);

        foreach ([10, 120] as $joursAvant) {
            $this->envoyer([$this->activite((string) Str::uuid(), [
                'type' => 'reunion_gsp',
                'date' => now()->subDays($joursAvant)->toDateString(),
                'gsp_uuid' => $gspUuid,
            ])]);
        }

        // La remontée tardive d'une vieille réunion ne doit pas effacer la
        // récente : la continuité ne recule pas.
        $this->assertSame(
            now()->subDays(10)->toDateString(),
            GroupeSoutien::where('uuid', $gspUuid)->firstOrFail()->derniere_reunion->toDateString(),
        );
    }

    /* ---------------------------------------------------------------------- */
    /* Signalements                                                           */
    /* ---------------------------------------------------------------------- */

    public function test_un_signalement_apparait_chez_son_superviseur_et_nulle_part_ailleurs(): void
    {
        // Critère d'acceptation n° 8 du brief.
        $uuid = (string) Str::uuid();

        $this->envoyer([$this->signalement($uuid)]);

        $this->app['auth']->forgetGuards();

        // Le superviseur d'Ebolowa II le voit.
        $sien = collect($this->getJson('/api/superviseur/signalements',
            $this->entete($this->jetonSuperviseurArrondissement()))->assertOk()
            ->json('signalements'))->pluck('uuid');

        $this->assertContains($uuid, $sien);

        // Un superviseur d'un autre département ne le voit pas. On prend
        // l'Océan, qui ne contient pas Ebolowa II.
        $this->app['auth']->forgetGuards();

        $ocean = $this->jetonDelegation('ocean@mvoe.test');

        $ailleurs = collect($this->getJson('/api/superviseur/signalements', $this->entete($ocean))
            ->assertOk()->json('signalements'))->pluck('uuid');

        $this->assertNotContains($uuid, $ailleurs);
    }

    public function test_un_signalement_ne_porte_aucune_identite(): void
    {
        $uuid = (string) Str::uuid();
        $evenement = $this->signalement($uuid);

        $evenement['charge'] += [
            'nom_enfant' => 'Odile',
            'parent_id' => 1,
            'foyer_id' => 1,
            'description' => 'La fille de la voisine du dispensaire',
        ];

        $this->envoyer([$evenement]);

        $signalement = Signalement::where('uuid', $uuid)->firstOrFail();

        foreach (['nom_enfant', 'parent_id', 'foyer_id', 'description'] as $interdit) {
            $this->assertFalse(array_key_exists($interdit, $signalement->getAttributes()),
                "Un signalement ne doit avoir aucune colonne « $interdit ».");
        }

        // Ce qu'il porte, et rien d'autre.
        $this->assertSame('maltraitance', $signalement->type->value);
        $this->assertSame('elevee', $signalement->gravite->value);
        $this->assertSame('soumis', $signalement->statut->value);
    }

    public function test_le_facilitateur_voit_la_suite_donnee_a_son_signalement(): void
    {
        // Un signalement sans retour est un signalement qu'on ne refait pas.
        $uuid = (string) Str::uuid();

        $this->envoyer([$this->signalement($uuid)]);

        $signalement = Signalement::where('uuid', $uuid)->firstOrFail();

        $this->app['auth']->forgetGuards();

        $reponse = $this->patchJson("/api/superviseur/signalements/{$signalement->id}", [
            'statut' => 'oriente',
            'suite_donnee' => 'Transmis au centre social, qui a pris le relais.',
        ], $this->entete($this->jetonSuperviseurArrondissement()))->assertOk();

        // Dit explicitement : le système n'a prévenu personne.
        $this->assertTrue($reponse->json('aucune_notification_envoyee'));

        $this->app['auth']->forgetGuards();

        $sien = collect($this->getJson('/api/facilitateur/signalements',
            $this->entete($this->jetonFacilitateur()))->assertOk()->json('signalements'))
            ->firstWhere('uuid', $uuid);

        $this->assertSame('Transmis au centre social, qui a pris le relais.', $sien['suite_donnee']);
        $this->assertSame('Orienté', $sien['statut_libelle']);
        $this->assertFalse($sien['ouvert']);
    }

    public function test_clore_un_signalement_sans_ecrire_la_suite_est_refuse(): void
    {
        $uuid = (string) Str::uuid();

        $this->envoyer([$this->signalement($uuid)]);

        $signalement = Signalement::where('uuid', $uuid)->firstOrFail();

        $this->app['auth']->forgetGuards();

        $this->patchJson("/api/superviseur/signalements/{$signalement->id}", ['statut' => 'clos'],
            $this->entete($this->jetonSuperviseurArrondissement()))
            ->assertStatus(422);

        $this->assertSame('soumis', $signalement->fresh()->statut->value);
    }

    public function test_un_superviseur_ne_traite_pas_un_signalement_hors_de_sa_portee(): void
    {
        $uuid = (string) Str::uuid();

        $this->envoyer([$this->signalement($uuid)]);

        $signalement = Signalement::where('uuid', $uuid)->firstOrFail();

        $this->app['auth']->forgetGuards();

        // Un identifiant d'URL n'est pas une autorisation.
        $this->patchJson("/api/superviseur/signalements/{$signalement->id}", [
            'statut' => 'clos',
            'suite_donnee' => 'Rien à signaler.',
        ], $this->entete($this->jetonDelegation('ocean@mvoe.test')))->assertForbidden();

        $this->assertSame('soumis', $signalement->fresh()->statut->value);
    }

    public function test_aucune_route_nenvoie_de_notification_a_une_autorite(): void
    {
        // La règle se vérifie dans le code : aucun canal de sortie n'existe.
        // Si un jour quelqu'un en ajoute un, ce test le dira.
        $controleur = \Illuminate\Support\Facades\File::get(
            app_path('Http/Controllers/Api/Superviseur/SignalementController.php'),
        );

        foreach (['Mail::', 'Notification::', 'Http::post', 'dispatch('] as $canal) {
            $this->assertStringNotContainsString($canal, $controleur,
                "Le système ne notifie jamais une autorité : « $canal » n'a rien à faire ici.");
        }
    }

    /* ---------------------------------------------------------------------- */

    private function facilitateur(): Facilitateur
    {
        return Facilitateur::where('nom', 'Ndzana Étienne')->firstOrFail();
    }

    private function envoyer(array $evenements)
    {
        return $this->postJson('/api/facilitateur/evenements', ['evenements' => $evenements],
            $this->entete($this->jetonFacilitateur()))->assertAccepted();
    }

    private function activite(string $uuid, array $charge = []): array
    {
        return [
            'uuid' => $uuid,
            'type' => 'activite',
            'seance_uuid' => null,
            'emis_a' => now()->toIso8601String(),
            'charge' => array_merge([
                'type' => 'causerie_educative',
                'date' => now()->subDay()->toDateString(),
                'lieu' => 'sous le manguier du marché',
                'duree_minutes' => 60,
                'nb_parents_touches' => 20,
                'nb_hommes' => 8,
                'nb_femmes' => 12,
                'nb_participants_handicap' => 0,
            ], $charge),
        ];
    }

    private function foyer(string $uuid): array
    {
        return [
            'uuid' => $uuid,
            'type' => 'foyer',
            'seance_uuid' => null,
            'emis_a' => now()->toIso8601String(),
            'charge' => [
                'localite' => 'quartier Angalé',
                'nb_adultes' => 2,
                'nb_enfants' => 3,
                'difficultes_fonctionnelles_foyer' => ['vision'],
                'deja_suivi_programme' => false,
            ],
        ];
    }

    private function signalement(string $uuid): array
    {
        return [
            'uuid' => $uuid,
            'type' => 'signalement',
            'seance_uuid' => null,
            'emis_a' => now()->toIso8601String(),
            'charge' => ['type' => 'maltraitance', 'gravite' => 'elevee'],
        ];
    }
}
