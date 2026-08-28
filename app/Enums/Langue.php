<?php

namespace App\Enums;

enum Langue: string
{
    case Fr = 'fr';
    case En = 'en';
    case Bulu = 'bulu';

    public function libelle(): string
    {
        return match ($this) {
            self::Fr => 'Francais',
            self::En => 'English',
            self::Bulu => 'Bulu',
        };
    }
}
