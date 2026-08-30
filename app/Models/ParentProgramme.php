<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Laravel\Sanctum\HasApiTokens;

/**
 * Table `parents`. Le modele ne peut pas s'appeler Parent : c'est un mot
 * reserve de PHP, on ne peut pas en declarer une classe.
 *
 * AUCUNE donnee nominative ici, par exigence de la loi n° 2024/017 : ni nom,
 * ni prenom, ni profession, ni religion, ni ethnie, ni position GPS.
 * Le facilitateur reconnait ses parents grace a un libelle local qu'il saisit
 * lui-meme (« Odile, marche ») ; ce libelle vit dans IndexedDB sur son seul
 * appareil, il est exclu de la file de synchronisation et purge en fin de cycle.
 * Il n'existe donc nulle part cote serveur, et surtout pas dans ce modele.
 */
class ParentProgramme extends Model
{
    use HasApiTokens;
    use \App\Models\Concerns\LimiteParPortee;

    protected $table = 'parents';

    /** Un parent n'a pas d'arrondissement : il tient celui de sa cohorte. */
    protected static function relaisDePortee(): ?array
    {
        return [Cohorte::class, 'cohorte_id'];
    }

    protected $fillable = [
        'cohorte_id', 'code_parent', 'code_acces', 'langue_id',
        'statut_matrimonial', 'revenu_regularite', 'telephone_partage',
    ];

    protected $hidden = ['code_acces'];

    protected function casts(): array
    {
        return [
            'telephone_partage' => 'boolean',
            // Le code a 4 chiffres est hache a l'ecriture : une copie de la base
            // ne suffit pas a entrer dans l'espace parent.
            'code_acces' => 'hashed',
        ];
    }

    public function cohorte(): BelongsTo
    {
        return $this->belongsTo(Cohorte::class);
    }

    /**
     * La langue du parent, pas celle de sa region.
     *
     * Un locuteur bulu installe dans l'Ocean recoit du bulu. C'est une
     * propriete de la personne, jamais de l'endroit ou elle habite.
     */
    public function langue(): BelongsTo
    {
        return $this->belongsTo(Langue::class);
    }

    public function enfants(): HasMany
    {
        return $this->hasMany(Enfant::class, 'parent_id');
    }

    public function presences(): HasMany
    {
        return $this->hasMany(Presence::class, 'parent_id');
    }

    /**
     * Le binome est enregistre dans un sens ou dans l'autre ; on cherche des deux cotes.
     */
    public function binome(): ?ParentProgramme
    {
        $lien = Binome::where('parent_a_id', $this->id)
            ->orWhere('parent_b_id', $this->id)
            ->first();

        if ($lien === null) {
            return null;
        }

        return $lien->parent_a_id === $this->id ? $lien->parentB : $lien->parentA;
    }
}
