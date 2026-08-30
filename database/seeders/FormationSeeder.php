<?php

namespace Database\Seeders;

use App\Enums\StatutValidation;
use App\Enums\TypeFormation;
use App\Models\Facilitateur;
use App\Models\ModuleFormation;
use App\Models\SectionFormation;
use App\Services\ReceptionEvenements;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

/**
 * Le catalogue destiné au facilitateur.
 *
 * Trois modules, un par nature : ce qu'il a appris à la formation, une capsule
 * courte à rouvrir, et la fiche de conduite à tenir face à une révélation.
 *
 * Un quatrième existe en brouillon. Il ne doit apparaître nulle part côté
 * facilitateur — c'est la démonstration que « un contenu non validé ne peut pas
 * être diffusé » est une condition dans le code et non une consigne.
 */
class FormationSeeder extends Seeder
{
    /**
     * [code, type, titre, objectif, durée, statut, [sections]]
     *
     * Chaque section : [titre, durée, texte].
     */
    private const MODULES = [
        [
            'FI-01', TypeFormation::FormationInitiale,
            'Animer une séance de cohorte',
            'Conduire les cinq séquences d\'un module sans perdre le fil ni le groupe.',
            45, StatutValidation::Valide,
            [
                ['Ouvrir la salle et le groupe', 8,
                    "Le brise-glace n'est pas un échauffement : c'est le moment où la salle "
                    ."prend la parole. Levez-vous, chantez, faites lever tout le monde. Un "
                    ."groupe qui a ri ensemble pose ensuite des questions qu'il n'aurait pas "
                    ."posées assis en silence."],
                ['Tenir le déroulé sans le réciter', 12,
                    "Le déroulé est une échelle, pas un texte. Chaque séquence a une durée "
                    ."officielle : si vous débordez sur la deuxième, c'est la cinquième qui "
                    ."saute — et c'est presque toujours la plus utile, parce qu'elle demande "
                    ."aux parents ce qu'ils feront chez eux cette semaine."],
                ['Faire parler ceux qui se taisent', 15,
                    "Dans chaque cohorte, trois ou quatre personnes parlent, et quinze "
                    ."écoutent. Posez la question au groupe, laissez le silence durer, puis "
                    ."adressez-vous à quelqu'un par son prénom. Le silence de sept secondes "
                    ."est votre meilleur outil ; la plupart des facilitateurs le coupent au "
                    ."bout de deux."],
                ['Clore et fixer un rendez-vous', 10,
                    "Une séance qui se termine sans date suivante perd un tiers du groupe. "
                    ."Annoncez le jour, l'heure et le lieu avant que les gens ne se lèvent, "
                    ."et faites répéter la date à voix haute."],
            ],
        ],
        [
            'RN-01', TypeFormation::RemiseANiveau,
            'Discipliner sans frapper : les trois gestes',
            'Reprendre les trois alternatives concrètes à la punition physique.',
            15, StatutValidation::Valide,
            [
                ['Nommer le comportement, pas l\'enfant', 5,
                    "« Tu as renversé l'eau » et non « tu es maladroit ». La première phrase "
                    ."porte sur un fait qu'on peut réparer ; la seconde sur une personne "
                    ."qu'on ne change pas. Les parents entendent la différence tout de suite "
                    ."quand on la leur fait dire à voix haute."],
                ['Proposer avant d\'interdire', 5,
                    "Un enfant à qui l'on retire quelque chose cherche autre chose. "
                    ."Donnez-lui d'abord ce qu'il peut faire : « pose le verre ici » avant "
                    ."« ne touche pas au verre »."],
                ['Réparer plutôt que punir', 5,
                    "Ce qui est cassé se répare, ce qui est sali se nettoie. La réparation "
                    ."apprend une compétence ; la punition n'apprend qu'à ne pas se faire "
                    ."prendre."],
            ],
        ],
        [
            'CT-01', TypeFormation::ConduiteATenir,
            'Quand un enfant ou un parent vous révèle quelque chose',
            'Savoir quoi faire dans les dix minutes qui suivent une révélation.',
            20, StatutValidation::Valide,
            [
                ['Écouter sans enquêter', 6,
                    "Vous n'êtes pas enquêteur. Ne demandez pas de détails, ne faites pas "
                    ."répéter, ne posez aucune question qui commence par « est-ce que c'est "
                    ."lui qui ». Une audition mal menée abîme un témoignage, et un enfant "
                    ."qu'on fait répéter cesse de parler."],
                ['Ne rien promettre qu\'on ne tiendra pas', 5,
                    "Ne dites jamais « je ne le dirai à personne ». Vous allez le dire à "
                    ."votre superviseur, et c'est ce qu'il faut faire. Dites plutôt : « je "
                    ."vais en parler à quelqu'un qui peut aider, et je te dirai ce qui se "
                    ."passe »."],
                ['Signaler le jour même, sans nommer', 5,
                    "Ouvrez « Signaler une situation » dans votre kit. Vous n'aurez à donner "
                    ."ni nom ni détail : un type, une gravité. Votre superviseur reçoit, et "
                    ."c'est lui qui décide de la suite. Aucune autorité n'est prévenue "
                    ."automatiquement."],
                ['Ce que vous verrez ensuite', 4,
                    "La suite donnée à votre signalement apparaîtra dans votre kit. Si elle "
                    ."tarde, relancez votre superviseur : un signalement sans retour est un "
                    ."signalement qu'on ne refait pas."],
            ],
        ],
        // Non validé : il ne doit apparaître nulle part côté facilitateur.
        [
            'RN-02', TypeFormation::RemiseANiveau,
            'Accueillir un parent en situation de handicap',
            'Adapter une séance à quelqu\'un qui n\'entend pas ou ne se déplace pas.',
            15, StatutValidation::Soumis,
            [
                ['Brouillon en cours de relecture', 15,
                    "Ce module attend la validation du ministère."],
            ],
        ],
    ];

    public function run(ReceptionEvenements $reception): void
    {
        foreach (self::MODULES as $ordre => [$code, $type, $titre, $objectif, $duree, $statut, $sections]) {
            $module = ModuleFormation::create([
                'code' => $code,
                'titre' => $titre,
                'type' => $type,
                'objectif' => $objectif,
                'ordre' => $ordre + 1,
                'duree_minutes' => $duree,
                'statut_validation' => $statut,
            ]);

            foreach ($sections as $rang => [$titreSection, $dureeSection, $texte]) {
                SectionFormation::create([
                    'module_formation_id' => $module->id,
                    'ordre' => $rang + 1,
                    'titre' => $titreSection,
                    'contenu_texte' => $texte,
                    // Muet pour l'instant : l'interface reste utilisable sans.
                    'fichier_audio' => sprintf('audio/formation/%s-s%d.wav', Str::lower($code), $rang + 1),
                    'duree_minutes' => $dureeSection,
                ]);
            }
        }

        $this->progressionDeDemonstration($reception);
    }

    /**
     * Un module terminé, un module commencé, un module jamais ouvert.
     *
     * C'est ce que le superviseur doit voir : trois états différents chez la
     * même personne, dont un qui s'est arrêté au milieu.
     */
    private function progressionDeDemonstration(ReceptionEvenements $reception): void
    {
        $facilitateur = Facilitateur::where('nom', 'Ndzana Étienne')->firstOrFail();

        $file = [
            // Formation initiale : terminée, il y a quelques mois.
            [
                'uuid' => (string) Str::uuid(),
                'type' => 'progression_formation',
                'seance_uuid' => null,
                'emis_a' => now()->subDays(120)->toIso8601String(),
                'charge' => [
                    'module_code' => 'FI-01',
                    'sections_vues' => [1, 2, 3, 4],
                    'ouverte_a' => now()->subDays(120)->toIso8601String(),
                ],
            ],
            // Remise à niveau : commencée, arrêtée au milieu. C'est celle-là
            // que le superviseur doit repérer.
            [
                'uuid' => (string) Str::uuid(),
                'type' => 'progression_formation',
                'seance_uuid' => null,
                'emis_a' => now()->subDays(11)->toIso8601String(),
                'charge' => [
                    'module_code' => 'RN-01',
                    'sections_vues' => [1],
                    'ouverte_a' => now()->subDays(11)->toIso8601String(),
                ],
            ],
            // CT-01 : jamais ouvert. Aucune ligne, et c'est un fait, pas un vide.
        ];

        $bilan = $reception->recevoir($file, $facilitateur, now()->subDays(11));

        if ($bilan['rejetes'] !== []) {
            throw new \RuntimeException(
                'Progression de démonstration rejetée : '.json_encode($bilan['rejetes'])
            );
        }
    }
}
