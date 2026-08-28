<?php

namespace Database\Seeders;

use App\Enums\Langue;
use App\Enums\Modalite;
use App\Enums\TypeSequence;
use App\Models\CurriculumVersion;
use App\Models\Module;
use App\Models\Realisation;
use App\Models\Sequence;
use App\Models\UniteDigitale;
use Illuminate\Database\Seeder;

class CurriculumSeeder extends Seeder
{
    /**
     * Les dix modules du programme national. Seul le module 8 est renseigné.
     * Les neuf autres apparaissent dans l'interface avec leur titre et rien
     * d'autre : montrer l'architecture sans laisser croire qu'elle est prête.
     */
    private const MODULES = [
        1 => "Être parent aujourd'hui",
        2 => 'Connaître son enfant',
        3 => 'Parler avec son enfant',
        4 => "Le jeu et l'éveil",
        5 => "La santé et l'hygiène au quotidien",
        6 => 'Nourrir son enfant',
        7 => "L'école et les apprentissages",
        8 => 'Discipline positive',
        9 => 'Protéger son enfant des violences',
        10 => 'Prendre soin de soi comme parent',
    ];

    /**
     * Le déroulé chronométré du module 8, tel qu'il figure au guide officiel.
     * Les durées totalisent 90 minutes et donnent sa hauteur à chaque bloc
     * dans la colonne de séance.
     */
    private const SEQUENCES = [
        [
            'ordre' => 1,
            'titre' => 'Accueil et brise-glace',
            'duree_minutes' => 10,
            'type' => 'consigne_animation',
            'est_brise_glace' => true,
            'consigne' => 'Tout le monde se lève. On chante et on danse ensemble avant de commencer.',
        ],
        ['ordre' => 2, 'titre' => 'Discipliner, est-ce punir ?', 'duree_minutes' => 20, 'type' => 'unite_digitale'],
        ['ordre' => 3, 'titre' => "Ce que le coup apprend à l'enfant", 'duree_minutes' => 25, 'type' => 'unite_digitale'],
        ['ordre' => 4, 'titre' => "Poser une règle qu'un enfant peut comprendre", 'duree_minutes' => 20, 'type' => 'unite_digitale'],
        ['ordre' => 5, 'titre' => 'Ce que je fais cette semaine à la maison', 'duree_minutes' => 15, 'type' => 'unite_digitale'],
    ];

    /**
     * Les six unités digitales du module 8.
     *
     * `message_cle` est le champ que l'assistant à corpus fermé interroge. Ce
     * sont ces phrases exactes qui seront restituées à un parent, mot pour mot :
     * elles doivent être relisibles ligne à ligne contre le guide du ministère.
     */
    private const UNITES = [
        [
            'sequence' => 2,
            'message_cle' => "Discipliner un enfant, c'est lui apprendre à se conduire, pas lui faire mal.",
            'titre' => "Discipliner, c'est enseigner",
            'texte' => "Discipliner, c'est enseigner à l'enfant comment se conduire. Ce n'est pas le faire souffrir. Un enfant apprend mieux quand on lui montre quoi faire que quand on le punit.",
            'pictos' => ['adulte-montre', 'enfant-ecoute', 'main-ouverte'],
        ],
        [
            'sequence' => 2,
            'message_cle' => "Un enfant qui a peur obéit devant vous, et recommence dès que vous avez le dos tourné.",
            'titre' => 'La peur ne fait pas apprendre',
            'texte' => "Quand l'enfant obéit par peur, il obéit seulement quand vous êtes là. Dès que vous partez, il recommence. Ce que vous vouliez lui apprendre n'est pas entré.",
            'pictos' => ['enfant-craintif', 'adulte-de-dos', 'horloge'],
        ],
        [
            'sequence' => 3,
            'message_cle' => 'Frapper un enfant lui apprend que celui qui est le plus fort a raison.',
            'titre' => 'Ce que le coup enseigne',
            'texte' => "L'enfant qu'on frappe retient une chose : le plus fort a raison. Il la répétera avec les plus petits que lui, à la maison et dehors.",
            'pictos' => ['main-barree', 'deux-enfants', 'balance'],
        ],
        [
            'sequence' => 3,
            'message_cle' => "Quand l'enfant se trompe, dites-lui ce qu'il doit faire, pas seulement ce qu'il a fait de mal.",
            'titre' => "Dire ce qu'il faut faire",
            'texte' => "« Ne fais pas ça » ne suffit pas : l'enfant ne sait toujours pas quoi faire à la place. Dites-lui le geste attendu, en une phrase courte, et montrez-le une fois.",
            'pictos' => ['bulle-parole', 'geste-montre', 'enfant-fait'],
        ],
        [
            'sequence' => 4,
            'message_cle' => "Une règle claire, courte et répétée vaut mieux que dix règles qu'on n'explique jamais.",
            'titre' => 'Peu de règles, mais claires',
            'texte' => "Choisissez trois règles pour la maison. Dites-les avec des mots que l'enfant comprend. Répétez-les calmement, toujours les mêmes, par tous les adultes du foyer.",
            'pictos' => ['trois-traits', 'maison', 'bulle-parole'],
        ],
        [
            'sequence' => 5,
            'message_cle' => "Félicitez l'enfant quand il fait bien. Un enfant grandit avec ce qu'on remarque de bon en lui.",
            'titre' => 'Remarquer ce qui va bien',
            'texte' => "Cette semaine, dites à votre enfant une chose qu'il a bien faite, chaque jour. Nommez le geste précis. C'est ce qu'il entend de lui-même qui le fait grandir.",
            'pictos' => ['pouce-leve', 'soleil', 'enfant-souriant'],
        ],
    ];

    public function run(): void
    {
        $version = CurriculumVersion::create([
            'label' => 'Guide national de parentalité positive — édition 2024',
            'active' => true,
        ]);

        $modules = [];

        foreach (self::MODULES as $numero => $titre) {
            $modules[$numero] = Module::create([
                'curriculum_version_id' => $version->id,
                'numero' => $numero,
                'titre' => $titre,
                'ordre' => $numero,
            ]);
        }

        $module8 = $modules[8];
        $sequences = [];

        foreach (self::SEQUENCES as $donnees) {
            $sequences[$donnees['ordre']] = Sequence::create([
                'module_id' => $module8->id,
                'titre' => $donnees['titre'],
                'ordre' => $donnees['ordre'],
                'duree_minutes' => $donnees['duree_minutes'],
                'type' => TypeSequence::from($donnees['type']),
                'est_brise_glace' => $donnees['est_brise_glace'] ?? false,
                'consigne' => $donnees['consigne'] ?? null,
            ]);
        }

        foreach (self::UNITES as $rang => $donnees) {
            $unite = UniteDigitale::create([
                'module_id' => $module8->id,
                'sequence_id' => $sequences[$donnees['sequence']]->id,
                'message_cle' => $donnees['message_cle'],
            ]);

            $this->realisations($unite, $rang + 1, $donnees);
        }
    }

    /**
     * Quatre réalisations par unité : français et bulu, en audio et en
     * texte + pictogrammes. Le parcours ne doit dépendre ni de la lecture,
     * ni de la présence effective du fichier audio.
     */
    private function realisations(UniteDigitale $unite, int $rang, array $donnees): void
    {
        foreach ([Langue::Fr, Langue::Bulu] as $langue) {
            // Les textes bulu ne sont pas traduits : ils portent un marqueur
            // explicite. Inventer une traduction serait pire que l'absence.
            $marque = $langue === Langue::Bulu ? '[BU] ' : '';

            Realisation::create([
                'unite_id' => $unite->id,
                'langue' => $langue,
                'modalite' => Modalite::Audio,
                'titre' => $marque.$donnees['titre'],
                'contenu_texte' => null,
                'fichier_audio' => sprintf('audio/unites/m08-u%d-%s.wav', $rang, $langue->value),
                'pictogrammes' => null,
            ]);

            Realisation::create([
                'unite_id' => $unite->id,
                'langue' => $langue,
                'modalite' => Modalite::TextePicto,
                'titre' => $marque.$donnees['titre'],
                'contenu_texte' => $marque.$donnees['texte'],
                'fichier_audio' => null,
                'pictogrammes' => $donnees['pictos'],
            ]);
        }
    }
}
