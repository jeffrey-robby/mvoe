<?php

namespace App\Enums;

/**
 * Ce qu'un facilitateur est juridiquement.
 *
 * Ce n'est pas decoratif : c'est ce qui permettra de savoir quel type de
 * facilitateur reste actif le plus longtemps. Une association de femmes
 * tient-elle mieux qu'un vacataire ? Personne ne peut repondre aujourd'hui.
 */
enum TypeJuridique: string
{
    case AgentPublic = 'agent_public';
    case Enseignant = 'enseignant';
    case Ong = 'ong';
    case AssociationFemmes = 'association_femmes';
    case GroupeReligieux = 'groupe_religieux';
    case RelaisCommunautaire = 'relais_communautaire';
    case Vacataire = 'vacataire';

    public function libelle(): string
    {
        return match ($this) {
            self::AgentPublic => 'Agent public',
            self::Enseignant => 'Enseignant',
            self::Ong => 'ONG',
            self::AssociationFemmes => 'Association de femmes',
            self::GroupeReligieux => 'Groupe religieux',
            self::RelaisCommunautaire => 'Relais communautaire',
            self::Vacataire => 'Vacataire',
        };
    }
}
