<?php

namespace App\Enums;

/**
 * Tout ce qu'un facilitateur fait sur le terrain, pas seulement les séances.
 *
 * Le programme ne se résume pas aux séances de cohorte : une causerie sous
 * l'arbre, un porte-à-porte, une visite à domicile comptent autant. Ne compter
 * que les séances reviendrait à effacer la moitié du travail réel.
 */
enum TypeActivite: string
{
    case SeanceCohorte = 'seance_cohorte';
    case CauserieEducative = 'causerie_educative';
    case AtelierPratique = 'atelier_pratique';
    case PorteAPorte = 'porte_a_porte';
    case VisiteDomicile = 'visite_domicile';
    case ReunionGsp = 'reunion_gsp';
    case SensibilisationPublique = 'sensibilisation_publique';

    public function libelle(): string
    {
        return match ($this) {
            self::SeanceCohorte => 'Séance de cohorte',
            self::CauserieEducative => 'Causerie éducative',
            self::AtelierPratique => 'Atelier pratique',
            self::PorteAPorte => 'Porte-à-porte',
            self::VisiteDomicile => 'Visite à domicile',
            self::ReunionGsp => 'Réunion de groupe de soutien',
            self::SensibilisationPublique => 'Sensibilisation publique',
        };
    }
}
