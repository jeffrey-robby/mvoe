<?php

namespace App\Models;

use App\Enums\Langue;
use App\Enums\Modalite;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class UniteDigitale extends Model
{
    protected $table = 'unites_digitales';

    protected $fillable = ['module_id', 'sequence_id', 'message_cle'];

    public function module(): BelongsTo
    {
        return $this->belongsTo(Module::class);
    }

    public function sequence(): BelongsTo
    {
        return $this->belongsTo(Sequence::class);
    }

    public function realisations(): HasMany
    {
        return $this->hasMany(Realisation::class, 'unite_id');
    }

    public function questions(): HasMany
    {
        return $this->hasMany(Question::class, 'unite_id');
    }

    /**
     * Realisation dans la langue demandee, avec repli sur le francais.
     * Renvoie null plutot qu'un contenu invente : l'interface doit savoir dire
     * qu'une version manque, pas la remplacer par autre chose.
     */
    public function realisation(Langue $langue, Modalite $modalite): ?Realisation
    {
        return $this->realisations
            ->firstWhere(fn (Realisation $r) => $r->langue === $langue && $r->modalite === $modalite)
            ?? $this->realisations
                ->firstWhere(fn (Realisation $r) => $r->langue === Langue::Fr && $r->modalite === $modalite);
    }

    /**
     * Reference citee avec chaque reponse de l'assistant. C'est elle qui rend
     * la reponse verifiable ligne a ligne contre le guide officiel.
     */
    public function reference(): string
    {
        return sprintf(
            'Module %d — %s, sequence %d',
            $this->module->numero,
            $this->module->titre,
            $this->sequence->ordre,
        );
    }
}
