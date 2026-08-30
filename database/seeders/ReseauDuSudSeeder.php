<?php

namespace Database\Seeders;

use App\Models\Arrondissement;
use App\Models\Cohorte;
use App\Models\CurriculumVersion;
use App\Models\Facilitateur;
use App\Models\Langue;
use App\Models\ParentProgramme;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * Les soixante-trois autres cohortes de la région du Sud.
 *
 * La cohorte d'Ebolowa II, elle, est écrite à la main dans `CohorteSeeder` :
 * c'est la seule complète en données, parce que c'est celle qu'on ouvre en
 * démonstration. Les soixante-trois autres existent pour une raison précise :
 * **sans elles, le tableau de bord ne démontre rien.** Le ministère, la
 * délégation régionale et le superviseur d'Ebolowa II liraient les mêmes
 * chiffres, et le mécanisme de portée serait invisible à l'œil.
 *
 * Aucun nom de parent ici, ni ailleurs côté serveur. Un code, une langue, une
 * situation — rien qui permette de reconnaître quelqu'un.
 *
 * Tout est déterministe : deux exécutions du seeder produisent exactement la
 * même base, sans quoi une capture d'écran de démonstration serait périmée
 * avant d'être montrée.
 */
class ReseauDuSudSeeder extends Seeder
{
    /** Le total demandé, cohorte de démonstration comprise. */
    public const COHORTES_ATTENDUES = 64;

    /**
     * Les effectifs tournent sur cette liste. Deux valeurs dépassent le plafond
     * de 20 : une cohorte trop pleine est un fait de terrain, pas une anomalie
     * de saisie, et le superviseur doit pouvoir la voir.
     */
    private const EFFECTIFS = [18, 12, 20, 9, 23, 15, 20, 8, 17, 21, 14, 19, 11, 16];

    private const LANGUES = ['bulu', 'bulu', 'fr', 'bulu', 'fr', 'bulu', 'bulu', 'fr'];

    private const STATUTS = ['union', 'union', 'seul', 'union', 'non_renseigne', 'seul'];

    private const REVENUS = ['irregulier', 'irregulier', 'regulier', 'aucun',
        'irregulier', 'non_renseigne', 'regulier'];

    private const JOURS = ['lundi', 'mardi', 'mercredi', 'jeudi', 'vendredi', 'samedi'];

    public function run(): void
    {
        $version = CurriculumVersion::where('active', true)->value('id');
        $facilitateurs = Facilitateur::orderBy('id')->get()->groupBy('arrondissement_id');
        $arrondissements = Arrondissement::orderBy('id')->get();

        // Mille cent codes à hacher. bcrypt est lent par construction, et c'est
        // une qualité : c'est ce qui protège un code à quatre chiffres. Mais
        // reconstruire la base de démonstration prenait plusieurs minutes pour
        // des parents dont AUCUN ne sert à se connecter. On abaisse donc le
        // coût le temps de ce seeder ; les comptes de démonstration, eux, sont
        // hachés au coût réel dans `CohorteSeeder`.
        $hacheur = Hash::driver('bcrypt');
        $cout = (int) config('hashing.bcrypt.rounds', 12);
        $hacheur->setRounds(4);

        try {
            $this->peupler($arrondissements, $facilitateurs, $version);
        } finally {
            $hacheur->setRounds($cout);
        }
    }

    private function peupler($arrondissements, $facilitateurs, ?int $version): void
    {
        $rang = 0;
        $numero = 0;

        foreach ($arrondissements as $arrondissement) {
            $equipe = $facilitateurs->get($arrondissement->id);

            // Un arrondissement sans facilitateur n'a pas de cohorte : personne
            // ne les animerait. Le cas existe, et le tableau de bord doit le
            // montrer à zéro plutôt que de l'omettre.
            if ($equipe === null || $equipe->isEmpty()) {
                continue;
            }

            // Deux cohortes par arrondissement, trois dans les cinq premiers :
            // 5 × 3 + 24 × 2 = 63, plus celle de la démonstration.
            $combien = $numero < 5 ? 3 : 2;
            $numero++;

            for ($index = 1; $index <= $combien; $index++) {
                $rang++;
                $this->cohorte($arrondissement, $equipe, $version, $rang);
            }
        }
    }

    private function cohorte(
        Arrondissement $arrondissement,
        $equipe,
        int $version,
        int $rang,
    ): void {
        $facilitateur = $equipe[$rang % $equipe->count()];
        $jour = self::JOURS[$rang % count(self::JOURS)];

        $cohorte = Cohorte::create([
            'libelle' => $arrondissement->libelle.' — groupe du '.$jour,
            'arrondissement_id' => $arrondissement->id,
            'ratio_max' => 20,
            'curriculum_version_id' => $version,
            'facilitateur_id' => $facilitateur->id,
            'date_debut' => now()->subDays(30 + ($rang * 11) % 240)->toDateString(),
        ]);

        $effectif = self::EFFECTIFS[$rang % count(self::EFFECTIFS)];

        // Trois lettres du lieu, puis le numéro global de la cohorte. Le numéro
        // est ce qui rend le code unique : « Ebolowa I » et « Ebolowa II »
        // donnent les mêmes trois lettres, « Kribi I » et « Kribi II » aussi.
        $prefixe = $this->prefixe($arrondissement->libelle).($rang + 1);

        // Résolues une fois : mille parents n'ont pas besoin de mille requêtes
        // pour retrouver trois langues.
        $langues = Langue::pluck('id', 'code');
        $maintenant = now();
        $lignes = [];

        for ($n = 1; $n <= $effectif; $n++) {
            $graine = $rang * 31 + $n;

            $lignes[] = [
                'cohorte_id' => $cohorte->id,
                'code_parent' => sprintf('%s-%02d', $prefixe, $n),
                // Haché ici parce qu'on écrit en lot : `insert()` court-circuite
                // les casts du modèle, et un code en clair en base serait une
                // faute silencieuse.
                'code_acces' => Hash::make(
                    str_pad((string) (1000 + ($graine * 373) % 8999), 4, '0', STR_PAD_LEFT),
                ),
                'langue_id' => $langues[self::LANGUES[$graine % count(self::LANGUES)]],
                'statut_matrimonial' => self::STATUTS[$graine % count(self::STATUTS)],
                'revenu_regularite' => self::REVENUS[$graine % count(self::REVENUS)],
                // Deux parents sur trois partagent leur téléphone avec le foyer.
                'telephone_partage' => $graine % 3 !== 0,
                'created_at' => $maintenant,
                'updated_at' => $maintenant,
            ];
        }

        // Une seule requête par cohorte au lieu d'une par parent. Mille et un
        // aller-retours vers MySQL coûtaient cinquante secondes à chaque
        // reconstruction de la base et à chaque exécution des tests.
        ParentProgramme::insert($lignes);
    }

    /** Trois lettres tirées du libellé : « Kribi I » donne « KRI ». */
    private function prefixe(string $libelle): string
    {
        return Str::upper(Str::substr(Str::slug($libelle, ''), 0, 3));
    }
}
