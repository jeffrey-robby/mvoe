<?php

namespace App\Enums;

enum StatutCampagne: string
{
    case Brouillon = 'brouillon';
    case Declenchee = 'declenchee';
    case Close = 'close';

    public function libelle(): string
    {
        return match ($this) {
            self::Brouillon => 'Brouillon',
            self::Declenchee => 'Déclenchée',
            self::Close => 'Close',
        };
    }
}
