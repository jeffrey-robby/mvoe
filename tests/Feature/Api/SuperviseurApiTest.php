<?php

namespace Tests\Feature\Api;

use App\Enums\StatutPresence;
use App\Models\Cohorte;
use App\Models\Presence;
use App\Models\Seance;
use App\Models\User;
use Tests\ApiTestCase;

class SuperviseurApiTest extends ApiTestCase
{
    public function test_le_registre_distingue_les_facilitateurs_actifs_des_inactifs(): void
    {
        $reponse = $this->getJson('/api/superviseur/facilitateurs', $this->entete($this->jetonSuperviseur()))
            ->assertOk();

        // La delegation departementale de la Mvila : ses 8 arrondissements.
        $this->assertSame('Mvila', $reponse->json('portee.libelle'));
        $this->assertSame(8, $reponse->json('portee.arrondissements'));
        $this->assertGreaterThan(0, $reponse->json('synthese.jamais_actifs'));
        $this->assertSame(
            $reponse->json('synthese.formes'),
            $reponse->json('synthese.actifs') + $reponse->json('synthese.inactifs'),
        );
    }

    public function test_le_rapport_trimestriel_chiffre_lecart_declare_observe(): void
    {
        $reponse = $this->getJson('/api/superviseur/rapport?annee=2026&trimestre=3',
            $this->entete($this->jetonSuperviseur()))->assertOk();

        // La cohorte de démonstration a tenu trois séances sur le trimestre.
        // On la retrouve par son libellé : la Mvila en compte vingt-deux, et se
        // fier à une position dans la liste serait se fier au hasard.
        $cohorte = collect($reponse->json('cohortes'))
            ->firstWhere('libelle', 'Ebolowa II — groupe du mardi');

        $this->assertSame(3, $cohorte['seances_tenues']);

        // La deuxième de ces trois séances porte un écart dans les DEUX sens :
        // une séquence déclarée réalisée sans avoir jamais été ouverte, et une
        // séquence ouverte pendant la séance puis déclarée non faite. C'est la
        // confrontation qu'aucun formulaire papier ne peut produire.
        $ligne = collect($reponse->json('facilitateurs'))
            ->firstWhere('nom', 'Ndzana Étienne');

        $this->assertSame(1, $ligne['declarees_jamais_ouvertes']);
        $this->assertSame(1, $ligne['ouvertes_declarees_non_faites']);
        $this->assertNotNull($ligne['delai_moyen_remontee_jours']);
    }

    public function test_les_arrondissements_sont_rendus_en_texte_et_jamais_en_objet(): void
    {
        // Depuis que l'arrondissement est une table, `$modele->arrondissement`
        // rend un modèle, pas une chaîne. Sérialisé tel quel, le client
        // affiche « [object Object] » sans qu'aucun test n'échoue. Ce test
        // vérifie la forme du champ partout où il sort de l'API.
        $rapport = $this->getJson('/api/superviseur/rapport?annee=2026&trimestre=3',
            $this->entete($this->jetonSuperviseur()))->assertOk();

        // Deux jetons différents dans le même test : sans cela, le garde
        // résout encore l'utilisateur de la requête précédente.
        $this->app['auth']->forgetGuards();

        $paquet = $this->getJson('/api/facilitateur/cohortes/1/paquet', $this->entete($this->jetonFacilitateur()))
            ->assertOk();

        $champs = [
            'rapport.cohortes' => $rapport->json('cohortes'),
            'rapport.facilitateurs' => $rapport->json('facilitateurs'),
            'paquet.cohorte' => [$paquet->json('cohorte')],
        ];

        foreach ($champs as $origine => $lignes) {
            foreach ($lignes as $ligne) {
                $this->assertIsString($ligne['arrondissement'],
                    "« $origine » doit rendre le libellé de l'arrondissement, pas le modèle.");
            }
        }
    }

    public function test_la_dose_moyenne_compte_le_rattrapage_par_binome(): void
    {
        $reponse = $this->getJson('/api/superviseur/rapport?annee=2026&trimestre=3',
            $this->entete($this->jetonSuperviseur()))->assertOk();

        // La règle, plutôt qu'un nombre : un parent rattrapé par son binôme a
        // reçu la séance, autrement. Il compte donc dans la dose au même titre
        // qu'un présent. Un nombre écrit en dur ici se périmerait à chaque
        // changement du jeu de démonstration sans rien prouver de plus.
        $seances = Seance::dansLaPortee(
            User::where('email', 'mvila@mvoe.test')->firstOrFail()->portee(),
        )->whereBetween('date', ['2026-07-01', '2026-09-30'])->pluck('id');

        $compter = fn (StatutPresence $statut) => Presence::whereIn('seance_id', $seances)
            ->where('statut', $statut->value)
            ->count();

        $presents = $compter(StatutPresence::Present);
        $rattrapes = $compter(StatutPresence::RattrapeBinome);
        $inscrits = $reponse->collect('cohortes')->sum('effectif');

        // Sans rattrapés, le test ne prouverait rien : on vérifie qu'il y en a.
        $this->assertGreaterThan(0, $rattrapes);

        $this->assertSame(
            round(($presents + $rattrapes) / $inscrits, 2),
            $reponse->json('synthese.dose_moyenne_par_parent'),
        );

        // Et la dose est bien SUPÉRIEURE à celle des seuls présents : c'est
        // exactement là que le rattrapage se voit.
        $this->assertGreaterThan(
            round($presents / $inscrits, 2),
            $reponse->json('synthese.dose_moyenne_par_parent'),
        );
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
