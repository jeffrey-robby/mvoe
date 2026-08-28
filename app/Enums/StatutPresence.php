<?php

namespace App\Enums;

enum StatutPresence: string
{
    case Present = 'present';
    case Absent = 'absent';
    case RattrapeBinome = 'rattrape_binome';

    public function libelle(): string
    {
        return match ($this) {
            self::Present => 'Present',
            self::Absent => 'Absent',
            self::RattrapeBinome => 'Rattrape par binome',
        };
    }
}
