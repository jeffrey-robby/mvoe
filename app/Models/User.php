<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

/**
 * Un compte administratif, à l'un des quatre niveaux de la chaîne :
 * MINPROFF, délégation régionale, délégation départementale, superviseur
 * d'arrondissement.
 *
 * Personne ne s'auto-inscrit : chaque compte est créé par le niveau au-dessus,
 * et `cree_par_id` garde cette chaîne vérifiable.
 *
 * Le facilitateur n'est pas un `user` : il a sa propre table et son propre mode
 * d'authentification. Le parent non plus.
 */
#[Fillable(['name', 'email', 'password', 'niveau', 'region_id', 'departement_id', 'arrondissement_id', 'cree_par_id'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasApiTokens, HasFactory, Notifiable;

    /**
     * Ce que ce compte a le droit de voir. Toute requête de données la traverse.
     */
    public function portee(): \App\Support\Portee
    {
        return \App\Support\Portee::pour($this);
    }

    public function region(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(Region::class);
    }

    public function departement(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(Departement::class);
    }

    public function arrondissement(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(Arrondissement::class);
    }

    /** Le compte qui a créé celui-ci. Null pour le seul compte racine. */
    public function creePar(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(User::class, 'cree_par_id');
    }

    /** Les comptes que ce compte a le droit de créer, et eux seuls. */
    public function niveauQuIlPeutCreer(): ?string
    {
        return match ($this->niveau) {
            'national' => 'region',
            'region' => 'departement',
            'departement' => 'arrondissement',
            default => null,
        };
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }
}
