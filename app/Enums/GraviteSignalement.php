<?php

namespace App\Enums;

enum GraviteSignalement: string
{
    case Faible = 'faible';
    case Moyenne = 'moyenne';
    case Elevee = 'elevee';

    public function libelle(): string
    {
        return match ($this) {
            self::Faible => 'Faible',
            self::Moyenne => 'Moyenne',
            self::Elevee => 'Élevée',
        };
    }
}
