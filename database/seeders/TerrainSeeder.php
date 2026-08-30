<?php

namespace Database\Seeders;

use App\Enums\DifficulteFonctionnelle;
use App\Enums\GraviteSignalement;
use App\Enums\StatutSignalement;
use App\Enums\TypeActivite;
use App\Enums\TypeSignalement;
use App\Models\Cohorte;
use App\Models\Facilitateur;
use App\Models\User;
use App\Services\ReceptionEvenements;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

/**
 * Le travail de terrain : activités, groupes de soutien, foyers, visites,
 * signalements.
 *
 * Comme les séances, rien n'est écrit directement : ce seeder rejoue la file
 * d'événements qu'un kit hors ligne aurait remontée. Un seul chemin d'écriture,
 * y compris pour la démonstration.
 *
 * Ce que ces données doivent rendre visible, et qui n'existe nulle part
 * aujourd'hui :
 *
 *   - qu'un facilitateur fait bien autre chose que des séances de cohorte ;
 *   - que le critère « handicap » est un CHIFFRE et non une phrase de rapport ;
 *   - que des groupes de soutien créés il y a un an ne se réunissent plus ;
 *   - qu'un signalement peut attendre des semaines dans une file.
 */
class TerrainSeeder extends Seeder
{
    /** Le brief en demande 120, de tous les types. */
    private const ACTIVITES = 120;

    private const FOYERS = 40;

    private const GROUPES = 15;

    /** Les types, hors séance de cohorte : celles-ci viennent des vraies séances. */
    private const TYPES_TERRAIN = [
        TypeActivite::CauserieEducative,
        TypeActivite::AtelierPratique,
        TypeActivite::PorteAPorte,
        TypeActivite::VisiteDomicile,
        TypeActivite::ReunionGsp,
        TypeActivite::SensibilisationPublique,
    ];

    private const LIEUX = [
        'sous le manguier du marché', 'salle communautaire', 'cour de l\'école publique',
        'parvis de la paroisse', 'case de santé', 'foyer des femmes',
        'place du village', 'préau du centre social',
    ];

    private const LOCALITES = [
        'quartier Nko\'ovos', 'quartier Angalé', 'village Mvomeka\'a', 'quartier Ekombitié',
        'village Nkolandom', 'quartier Mekomo', 'village Elat', 'quartier Nkoemvone',
    ];

    /** Les observations d'une visite : des cases cochées, jamais un récit. */
    private const OBSERVATIONS = [
        'espace_de_jeu', 'routine_du_coucher', 'repas_partages',
        'enfant_scolarise', 'tensions_dans_le_foyer', 'entourage_present',
    ];

    public function run(ReceptionEvenements $reception): void
    {
        // Déterministe : deux exécutions produisent la même base, sinon une
        // capture d'écran de démonstration serait périmée avant d'être montrée.
        //
        // On écarte les facilitateurs qui n'ont JAMAIS été actifs. Leur donner
        // des causeries les rendrait actifs — une activité remontée est une
        // preuve de travail — et effacerait ce que le registre doit rendre
        // visible : que six personnes formées n'ont jamais rien tenu.
        $facilitateurs = Facilitateur::whereNotNull('derniere_activite')
            ->orderBy('id')
            ->get();
        $cohortes = Cohorte::orderBy('id')->get()->keyBy('facilitateur_id');

        $groupes = $this->groupes($reception, $facilitateurs, $cohortes);
        $this->activites($reception, $facilitateurs, $cohortes, $groupes);
        $this->foyersEtVisites($reception, $facilitateurs);
        $this->signalements($reception, $facilitateurs);
        $this->traiterQuelquesSignalements();
    }

    /**
     * Quinze groupes de soutien. Plusieurs ne se sont pas réunis depuis
     * longtemps — c'est précisément ce que personne ne sait aujourd'hui.
     */
    private function groupes(ReceptionEvenements $reception, $facilitateurs, $cohortes): array
    {
        $crees = [];

        for ($rang = 0; $rang < self::GROUPES; $rang++) {
            $facilitateur = $facilitateurs[($rang * 3) % $facilitateurs->count()];
            $cohorte = $cohortes->get($facilitateur->id);

            if ($cohorte === null) {
                continue;
            }

            $uuid = (string) Str::uuid();
            $membres = $cohorte->parents()->orderBy('id')->limit(4 + $rang % 5)
                ->pluck('code_parent')->all();

            $this->remonter($reception, $facilitateur, [[
                'uuid' => $uuid,
                'type' => 'groupe_soutien',
                'seance_uuid' => null,
                'emis_a' => now()->subDays(300 - $rang * 12)->toIso8601String(),
                'charge' => [
                    'libelle' => $cohorte->arrondissement->libelle.' — groupe de soutien '.($rang + 1),
                    'cohorte_id' => $cohorte->id,
                    'date_creation' => now()->subDays(300 - $rang * 12)->toDateString(),
                    'membres' => $membres,
                ],
            ]]);

            $crees[] = ['uuid' => $uuid, 'facilitateur' => $facilitateur, 'rang' => $rang];
        }

        return $crees;
    }

    /**
     * Cent vingt activités, de tous types, avec leur répartition par sexe et
     * leur nombre de participants en situation de handicap.
     */
    private function activites(ReceptionEvenements $reception, $facilitateurs, $cohortes, array $groupes): void
    {
        for ($rang = 0; $rang < self::ACTIVITES; $rang++) {
            $facilitateur = $facilitateurs[($rang * 7) % $facilitateurs->count()];
            $type = self::TYPES_TERRAIN[$rang % count(self::TYPES_TERRAIN)];

            // Des tailles très inégales : un porte-à-porte touche trois
            // personnes, une sensibilisation publique en touche soixante.
            $touches = match ($type) {
                TypeActivite::PorteAPorte, TypeActivite::VisiteDomicile => 2 + $rang % 4,
                TypeActivite::SensibilisationPublique => 40 + $rang % 35,
                TypeActivite::ReunionGsp => 6 + $rang % 8,
                default => 12 + $rang % 26,
            };

            // Les femmes sont majoritaires partout, c'est le fait du terrain.
            $femmes = (int) round($touches * (0.55 + ($rang % 7) / 40));
            $femmes = min($femmes, $touches);
            $hommes = max(0, $touches - $femmes - ($rang % 5 === 0 ? 1 : 0));

            // Autour de 4 % : la part que le programme doit pouvoir prouver.
            $handicap = $rang % 6 === 0 ? 1 + $rang % 3 : 0;

            // Une réunion de groupe se rattache à un groupe : c'est ce qui fait
            // avancer sa dernière réunion.
            $groupe = $type === TypeActivite::ReunionGsp
                ? collect($groupes)->firstWhere('facilitateur.id', $facilitateur->id)
                : null;

            $date = now()->subDays(5 + ($rang * 3) % 260);

            $this->remonter($reception, $facilitateur, [[
                'uuid' => (string) Str::uuid(),
                'type' => 'activite',
                'seance_uuid' => null,
                'emis_a' => $date->toIso8601String(),
                'charge' => array_filter([
                    'type' => $type->value,
                    'date' => $date->toDateString(),
                    'lieu' => self::LIEUX[$rang % count(self::LIEUX)],
                    'duree_minutes' => 45 + ($rang % 5) * 15,
                    'nb_parents_touches' => $touches,
                    'nb_hommes' => $hommes,
                    'nb_femmes' => $femmes,
                    'nb_participants_handicap' => $handicap,
                    'gsp_uuid' => $groupe['uuid'] ?? null,
                    'commentaire' => $rang % 9 === 0
                        ? 'Beaucoup de questions sur les punitions à l\'école.'
                        : null,
                ], fn ($v) => $v !== null),
            ]]);
        }
    }

    /** Quarante foyers, et les visites qui les ont fait naître. */
    private function foyersEtVisites(ReceptionEvenements $reception, $facilitateurs): void
    {
        $domaines = array_map(fn ($d) => $d->value, DifficulteFonctionnelle::cases());

        for ($rang = 0; $rang < self::FOYERS; $rang++) {
            $facilitateur = $facilitateurs[($rang * 5) % $facilitateurs->count()];
            $uuid = (string) Str::uuid();

            // Un foyer sur trois porte au moins une difficulté fonctionnelle.
            $difficultes = $rang % 3 === 0
                ? [$domaines[$rang % count($domaines)]]
                : [];

            if ($rang % 9 === 0) {
                $difficultes[] = $domaines[($rang + 2) % count($domaines)];
            }

            $creeLe = now()->subDays(20 + ($rang * 6) % 200);

            $this->remonter($reception, $facilitateur, [[
                'uuid' => $uuid,
                'type' => 'foyer',
                'seance_uuid' => null,
                'emis_a' => $creeLe->toIso8601String(),
                'charge' => [
                    'localite' => self::LOCALITES[$rang % count(self::LOCALITES)],
                    'nb_adultes' => 1 + $rang % 3,
                    'nb_enfants' => 1 + $rang % 6,
                    'difficultes_fonctionnelles_foyer' => array_values(array_unique($difficultes)),
                    'deja_suivi_programme' => $rang % 4 === 0,
                ],
            ]]);

            // Une à trois visites par foyer.
            for ($n = 0; $n <= $rang % 3; $n++) {
                $date = $creeLe->copy()->addDays($n * 30);

                if ($date->isFuture()) {
                    break;
                }

                $this->remonter($reception, $facilitateur, [[
                    'uuid' => (string) Str::uuid(),
                    'type' => 'visite',
                    'seance_uuid' => null,
                    'emis_a' => $date->toIso8601String(),
                    'charge' => [
                        'foyer_uuid' => $uuid,
                        'date' => $date->toDateString(),
                        'observations_structurees' => array_slice(
                            self::OBSERVATIONS, ($rang + $n) % 3, 2 + $n % 2,
                        ),
                        'suivi_prevu' => ($rang + $n) % 3 !== 0,
                    ],
                ]]);
            }
        }
    }

    /**
     * Huit signalements, à différents stades.
     *
     * Aucun ne porte d'identité, et le système n'a notifié personne : ils
     * attendent dans la file de leur superviseur.
     */
    private function signalements(ReceptionEvenements $reception, $facilitateurs): void
    {
        $cas = [
            [TypeSignalement::Negligence, GraviteSignalement::Faible, 95],
            [TypeSignalement::Maltraitance, GraviteSignalement::Elevee, 80],
            [TypeSignalement::MariagePrecoce, GraviteSignalement::Elevee, 62],
            [TypeSignalement::Vbg, GraviteSignalement::Moyenne, 47],
            [TypeSignalement::Negligence, GraviteSignalement::Moyenne, 33],
            [TypeSignalement::Autre, GraviteSignalement::Faible, 21],
            // Les deux derniers restent non traités : une file vide donnerait à
            // croire que tout est réglé.
            [TypeSignalement::Maltraitance, GraviteSignalement::Elevee, 9],
            [TypeSignalement::Vbg, GraviteSignalement::Moyenne, 3],
        ];

        foreach ($cas as $rang => [$type, $gravite, $joursAvant]) {
            // Six des huit viennent d'Ebolowa II, dont LES DEUX NON TRAITÉS :
            // une file de démonstration où tout serait déjà réglé ne montrerait
            // pas le geste qui compte, celui de traiter.
            $facilitateur = ($rang < 4 || $rang >= 6)
                ? $facilitateurs->firstWhere('nom', 'Ndzana Étienne')
                : $facilitateurs[($rang * 11) % $facilitateurs->count()];

            $this->remonter($reception, $facilitateur, [[
                'uuid' => (string) Str::uuid(),
                'type' => 'signalement',
                'seance_uuid' => null,
                'emis_a' => now()->subDays($joursAvant)->toIso8601String(),
                'charge' => ['type' => $type->value, 'gravite' => $gravite->value],
            ]], now()->subDays($joursAvant));
        }
    }

    /**
     * Six des huit sont traités, avec une suite écrite.
     *
     * Le traitement passe par la même écriture que l'écran du superviseur :
     * un statut, un auteur, une date, et surtout une SUITE que le facilitateur
     * pourra lire.
     */
    private function traiterQuelquesSignalements(): void
    {
        $suites = [
            StatutSignalement::Clos->value => 'Situation vue avec la famille lors d\'une visite. Pas de suite judiciaire nécessaire.',
            StatutSignalement::Oriente->value => 'Transmis au centre social d\'arrondissement, qui a pris le relais.',
            StatutSignalement::Examine->value => 'Reçu et lu. Un entretien est prévu avec le facilitateur avant décision.',
        ];

        // Par date de RÉCEPTION, pas d'insertion : le seeder écrit les huit dans
        // la même seconde, et `created_at` ne distingue donc rien. Ce sont les
        // six plus anciens qui ont été traités ; les deux récents attendent.
        $signalements = \App\Models\Signalement::orderBy('recue_a')->limit(6)->get();

        foreach ($signalements as $rang => $signalement) {
            $statut = [
                StatutSignalement::Clos, StatutSignalement::Oriente,
                StatutSignalement::Oriente, StatutSignalement::Examine,
                StatutSignalement::Clos, StatutSignalement::Examine,
            ][$rang];

            $superviseur = User::where('niveau', 'arrondissement')
                ->where('arrondissement_id', $signalement->arrondissement_id)
                ->first();

            $signalement->update([
                'statut' => $statut,
                'suite_donnee' => $suites[$statut->value],
                'traite_par_superviseur_id' => $superviseur?->id,
                'date_traitement' => $signalement->recue_a->copy()->addDays(2 + $rang * 3),
            ]);
        }
    }

    /** Un envoi de file, avec le contrôle qu'aucun événement n'a été rejeté. */
    private function remonter(
        ReceptionEvenements $reception,
        Facilitateur $facilitateur,
        array $file,
        ?Carbon $recuA = null,
    ): void {
        $bilan = $reception->recevoir($file, $facilitateur, $recuA);

        if ($bilan['rejetes'] !== []) {
            throw new \RuntimeException(
                'Événement de démonstration rejeté : '.json_encode($bilan['rejetes'])
            );
        }
    }
}
