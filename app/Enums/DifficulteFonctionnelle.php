<?php

namespace App\Enums;

/**
 * Les domaines du questionnaire court du Washington Group.
 *
 * On ne demande pas « êtes-vous handicapé ? » : on demande ce qu'une personne a
 * du mal à faire. La différence n'est pas de vocabulaire — la première question
 * produit des zéros, la seconde produit des chiffres.
 */
enum DifficulteFonctionnelle: string
{
    case Vision = 'vision';
    case Audition = 'audition';
    case Mobilite = 'mobilite';
    case Comprehension = 'comprehension';
    case Communication = 'communication';

    public function libelle(): string
    {
        return match ($this) {
            self::Vision => 'Voir',
            self::Audition => 'Entendre',
            self::Mobilite => 'Se déplacer',
            self::Comprehension => 'Comprendre, se souvenir',
            self::Communication => 'Se faire comprendre',
        };
    }
}
