<?php

namespace App\Services;

use App\Models\Appariement;
use App\Models\UniteDigitale;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

/**
 * L'assistant à corpus fermé.
 *
 * AUCUN modèle de langage génératif n'intervient ici, et il ne faut pas en
 * introduire. Le texte du parent est comparé aux `message_cle` des unités
 * digitales ; si le meilleur score dépasse le seuil, l'unité est restituée
 * MOT POUR MOT avec sa référence de module. Sinon l'application dit qu'elle ne
 * sait pas et propose un facilitateur.
 *
 * Toute phrase lue par un parent a donc été écrite puis validée par le
 * ministère : chaque réponse est vérifiable ligne à ligne contre le guide.
 *
 * Le score est volontairement simple et explicable — on doit pouvoir le
 * défendre devant un jury :
 *
 *     score = (poids des mots de la question retrouvés dans l'unité)
 *             / (poids de tous les mots significatifs de la question)
 *
 * Le poids d'un mot est son IDF sur le corpus : un mot présent dans presque
 * toutes les unités (« enfant ») ne prouve rien, un mot rare (« frapper »)
 * prouve beaucoup. Le score vaut donc entre 0 et 1, et se lit comme « quelle
 * part de la question cette unité couvre-t-elle ».
 *
 * Le seuil vit dans config/mvoe.php et doit rester HAUT : mieux vaut refuser
 * une question à laquelle on aurait pu répondre que répondre à côté sur un
 * sujet de protection de l'enfance.
 */
class AppariementCorpus
{
    /**
     * Mots trop fréquents pour porter du sens. « bien », « mal », « peur »
     * n'y sont pas : ce sont justement des mots du programme.
     */
    private const MOTS_VIDES = [
        'les', 'des', 'une', 'aux', 'que', 'qui', 'quoi', 'dont', 'mais', 'donc',
        'car', 'ni', 'est', 'sont', 'suis', 'etre', 'avoir', 'pas', 'plus',
        'moins', 'pour', 'par', 'sur', 'sous', 'dans', 'avec', 'sans', 'chez',
        'mon', 'ton', 'son', 'mes', 'tes', 'ses', 'notre', 'votre', 'leur',
        'leurs', 'nos', 'vos', 'cette', 'cet', 'ces', 'celui', 'celle', 'ceux',
        'elle', 'elles', 'ils', 'nous', 'vous', 'lui', 'moi', 'toi', 'soi',
        'comme', 'quand', 'alors', 'aussi', 'tout', 'tous', 'toute', 'toutes',
        'meme', 'encore', 'deja', 'jamais', 'toujours', 'tres', 'trop', 'peu',
        'beaucoup', 'ainsi', 'depuis', 'apres', 'avant', 'entre', 'vers',
        'faut', 'fait', 'faire', 'sais', 'savoir', 'veux', 'voudrais', 'peut',
        'peux', 'pouvoir', 'quelque', 'quelques', 'autre', 'autres', 'ete',
        'est-ce', 'oui', 'non', 'des', 'ver',
    ];

    /**
     * Suffixes retirés pour rapprocher « frapper » de « frappe » et
     * « règles » de « règle ». Racinisation volontairement rudimentaire :
     * une vraie lemmatisation serait plus juste, mais moins prévisible, et
     * ici la prévisibilité compte davantage que le rappel.
     */
    private const SUFFIXES = [
        'issements', 'issement', 'ations', 'ation', 'ements', 'ement',
        'eraient', 'erions', 'erait', 'eront', 'irent', 'erent',
        'ions', 'iez', 'ent', 'ons', 'ait', 'ais', 'aient',
        'er', 'ez', 'ir', 're', 'es', 's', 'e',
    ];

    /** @var array<string, float>|null */
    private ?array $idf = null;

    /** @var Collection<int, UniteDigitale>|null */
    private ?Collection $corpus = null;

    public function seuil(): float
    {
        return (float) config('mvoe.assistant.seuil');
    }

    public function chercher(string $texte): ResultatAppariement
    {
        $motsQuestion = $this->racines($texte);

        $scores = $this->corpus()->map(fn (UniteDigitale $unite) => [
            'unite' => $unite,
            'score' => $this->score($motsQuestion, $this->racines($unite->message_cle)),
        ])->sortByDesc('score')->values();

        $meilleur = $scores->first();
        $score = $meilleur['score'] ?? 0.0;

        return new ResultatAppariement(
            texte: $texte,
            // En dessous du seuil, on ne renvoie AUCUNE unité : pas de
            // « meilleure approximation », pas de réponse à côté.
            unite: $score >= $this->seuil() ? $meilleur['unite'] : null,
            score: $score,
            seuil: $this->seuil(),
            details: $scores->map(fn (array $l) => [
                'unite_id' => $l['unite']->id,
                'message_cle' => $l['unite']->message_cle,
                'score' => round($l['score'], 3),
            ])->all(),
        );
    }

    /**
     * Journal de l'assistant : SANS parent_id, sans identifiant de session,
     * sans rien qui permette de relier une question à une personne. Il sert à
     * repérer ce que le corpus ne couvre pas encore, jamais à profiler.
     */
    public function journaliser(ResultatAppariement $resultat): void
    {
        Appariement::create([
            'texte_question' => $resultat->texte,
            'unite_id' => $resultat->unite?->id,
            'score' => round($resultat->score, 3),
            'date' => Carbon::now(),
        ]);
    }

    /**
     * @param  array<int, string>  $question
     * @param  array<int, string>  $unite
     */
    private function score(array $question, array $unite): float
    {
        if ($question === []) {
            return 0.0;
        }

        $total = 0.0;
        $retrouve = 0.0;

        foreach ($question as $mot) {
            $poids = $this->idf()[$mot] ?? $this->idfMotInconnu();
            $total += $poids;

            if (in_array($mot, $unite, true)) {
                $retrouve += $poids;
            }
        }

        return $total > 0 ? $retrouve / $total : 0.0;
    }

    /**
     * @return Collection<int, UniteDigitale>
     */
    private function corpus(): Collection
    {
        return $this->corpus ??= UniteDigitale::with('module', 'sequence')->get();
    }

    /**
     * @return array<string, float>
     */
    private function idf(): array
    {
        if ($this->idf !== null) {
            return $this->idf;
        }

        $documents = $this->corpus()->map(fn (UniteDigitale $u) => $this->racines($u->message_cle));
        $total = max(1, $documents->count());

        $frequences = [];

        foreach ($documents as $mots) {
            foreach ($mots as $mot) {
                $frequences[$mot] = ($frequences[$mot] ?? 0) + 1;
            }
        }

        return $this->idf = collect($frequences)
            ->map(fn (int $df) => log(1 + $total / $df))
            ->all();
    }

    /**
     * Un mot absent du corpus pèse autant que le plus rare des mots connus :
     * une question pleine de mots inconnus doit s'effondrer sous le seuil.
     */
    private function idfMotInconnu(): float
    {
        return log(1 + max(1, $this->corpus()->count()));
    }

    /**
     * @return array<int, string>
     */
    private function racines(string $texte): array
    {
        $mots = preg_split('/[^a-z0-9]+/', $this->normaliser($texte), -1, PREG_SPLIT_NO_EMPTY) ?: [];

        return collect($mots)
            ->filter(fn (string $mot) => mb_strlen($mot) >= 3 && ! in_array($mot, self::MOTS_VIDES, true))
            ->map(fn (string $mot) => $this->racine($mot))
            ->unique()
            ->values()
            ->all();
    }

    private function normaliser(string $texte): string
    {
        $accents = [
            'à' => 'a', 'â' => 'a', 'ä' => 'a', 'á' => 'a', 'ã' => 'a',
            'ç' => 'c', 'é' => 'e', 'è' => 'e', 'ê' => 'e', 'ë' => 'e',
            'î' => 'i', 'ï' => 'i', 'í' => 'i', 'ô' => 'o', 'ö' => 'o',
            'ó' => 'o', 'õ' => 'o', 'ù' => 'u', 'û' => 'u', 'ü' => 'u',
            'ú' => 'u', 'ÿ' => 'y', 'ñ' => 'n', 'œ' => 'oe', 'æ' => 'ae',
        ];

        return strtr(mb_strtolower($texte, 'UTF-8'), $accents);
    }

    private function racine(string $mot): string
    {
        foreach (self::SUFFIXES as $suffixe) {
            if (str_ends_with($mot, $suffixe) && mb_strlen($mot) - mb_strlen($suffixe) >= 4) {
                return mb_substr($mot, 0, mb_strlen($mot) - mb_strlen($suffixe));
            }
        }

        return $mot;
    }
}
