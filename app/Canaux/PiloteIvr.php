<?php

namespace App\Canaux;

use App\Enums\Canal;

/**
 * Serveur vocal. Le seul canal qui ne demande pas de savoir lire, donc le seul
 * qui atteigne vraiment tout le monde — et le plus cher à la minute.
 */
class PiloteIvr extends PiloteFactice
{
    public function canal(): Canal
    {
        return Canal::Ivr;
    }

    /** Écoutés jusqu'au bout. Les autres raccrochent au bout de vingt secondes. */
    protected function tauxDaboutissement(): float
    {
        return 0.62;
    }
}
