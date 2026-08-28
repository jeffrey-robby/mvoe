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
    protected $fillable = ['unite_id', 'ordre'];

    public function unite(): BelongsTo
    {
        return $this->belongsTo(UniteDigitale::class, 'unite_id');
    }

    public function options(): HasMany
    {
        return $this->hasMany(Option::class);
    }

    public function traductions(): HasMany
    {
        return $this->hasMany(QuestionTraduite::class);
    }

    /**
     * Le texte dans la langue demandee, avec repli sur le francais.
     *
     * On renvoie la traduction reellement servie, pas un texte anonyme :
     * l'ecran doit pouvoir dire au parent qu'il lit une version francaise
     * plutot que de la faire passer pour du bulu.
     */
    public function traduction(\App\Enums\Langue $langue): ?QuestionTraduite
    {
        return $this->traductions->firstWhere('langue', $langue)
            ?? $this->traductions->firstWhere('langue', \App\Enums\Langue::Fr);
    }

    public function reponsesAgregees(): HasMany
    {
        return $this->hasMany(ReponseAgregee::class);
    }
}
