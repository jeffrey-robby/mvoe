<?php

namespace App\Canaux;

use App\Enums\Canal;

/**
 * SMS. Le canal le plus simple et le plus limité : 160 caractères, pas de son,
 * et il suppose de savoir lire.
 */
class PiloteSms extends PiloteFactice
{
    public function canal(): Canal
    {
        return Canal::Sms;
    }

    /** Numéros hors service, téléphones éteints, réseau saturé. */
    protected function tauxDaboutissement(): float
    {
        return 0.87;
    }
}
