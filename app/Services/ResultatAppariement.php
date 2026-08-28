<?php

namespace App\Services;

use App\Models\UniteDigitale;

/**
 * Résultat d'une interrogation de l'assistant.
 *
 * Le refus n'est pas une erreur : c'est un résultat, et il doit être présenté
 * aussi soigneusement qu'une réponse trouvée. `unite` à null signifie qu'aucune
 * unité validée n'a dépassé le seuil, et que l'application doit le dire
 * clairement puis proposer le contact d'un facilitateur.
 */
readonly class ResultatAppariement
{
    public function __construct(
        public string $texte,
        public ?UniteDigitale $unite,
        public float $score,
        public float $seuil,
        /** @var array<int, array{unite_id:int, message_cle:string, score:float}> */
        public array $details = [],
    ) {}

    public function trouve(): bool
    {
        return $this->unite !== null;
    }
}
