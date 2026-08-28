<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Episode extends Model
{
    protected $fillable = ['feuilleton_id', 'numero', 'titre', 'fichier_audio', 'duree', 'unite_id'];

    public function feuilleton(): BelongsTo
    {
        return $this->belongsTo(Feuilleton::class);
    }

    public function unite(): BelongsTo
    {
        return $this->belongsTo(UniteDigitale::class, 'unite_id');
    }

    /**
     * La reprise de lecture vit dans le navigateur du parent, jamais ici :
     * le serveur n'a pas a savoir ou en est quelqu'un, et le programme ne
     * reproche jamais a un parent son absence.
     */
    public function dureeLisible(): string
    {
        return sprintf('%d min %02d', intdiv($this->duree, 60), $this->duree % 60);
    }
}
