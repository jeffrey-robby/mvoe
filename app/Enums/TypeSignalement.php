<?php

namespace App\Enums;

/**
 * Le type d'une situation préoccupante remontée du terrain.
 *
 * Un signalement ne porte JAMAIS l'identité d'un enfant, d'un parent ou d'un
 * foyer : un type, une gravité, un arrondissement, rien de plus. Ce n'est pas
 * une précaution de façade — c'est ce qui permet à un facilitateur de signaler
 * sans exposer quelqu'un dans une base de données.
 */
enum TypeSignalement: string
{
    case Maltraitance = 'maltraitance';
    case Vbg = 'vbg';
    case MariagePrecoce = 'mariage_precoce';
    case Negligence = 'negligence';
    case Autre = 'autre';

    public function libelle(): string
    {
        return match ($this) {
            self::Maltraitance => 'Maltraitance',
            self::Vbg => 'Violence basée sur le genre',
            self::MariagePrecoce => 'Mariage précoce',
            self::Negligence => 'Négligence',
            self::Autre => 'Autre situation préoccupante',
        };
    }
}
