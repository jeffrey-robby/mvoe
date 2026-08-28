<?php

namespace App\Models;

use App\Enums\Langue;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Le libelle d'une option, dans une langue.
 *
 * L'option reste unique : c'est elle que compte `reponses_agregees`. On sait
 * donc combien de parents ont choisi une reponse, quelle que soit la langue
 * dans laquelle ils l'ont lue.
 */
class OptionTraduite extends Model
{
    protected $table = 'options_traduites';

    protected $fillable = ['option_id', 'langue', 'libelle'];

    protected function casts(): array
    {
        return ['langue' => Langue::class];
    }

    public function option(): BelongsTo
    {
        return $this->belongsTo(Option::class);
    }
}
