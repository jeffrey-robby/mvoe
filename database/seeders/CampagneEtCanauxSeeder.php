<?php

namespace Database\Seeders;

use App\Canaux\Canaux;
use App\Enums\Canal;
use App\Enums\StatutCampagne;
use App\Models\Arrondissement;
use App\Models\Campagne;
use App\Models\CampagneAffectation;
use App\Models\Diffusion;
use App\Models\Langue;
use App\Models\ModuleFormation;
use App\Models\Region;
use App\Models\UniteDigitale;
use App\Models\User;
use App\Services\DeclenchementCampagne;
use Illuminate\Database\Seeder;

/**
 * Deux campagnes et six mois de diffusions.
 *
 * Les campagnes montrent la cascade administrative : l'une déclenchée depuis
 * des mois, avec des échelons qui ont pris connaissance et d'autres non ;
 * l'autre toute fraîche, que personne n'a encore ouverte.
 *
 * Les diffusions servent à une seule démonstration, celle de la radio : on
 * n'invente aucune audience, on mesure le surcroît d'appels et de sessions dans
 * les 48 heures qui suivent une diffusion attestée. Pour que cette mesure ait
 * un sens, il faut donc un vrai creux et un vrai pic — c'est ce que ce seeder
 * fabrique, en assumant que les chiffres sont fictifs mais leur FORME réaliste.
 */
class CampagneEtCanauxSeeder extends Seeder
{
    /** Le surcroît qu'on veut voir apparaître dans les 48 h suivant une émission. */
    private const FACTEUR_APRES_RADIO = 3.2;

    public function run(DeclenchementCampagne $declenchement, Canaux $canaux): void
    {
        $this->campagnes($declenchement);
        $this->diffusions($canaux);
    }

    private function campagnes(DeclenchementCampagne $declenchement): void
    {
        $minproff = User::where('niveau', 'national')->firstOrFail();
        $sud = Region::where('code', 'SU')->orWhere('libelle', 'Sud')->firstOrFail();
        $modules = ModuleFormation::diffusables()->pluck('id');
        $langues = Langue::actives()->pluck('id');

        // 1. Une campagne ancienne, dont la cascade a partiellement avancé.
        $rentree = Campagne::create([
            'titre' => 'Rentrée scolaire — discipline positive',
            'objet' => 'Reprendre les alternatives à la punition physique avant la rentrée, '
                .'période où les tensions dans les foyers augmentent.',
            'module_ids' => $modules->take(2)->values()->all(),
            'langue_ids' => $langues->all(),
            'date_debut' => now()->subDays(70)->toDateString(),
            'date_fin' => now()->addDays(20)->toDateString(),
            'statut' => StatutCampagne::Brouillon,
            'creee_par_id' => $minproff->id,
        ]);

        $declenchement->declencher($rentree, [$sud->id]);

        // La région a pris connaissance, deux départements sur quatre aussi, et
        // seulement une poignée d'arrondissements. C'est exactement ce qu'une
        // cascade réelle donne à voir : elle ne descend pas d'un bloc.
        $this->marquerRecues($rentree, 'region', 1);
        $this->marquerRecues($rentree, 'departement', 2);
        $this->marquerRecues($rentree, 'arrondissement', 9);
        $this->marquerRecues($rentree, 'facilitateur', 11);

        // 2. Une campagne toute fraîche : personne n'a encore ouvert.
        $ecoute = Campagne::create([
            'titre' => 'Écoute et révélation',
            'objet' => 'Diffuser la fiche de conduite à tenir face à une révélation.',
            'module_ids' => $modules->take(1)->values()->all(),
            'langue_ids' => $langues->take(2)->values()->all(),
            'date_debut' => now()->subDays(3)->toDateString(),
            'date_fin' => now()->addDays(60)->toDateString(),
            'statut' => StatutCampagne::Brouillon,
            'creee_par_id' => $minproff->id,
        ]);

        $declenchement->declencher($ecoute, [$sud->id]);
    }

    /** Les `$combien` premières affectations d'un niveau prennent connaissance. */
    private function marquerRecues(Campagne $campagne, string $niveau, int $combien): void
    {
        CampagneAffectation::where('campagne_id', $campagne->id)
            ->where('niveau', $niveau)
            ->orderBy('entite_id')
            ->limit($combien)
            ->get()
            ->each(fn (CampagneAffectation $a, int $rang) => $a->update([
                'statut' => 'recue',
                'date_reception' => $campagne->date_debut->copy()->addDays(2 + $rang * 3),
            ]));
    }

    /**
     * Six mois de diffusions, sur les quatre canaux.
     *
     * Le pilote de chaque canal calcule lui-même ce qui aboutit : le seeder ne
     * fabrique pas de taux, il déclare des volumes et laisse le pilote faire.
     * C'est la même mécanique qu'en production, avec un pilote factice.
     */
    private function diffusions(Canaux $canaux): void
    {
        $langues = Langue::actives()->get();
        $unites = UniteDigitale::orderBy('id')->get();
        $arrondissements = Arrondissement::whereHas('region',
            fn ($q) => $q->where('peuplee', true))->orderBy('id')->get();

        if ($unites->isEmpty() || $arrondissements->isEmpty()) {
            return;
        }

        // Les émissions radio : une toutes les trois semaines, attestées par la
        // station. Une seule ne l'est pas — une diffusion déclarée mais dont
        // personne n'a signé le passage.
        $emissions = collect(range(0, 7))->map(
            fn (int $n) => now()->subDays(170 - $n * 21)->setTime(19, 30),
        );

        foreach ($emissions as $rang => $quand) {
            Diffusion::create([
                'canal' => Canal::Radio,
                'unite_id' => $unites[$rang % $unites->count()]->id,
                'langue_id' => $langues[$rang % $langues->count()]->id,
                'arrondissement_id' => $arrondissements[$rang % $arrondissements->count()]->id,
                'cible' => 'Radio communautaire d\'Ebolowa — 19 h 30',
                'date' => $quand,
                'volume' => 1,
                'aboutis' => $rang === 5 ? 0 : 1,
                'statut' => 'diffusee',
                // Sans attestation, une diffusion déclarée n'est qu'une intention.
                'atteste_par' => $rang === 5 ? null : 'Station communautaire d\'Ebolowa',
            ]);
        }

        // Les autres canaux, jour après jour. Le volume triple dans les 48 h
        // qui suivent une émission attestée : c'est ce que la mesure doit
        // retrouver, et c'est la seule chose que ce seeder met en scène.
        $attestees = $emissions->filter(fn ($q, $r) => $r !== 5);

        foreach (range(0, 175) as $jour) {
            $date = now()->subDays(175 - $jour)->setTime(11, 0);

            $apresEmission = $attestees->contains(
                fn ($emission) => $date->between($emission, $emission->copy()->addHours(48)),
            );

            foreach ([Canal::Ivr, Canal::Ussd, Canal::Sms] as $canal) {
                $base = match ($canal) {
                    Canal::Ivr => 18 + $jour % 7,
                    Canal::Ussd => 34 + $jour % 11,
                    Canal::Sms => 120 + $jour % 40,
                };

                // Le SMS ne réagit pas à la radio : il est poussé, pas appelé.
                // Le confondre avec les canaux entrants fausserait la mesure.
                $volume = $apresEmission && $canal !== Canal::Sms
                    ? (int) round($base * self::FACTEUR_APRES_RADIO)
                    : $base;

                $resultat = $canaux->pour($canal)->envoyer(['volume' => $volume]);

                Diffusion::create([
                    'canal' => $canal,
                    'unite_id' => $unites[$jour % $unites->count()]->id,
                    'langue_id' => $langues[$jour % $langues->count()]->id,
                    'arrondissement_id' => $arrondissements[$jour % $arrondissements->count()]->id,
                    'cible' => match ($canal) {
                        Canal::Ivr => 'Serveur vocal — 8080',
                        Canal::Ussd => 'Menu USSD — *880#',
                        Canal::Sms => 'Parents inscrits, Sud',
                    },
                    'date' => $date,
                    'volume' => $resultat['volume'],
                    'aboutis' => $resultat['aboutis'],
                    'statut' => $resultat['statut'],
                ]);
            }
        }
    }
}
