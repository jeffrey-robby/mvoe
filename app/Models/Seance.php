<?php

namespace App\Models;

use App\Enums\StatutPresence;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Collection;

class Seance extends Model
{
    use \App\Models\Concerns\LimiteParPortee;

    protected $fillable = ['uuid', 'cohorte_id', 'module_id', 'date', 'facilitateur_id', 'recue_a'];

    /** Une séance n'a pas d'arrondissement : elle tient celui de sa cohorte. */
    protected static function relaisDePortee(): ?array
    {
        return [Cohorte::class, 'cohorte_id'];
    }

    protected function casts(): array
    {
        return [
            'date' => 'date',
            'recue_a' => 'datetime',
        ];
    }

    public function cohorte(): BelongsTo
    {
        return $this->belongsTo(Cohorte::class);
    }

    public function module(): BelongsTo
    {
        return $this->belongsTo(Module::class);
    }

    public function facilitateur(): BelongsTo
    {
        return $this->belongsTo(Facilitateur::class);
    }

    public function presences(): HasMany
    {
        return $this->hasMany(Presence::class);
    }

    public function sequencesOuvertes(): HasMany
    {
        return $this->hasMany(SequenceOuverte::class);
    }

    public function fichesFidelite(): HasMany
    {
        return $this->hasMany(FicheFidelite::class);
    }

    /**
     * Delai entre la tenue de la seance et sa reception par le serveur, en jours.
     * Null tant que la seance n'est pas remontee.
     */
    public function delaiRemonteeJours(): ?int
    {
        return $this->recue_a === null
            ? null
            : (int) $this->date->diffInDays($this->recue_a);
    }

    /**
     * LE COEUR DU PROJET : confronter le DECLARE a l'OBSERVE.
     *
     * Le declare vient de la fiche de fidelite, remplie de memoire apres la seance.
     * L'observe vient de `sequences_ouvertes`, ecrit passivement pendant la seance,
     * sans que le facilitateur ait rien a saisir. Aucun formulaire papier ne peut
     * produire cette confrontation, parce que le papier n'a qu'une seule source.
     *
     * Deux ecarts possibles, et le second n'est pas une erreur de saisie :
     *  - `declaree_non_observee` : declaree realisee, aucune trace d'ouverture ;
     *  - `observee_non_declaree` : ouverte pendant la seance, declaree non realisee.
     *
     * @return Collection<int, array{sequence: Sequence, declaree: ?bool, observee: bool, ecart: ?string}>
     */
    public function ecarts(): Collection
    {
        // On passe par les relations, pas par des requêtes : un tableau de bord
        // national confronte des centaines de séances, et trois requêtes par
        // séance deviennent là des milliers. Chargées d'avance, elles sont
        // gratuites ; sinon Eloquent les charge à la demande, comme avant.
        $observees = $this->sequencesOuvertes->pluck('sequence_id')->unique();
        $declarees = $this->fichesFidelite
            ->whereNotNull('sequence_id')
            ->pluck('realisee_bool', 'sequence_id');

        return $this->module->sequences->sortBy('ordre')->values()
            ->map(function (Sequence $sequence) use ($observees, $declarees) {
                $observee = $observees->contains($sequence->id);
                $declaree = $declarees->has($sequence->id) ? (bool) $declarees[$sequence->id] : null;

                $ecart = match (true) {
                    $declaree === true && ! $observee => 'declaree_non_observee',
                    $declaree === false && $observee => 'observee_non_declaree',
                    default => null,
                };

                return compact('sequence', 'declaree', 'observee', 'ecart');
            });
    }

    public function nombreEcarts(): int
    {
        return $this->ecarts()->whereNotNull('ecart')->count();
    }

    /**
     * Un parent rattrape par son binome a recu la seance, autrement.
     * Il compte donc dans la dose, au meme titre qu'un present.
     */
    public function nombreTouches(): int
    {
        return $this->presences
            ->whereIn('statut', [StatutPresence::Present, StatutPresence::RattrapeBinome])
            ->count();
    }
}
