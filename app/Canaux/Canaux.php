<?php

namespace App\Canaux;

use App\Enums\Canal;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

/**
 * Le registre des pilotes.
 *
 * Un seul endroit sait quel pilote sert quel canal. Brancher un opérateur réel
 * consiste à remplacer une ligne de ce tableau — le reste du programme ne s'en
 * apercevra pas.
 */
class Canaux
{
    /** @var array<string, class-string<PiloteDeCanal>> */
    private const PILOTES = [
        'sms' => PiloteSms::class,
        'ussd' => PiloteUssd::class,
        'ivr' => PiloteIvr::class,
        'radio' => PiloteRadio::class,
    ];

    public function pour(Canal $canal): PiloteDeCanal
    {
        return app(self::PILOTES[$canal->value]);
    }

    /** @return Collection<int, PiloteDeCanal> */
    public function tous(): Collection
    {
        return collect(Canal::cases())->map(fn (Canal $c) => $this->pour($c));
    }

    /**
     * Le tableau de tous les canaux sur une période.
     *
     * @return array<int, array<string, mixed>>
     */
    public function statistiques(Carbon $du, Carbon $au): array
    {
        return $this->tous()
            ->map(fn (PiloteDeCanal $p) => $p->statistiques($du, $au))
            ->all();
    }
}
