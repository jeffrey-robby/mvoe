<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Question de la semaine. `explication` est portee par la question et non par
 * l'option : le texte lu apres la reponse est le meme quel que soit le choix
 * du parent. Ni bonne reponse, ni verdict, ni score, ni total.
 */
class Question extends Model
{
    protected $fillable = ['unite_id', 'enonce', 'enonce_audio', 'explication', 'ordre'];

    public function unite(): BelongsTo
    {
        return $this->belongsTo(UniteDigitale::class, 'unite_id');
    }

    public function options(): HasMany
    {
        return $this->hasMany(Option::class);
    }

    public function reponsesAgregees(): HasMany
    {
        return $this->hasMany(ReponseAgregee::class);
    }
}
