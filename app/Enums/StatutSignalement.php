<?php

namespace App\Enums;

/**
 * Le parcours d'un signalement dans la file du superviseur.
 *
 * Le système ne notifie JAMAIS une autorité automatiquement. Il place le
 * signalement dans cette file, et c'est un humain qui juge et qui décide.
 * Une alerte automatique de maltraitance ferait courir un risque à l'enfant
 * qu'elle prétend protéger : elle prévient avant que quiconque ait vérifié, et
 * parfois elle prévient l'agresseur.
 */
enum StatutSignalement: string
{
    case Soumis = 'soumis';
    case Examine = 'examine';
    case Oriente = 'oriente';
    case Clos = 'clos';

    public function libelle(): string
    {
        return match ($this) {
            self::Soumis => 'À traiter',
            self::Examine => 'Examiné',
            self::Oriente => 'Orienté',
            self::Clos => 'Clos',
        };
    }

    /** Un signalement encore dans la file de quelqu'un. */
    public function estOuvert(): bool
    {
        return $this === self::Soumis || $this === self::Examine;
    }
}
