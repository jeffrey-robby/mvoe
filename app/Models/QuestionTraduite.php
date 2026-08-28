<?php

namespace App\Models;

use App\Enums\Langue;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Le texte d'une question de la semaine, dans une langue.
 *
 * `explication` reste portee par la question et non par l'option, dans toutes
 * les langues : le texte lu apres la reponse est le meme quel que soit le
 * choix du parent. Ni bonne reponse, ni verdict, ni score.
 */
class QuestionTraduite extends Model
{
    protected $table = 'questions_traduites';

    protected $fillable = ['question_id', 'langue', 'enonce', 'enonce_audio', 'explication'];

    protected function casts(): array
    {
        return ['langue' => Langue::class];
    }

    public function question(): BelongsTo
    {
        return $this->belongsTo(Question::class);
    }
}
