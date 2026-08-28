<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * On compte les reponses, on ne sait jamais qui a repondu quoi.
 * Aucun parent_id ici, et il ne doit jamais y en avoir.
 */
class ReponseAgregee extends Model
{
    protected $table = 'reponses_agregees';

    protected $fillable = ['question_id', 'option_id', 'compteur'];

    public function question(): BelongsTo
    {
        return $this->belongsTo(Question::class);
    }

    public function option(): BelongsTo
    {
        return $this->belongsTo(Option::class);
    }

    public static function incrementer(int $questionId, int $optionId): void
    {
        static::query()
            ->where('question_id', $questionId)
            ->where('option_id', $optionId)
            ->increment('compteur');
    }
}
