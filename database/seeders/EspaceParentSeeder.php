<?php

namespace Database\Seeders;

use App\Models\Langue;
use App\Models\Episode;
use App\Models\Feuilleton;
use App\Models\Option;
use App\Models\Question;
use App\Models\ReponseAgregee;
use App\Models\SituationFrequente;
use App\Models\UniteDigitale;
use Illuminate\Database\Seeder;

/**
 * L'espace parent : feuilleton, questions de la semaine, entrée guidée de
 * l'assistant. Espace secondaire et optionnel — la majorité de la cohorte
 * n'y accédera jamais et sera servie par la séance, le binôme et la radio.
 */
class EspaceParentSeeder extends Seeder
{
    /** Le feuilleton, adossé au module 8. Quatre épisodes de 3 à 5 minutes. */
    private const EPISODES = [
        [1, "Le jour où Elombo a cassé la lampe", 210, 1],
        [2, "Ce que Papa Zé a dit ce soir-là", 245, 3],
        [3, 'La règle du soir', 195, 5],
        [4, 'Ce que Mama Ngo a remarqué', 260, 6],
    ];

    /**
     * Six questions de la semaine. `explication` est portée par la question :
     * le texte lu après la réponse est le même quel que soit le choix du parent.
     * Aucun verdict, aucun score, aucun total — pas même en base.
     *
     * [rang de l'unité, énoncé, explication, [[libellé, pictogramme, attendue], ...]]
     */
    private const QUESTIONS = [
        [
            1,
            "Votre enfant de cinq ans renverse un seau d'eau dans la maison. Que faites-vous d'abord ?",
            "Le programme propose de montrer d'abord le geste attendu : « prends le chiffon, on essuie ensemble ». L'enfant apprend ce qu'il doit faire, et il retient le geste plutôt que la peur.",
            [
                ['Je le gronde tout de suite', 'visage-fache', false],
                ["Je lui montre comment essuyer", 'main-ouverte', true],
                ['Je ne dis rien et je nettoie', 'silence', false],
            ],
        ],
        [
            2,
            "Votre enfant vous obéit, puis recommence dès que vous sortez. Qu'est-ce que cela veut dire ?",
            "Le programme lit cela comme un signe que la règle n'est pas encore comprise, seulement crainte. Une règle expliquée et répétée tient même quand l'adulte n'est pas là.",
            [
                ["Qu'il est têtu", 'enfant-boude', false],
                ["Qu'il obéit par peur, pas par compréhension", 'enfant-craintif', true],
            ],
        ],
        [
            3,
            'Un voisin dit que la chicote fait de bons enfants. Que répond le programme ?',
            "Le programme rappelle que l'enfant frappé retient surtout que le plus fort a raison, et qu'il répète ce geste avec les plus petits. La loi camerounaise protège l'enfant de ces violences.",
            [
                ["C'est comme cela qu'on a été élevé", 'ancien', false],
                ["L'enfant apprend que la force donne raison", 'main-barree', true],
                ['Cela dépend de la faute', 'balance', false],
            ],
        ],
        [
            4,
            "Votre enfant a fait quelque chose de mal. Que lui dites-vous en plus de « ne fais pas ça » ?",
            "Le programme propose de nommer le geste attendu, en une phrase courte, et de le montrer une fois. « Ne fais pas ça » laisse l'enfant sans solution de rechange.",
            [
                ['Rien de plus, il a compris', 'silence', false],
                ["Ce qu'il doit faire à la place", 'geste-montre', true],
            ],
        ],
        [
            5,
            'Combien de règles tenez-vous à la maison ?',
            "Le programme propose peu de règles — trois suffisent — dites avec des mots que l'enfant comprend, et tenues par tous les adultes du foyer de la même façon.",
            [
                ['Beaucoup, selon les jours', 'nuage', false],
                ['Trois règles, toujours les mêmes', 'trois-traits', true],
                ["Aucune, on voit au cas par cas", 'point-interrogation', false],
            ],
        ],
        [
            6,
            'Quand avez-vous dit à votre enfant quelque chose de bien sur lui pour la dernière fois ?',
            "Le programme propose de nommer chaque jour un geste précis que l'enfant a bien fait. Un enfant grandit avec ce qu'on remarque de bon en lui, et cela ne coûte rien.",
            [
                ["Aujourd'hui", 'soleil', true],
                ['Cette semaine', 'calendrier', false],
                ['Je ne me souviens pas', 'nuage', false],
            ],
        ],
    ];

    /**
     * Douze situations fréquentes, pour le parent qui ne sait pas écrire.
     *
     * Ce ne sont PAS des réponses pré-écrites : chaque libellé passe par le
     * même appariement que le texte libre. Les quatre dernières relèvent de
     * modules encore vides ou sortent du programme — l'assistant doit y
     * répondre qu'il ne sait pas et proposer un facilitateur. Ce refus est la
     * démonstration, pas un défaut.
     */
    private const SITUATIONS = [
        ["Mon enfant n'obéit que si je crie", 'megaphone'],
        ['Je ne sais pas comment le corriger sans le frapper', 'main-barree'],
        ["Mon enfant recommence dès que j'ai le dos tourné", 'adulte-de-dos'],
        ['Je voudrais poser des règles à la maison', 'maison'],
        ["Mon enfant se trompe et je ne sais pas quoi lui dire", 'bulle-parole'],
        ["Je voudrais l'encourager quand il fait bien", 'pouce-leve'],
        ['Est-ce que la fessée aide un enfant à comprendre ?', 'point-interrogation'],
        ['Mon enfant a peur de moi', 'enfant-craintif'],
        // À partir d'ici, hors corpus : aucune unité ne peut y répondre.
        ['Mon enfant tousse depuis trois jours', 'thermometre'],
        ["Je n'ai pas de quoi payer la rentrée scolaire", 'cartable'],
        ['Mon mari rentre tard et crie sur les enfants', 'nuage-orage'],
        ["Mon enfant de deux ans ne parle pas encore", 'bouche'],
    ];

    public function run(): void
    {
        $unites = UniteDigitale::orderBy('id')->get()->values();

        $this->feuilletons($unites);
        $this->questions($unites);
        $this->situations();
    }

    /**
     * Un feuilleton par langue. Les textes bulu portent le marqueur [BU] :
     * ils attendent une vraie traduction, on n'en invente pas.
     */
    private function feuilletons($unites): void
    {
        foreach (Langue::whereIn('code', ['fr', 'bulu'])->orderBy('ordre')->get() as $langue) {
            $marque = $langue->code === 'bulu' ? '[BU] ' : '';

            $feuilleton = Feuilleton::create([
                'titre' => $marque.'La maison de Mama Ngo',
                'langue_id' => $langue->id,
                'resume' => $marque."Dans une concession d'Ebolowa, Mama Ngo élève trois enfants. Chaque épisode suit une soirée ordinaire où quelque chose se joue entre un adulte et un enfant.",
            ]);

            foreach (self::EPISODES as [$numero, $titre, $duree, $rangUnite]) {
                Episode::create([
                    'feuilleton_id' => $feuilleton->id,
                    'numero' => $numero,
                    'titre' => $marque.$titre,
                    'fichier_audio' => sprintf('audio/feuilleton/%s-ep%d.wav', $langue->code, $numero),
                    'duree' => $duree,
                    'unite_id' => $unites[$rangUnite - 1]->id,
                ]);
            }
        }
    }

    /**
     * Les questions de la semaine n'ont pas de champ `langue` au schéma :
     * elles sont donc en français seulement pour l'instant.
     */
    private function questions($unites): void
    {
        foreach (self::QUESTIONS as $rang => [$rangUnite, $enonce, $explication, $options]) {
            $question = Question::create([
                'unite_id' => $unites[$rangUnite - 1]->id,
                'enonce' => $enonce,
                'enonce_audio' => sprintf('audio/questions/q%d-fr.wav', $rang + 1),
                'explication' => $explication,
                'ordre' => $rang + 1,
            ]);

            foreach ($options as $position => [$libelle, $pictogramme, $attendue]) {
                $option = Option::create([
                    'question_id' => $question->id,
                    'libelle' => $libelle,
                    'pictogramme' => $pictogramme,
                    'est_attendue' => $attendue,
                ]);

                // Compteurs agrégés d'un cycle précédent. On sait combien de
                // parents ont choisi une option, jamais lesquels.
                ReponseAgregee::create([
                    'question_id' => $question->id,
                    'option_id' => $option->id,
                    'compteur' => [11, 6, 3][$position] ?? 2,
                ]);
            }
        }
    }

    private function situations(): void
    {
        foreach (Langue::whereIn('code', ['fr', 'bulu'])->orderBy('ordre')->get() as $langue) {
            $marque = $langue->code === 'bulu' ? '[BU] ' : '';

            foreach (self::SITUATIONS as $rang => [$libelle, $pictogramme]) {
                SituationFrequente::create([
                    'libelle' => $marque.$libelle,
                    'pictogramme' => $pictogramme,
                    'langue_id' => $langue->id,
                    'fichier_audio' => sprintf('audio/situations/s%02d-%s.wav', $rang + 1, $langue->code),
                    'ordre' => $rang + 1,
                ]);
            }
        }
    }
}
