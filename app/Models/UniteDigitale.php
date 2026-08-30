<?php

namespace App\Models;

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
    /**
     * La realisation demandee, ou celle de la langue de repli.
     *
     * Le repli n'est jamais silencieux : l'appelant compare `langue_id` a ce
     * qu'il avait demande et le dit au parent. Afficher du francais en laissant
     * croire que c'est du bulu serait pire que de ne rien afficher.
     */
    public function realisation(Langue $langue, Modalite $modalite): ?Realisation
    {
        return $this->realisations
            ->firstWhere(fn (Realisation $r) => $r->langue_id === $langue->id
                && $r->modalite === $modalite);
    }

    /**
     * Les langues dans lesquelles cette unite existe VRAIMENT.
     *
     * L'interface parent ne propose que celles-la. Proposer une langue qui
     * n'est pas chargee, c'est promettre un contenu qui n'existe pas.
     *
     * @return \Illuminate\Support\Collection<int, Langue>
     */
    public function languesDisponibles(): \Illuminate\Support\Collection
    {
        return $this->realisations
            ->map(fn (Realisation $r) => $r->langue)
            ->filter()
            ->unique('id')
            ->sortBy('ordre')
            ->values();
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
