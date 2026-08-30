<?php

namespace App\Enums;

/**
 * Les trois natures de contenu destine au facilitateur.
 *
 * Ce catalogue est distinct de celui des parents, et c'est deliberé : un
 * facilitateur forme il y a deux ans ne se refait pas former, il rouvre ses
 * modules. Ce faisant il rouvre l'application, donc il reste actif dans le
 * registre — c'est le seul dispositif de reactivation qui ne coute rien.
 */
enum TypeFormation: string
{
    case FormationInitiale = 'formation_initiale';
    case RemiseANiveau = 'remise_a_niveau';
    case ConduiteATenir = 'conduite_a_tenir';

    public function libelle(): string
    {
        return match ($this) {
            self::FormationInitiale => 'Formation initiale',
            self::RemiseANiveau => 'Remise à niveau',
            self::ConduiteATenir => 'Conduite à tenir',
        };
    }

    public function description(): string
    {
        return match ($this) {
            self::FormationInitiale => 'Ce que vous avez appris à la formation.',
            self::RemiseANiveau => 'Une capsule courte, à rouvrir quand vous voulez.',
            self::ConduiteATenir => 'Que faire face à une révélation. À lire avant, pas pendant.',
        };
    }
}
