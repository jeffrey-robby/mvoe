<?php

namespace Tests\Feature;

use App\Models\Cohorte;
use App\Models\EvenementSync;
use App\Models\Facilitateur;
use App\Models\ParentProgramme;
use Illuminate\Support\Str;
use Tests\ApiTestCase;

/**
 * L'inscription d'un parent par son facilitateur.
 *
 * Le brief demande deux voies d'entrée pour un parent, et interdit par ailleurs
 * tout écran d'inscription publique. Les deux tiennent ensemble parce que
 * l'inscription n'est jamais faite par un visiteur anonyme : **le facilitateur
 * crée le dossier et remet le code en main propre ; le parent l'ACTIVE en se
 * connectant.** Il n'existe aucune route qui crée un parent sans jeton.
 *
 * L'inscription se fait hors ligne, en séance ou en visite à domicile. Elle
 * passe donc par la file d'événements, comme le pointage.
 */
class InscriptionParentTest extends ApiTestCase
{
    private const CODE = '7134';

    public function test_un_facilitateur_inscrit_un_parent_qui_peut_ensuite_se_connecter(): void
    {
        $cohorte = $this->cohorteDuFacilitateur();

        $this->postJson('/api/facilitateur/evenements',
            ['evenements' => [$this->inscription($cohorte, 'EB2-21')]],
            $this->entete($this->jetonFacilitateur()),
        )->assertAccepted()->assertJsonCount(1, 'acceptes');

        $parent = ParentProgramme::where('code_parent', 'EB2-21')->firstOrFail();

        $this->assertSame($cohorte->id, $parent->cohorte_id);
        $this->assertSame('bulu', $parent->langue->code);

        // Le code remis en main propre ouvre bien l'espace parent : c'est
        // l'activation, et c'est le parent qui la fait.
        $this->app['auth']->forgetGuards();

        $this->postJson('/api/parent/session', [
            'code_parent' => 'EB2-21',
            'code_acces' => self::CODE,
            'majeur' => true,
        ])->assertOk()->assertJsonPath('parent.code_parent', 'EB2-21');
    }

    public function test_le_code_dacces_ne_survit_jamais_en_clair(): void
    {
        // Le journal conserve chaque charge utile telle quelle et POUR
        // TOUJOURS : c'est ce qui fait sa valeur de preuve, et c'est aussi ce
        // qui en ferait le pire endroit du système pour un secret.
        $cohorte = $this->cohorteDuFacilitateur();

        $this->postJson('/api/facilitateur/evenements',
            ['evenements' => [$this->inscription($cohorte, 'EB2-22')]],
            $this->entete($this->jetonFacilitateur()),
        )->assertAccepted();

        $journal = EvenementSync::where('type', 'inscription_parent')->firstOrFail();

        $this->assertStringNotContainsString(self::CODE, json_encode($journal->charge));
        $this->assertStringNotContainsString(self::CODE, $journal->getRawOriginal('charge'));

        // Remplacé, pas retiré : on voit qu'un code a bien été transmis.
        $this->assertArrayHasKey('code_acces', $journal->charge);

        // En base non plus, bien sûr.
        $parent = ParentProgramme::where('code_parent', 'EB2-22')->firstOrFail();
        $this->assertStringStartsWith('$2y$', $parent->getRawOriginal('code_acces'));
    }

    public function test_un_facilitateur_ninscrit_personne_dans_la_cohorte_dun_autre(): void
    {
        $ailleurs = Cohorte::where('facilitateur_id', '!=', $this->facilitateur()->id)->firstOrFail();

        $reponse = $this->postJson('/api/facilitateur/evenements',
            ['evenements' => [$this->inscription($ailleurs, 'EB2-23')]],
            $this->entete($this->jetonFacilitateur()),
        )->assertAccepted();

        $this->assertSame([], $reponse->json('acceptes'));
        $this->assertNull(ParentProgramme::where('code_parent', 'EB2-23')->first());
    }

    public function test_renvoyer_la_file_ninscrit_pas_le_parent_deux_fois(): void
    {
        // Le kit renvoie sa file autant de fois qu'il veut : une coupure au
        // milieu d'un envoi ne doit pas créer de doublon.
        $evenement = $this->inscription($this->cohorteDuFacilitateur(), 'EB2-24');
        $jeton = $this->jetonFacilitateur();

        $premier = $this->postJson('/api/facilitateur/evenements',
            ['evenements' => [$evenement]], $this->entete($jeton))->assertAccepted();

        $second = $this->postJson('/api/facilitateur/evenements',
            ['evenements' => [$evenement]], $this->entete($jeton))->assertAccepted();

        $this->assertCount(1, $premier->json('acceptes'));
        $this->assertCount(1, $second->json('doublons'));
        $this->assertSame(1, ParentProgramme::where('code_parent', 'EB2-24')->count());
    }

    public function test_un_parent_inscrit_en_debut_de_seance_est_pointable_dans_la_foulee(): void
    {
        // Le cas réel : quelqu'un arrive au moment où la séance commence. Sa
        // présence ne peut pas se rattacher à un dossier qui n'existe pas
        // encore, et le kit envoie sa file dans l'ordre où les gestes ont eu
        // lieu — pas dans celui où le serveur voudrait les recevoir.
        $cohorte = $this->cohorteDuFacilitateur();
        $seanceUuid = (string) Str::uuid();

        $file = [
            // Volontairement dans le désordre : pointage avant inscription.
            [
                'uuid' => (string) Str::uuid(),
                'type' => 'presence',
                'seance_uuid' => $seanceUuid,
                'emis_a' => now()->toIso8601String(),
                'charge' => ['code_parent' => 'EB2-25', 'statut' => 'present'],
            ],
            $this->inscription($cohorte, 'EB2-25'),
            [
                'uuid' => $seanceUuid,
                'type' => 'seance',
                'seance_uuid' => null,
                'emis_a' => now()->toIso8601String(),
                'charge' => [
                    'cohorte_id' => $cohorte->id,
                    'module_id' => $cohorte->curriculumVersion->modules()->where('numero', 8)->value('id'),
                    'date' => now()->toDateString(),
                ],
            ],
        ];

        $reponse = $this->postJson('/api/facilitateur/evenements', ['evenements' => $file],
            $this->entete($this->jetonFacilitateur()))->assertAccepted();

        $this->assertCount(3, $reponse->json('acceptes'));
        $this->assertSame(1, ParentProgramme::where('code_parent', 'EB2-25')->count());
    }

    public function test_aucune_route_ne_cree_un_parent_sans_jeton(): void
    {
        // La règle du brief : aucun écran d'inscription publique, nulle part.
        // Sans jeton facilitateur, la file est refusée avant même d'être lue.
        $this->postJson('/api/facilitateur/evenements', [
            'evenements' => [$this->inscription($this->cohorteDuFacilitateur(), 'EB2-26')],
        ])->assertUnauthorized();

        $this->assertNull(ParentProgramme::where('code_parent', 'EB2-26')->first());
    }

    public function test_aucun_nom_nest_demande_ni_accepte(): void
    {
        $evenement = $this->inscription($this->cohorteDuFacilitateur(), 'EB2-27');
        $evenement['charge']['nom'] = 'Odile Mballa';

        $this->postJson('/api/facilitateur/evenements', ['evenements' => [$evenement]],
            $this->entete($this->jetonFacilitateur()))->assertAccepted();

        // Le champ est ignoré : le modèle n'a pas de colonne où le mettre, et
        // il n'en aura jamais. Il ne reste pas non plus au journal.
        $parent = ParentProgramme::where('code_parent', 'EB2-27')->firstOrFail();

        $this->assertFalse(array_key_exists('nom', $parent->getAttributes()));
    }

    /* ---------------------------------------------------------------------- */

    private function facilitateur(): Facilitateur
    {
        return Facilitateur::where('nom', 'Ndzana Étienne')->firstOrFail();
    }

    private function cohorteDuFacilitateur(): Cohorte
    {
        return Cohorte::where('libelle', 'Ebolowa II — groupe du mardi')->firstOrFail();
    }

    /** La charge exacte qu'un kit hors ligne aurait mise dans sa file. */
    private function inscription(Cohorte $cohorte, string $code): array
    {
        return [
            'uuid' => (string) Str::uuid(),
            'type' => 'inscription_parent',
            'seance_uuid' => null,
            'emis_a' => now()->toIso8601String(),
            'charge' => [
                'cohorte_id' => $cohorte->id,
                'code_parent' => $code,
                'code_acces' => self::CODE,
                'langue_pref' => 'bulu',
                'statut_matrimonial' => 'union',
                'revenu_regularite' => 'irregulier',
                'telephone_partage' => true,
            ],
        ];
    }
}
