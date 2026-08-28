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
 * Le superviseur de la délégation d'arrondissement. C'est le seul compte du
 * système avec un mot de passe : le facilitateur ouvre un kit sur un téléphone,
 * le parent entre un code à 4 chiffres reçu en main propre.
 */
#[Fillable(['name', 'email', 'arrondissement', 'password'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasApiTokens, HasFactory, Notifiable;

    /**
     * Périmètre de lecture. `null` = délégation départementale, qui voit les
     * huit arrondissements de la Mvila. Sinon, la délégation ne voit que son
     * propre arrondissement : l'écart d'un facilitateur se lit avec lui, et
     * son supérieur direct est le seul à en avoir l'usage.
     */
    public function voitToutLeDepartement(): bool
    {
        return $this->arrondissement === null;
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
