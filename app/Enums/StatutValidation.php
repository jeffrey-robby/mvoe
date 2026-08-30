<?php

namespace App\Enums;

/**
 * Le parcours de validation d'un contenu, du brouillon a la diffusion.
 *
 * **Un contenu non valide ne peut pas etre diffuse.** Ce n'est pas une consigne
 * dans une documentation : c'est une condition dans les requetes qui servent
 * les contenus, et un test echoue si elle disparait. Un module de formation mal
 * relu qui atteint cinquante facilitateurs se rattrape mal.
 */
enum StatutValidation: string
{
    case Brouillon = 'brouillon';
    case Soumis = 'soumis';
    case Valide = 'valide';

    public function libelle(): string
    {
        return match ($this) {
            self::Brouillon => 'Brouillon',
            self::Soumis => 'Soumis à validation',
            self::Valide => 'Validé',
        };
    }

    public function estDiffusable(): bool
    {
        return $this === self::Valide;
    }
}
