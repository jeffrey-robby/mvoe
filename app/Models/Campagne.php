<?php

namespace App\Models;

use App\Enums\StatutCampagne;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Une campagne : le ministère pousse des modules, dans des langues, sur des
 * territoires, entre deux dates.
 */
class Campagne extends Model
{
    protected $fillable = [
        'titre', 'objet', 'module_ids', 'langue_ids',
        'date_debut', 'date_fin', 'statut', 'creee_par_id',
    ];

    protected function casts(): array
    {
        return [
            'module_ids' => 'array',
            'langue_ids' => 'array',
            'date_debut' => 'date',
            'date_fin' => 'date',
            'statut' => StatutCampagne::class,
        ];
    }

    public function affectations(): HasMany
    {
        return $this->hasMany(CampagneAffectation::class);
    }

    public function auteur(): BelongsTo
    {
        return $this->belongsTo(User::class, 'creee_par_id');
    }

    public function estDeclenchee(): bool
    {
        return $this->statut === StatutCampagne::Declenchee;
    }

    /**
     * L'avancement de la cascade, niveau par niveau.
     *
     * Ce n'est pas un pourcentage d'exécution du programme : c'est le nombre
     * d'échelons qui ont pris connaissance. Confondre les deux ferait croire
     * qu'une campagne « à 80 % » a touché 80 % des parents.
     *
     * @return array<int, array<string, mixed>>
     */
    public function avancement(): array
    {
        $par = $this->affectations->groupBy('niveau');

        return collect(['region', 'departement', 'arrondissement', 'facilitateur'])
            ->map(function (string $niveau) use ($par) {
                $lignes = $par->get($niveau, collect());

                return [
                    'niveau' => $niveau,
                    'affectees' => $lignes->count(),
                    'recues' => $lignes->whereNotNull('date_reception')->count(),
                ];
            })
            ->filter(fn (array $l) => $l['affectees'] > 0)
            ->values()
            ->all();
    }
}
