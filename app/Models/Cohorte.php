<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Cohorte extends Model
{
    protected $fillable = [
        'libelle', 'arrondissement', 'ratio_max', 'curriculum_version_id', 'facilitateur_id', 'date_debut',
    ];

    protected function casts(): array
    {
        return ['date_debut' => 'date'];
    }

    public function curriculumVersion(): BelongsTo
    {
        return $this->belongsTo(CurriculumVersion::class);
    }

    public function facilitateur(): BelongsTo
    {
        return $this->belongsTo(Facilitateur::class);
    }

    public function parents(): HasMany
    {
        return $this->hasMany(ParentProgramme::class);
    }

    public function seances(): HasMany
    {
        return $this->hasMany(Seance::class);
    }

    /**
     * Le plafond vient toujours de la donnee de la cohorte. Aucun 20 n'est ecrit
     * dans le code : c'est ce parametre que le superviseur passe a 10 devant le jury.
     */
    public function placesRestantes(): int
    {
        return max(0, $this->ratio_max - $this->parents()->count());
    }

    public function estComplete(): bool
    {
        return $this->placesRestantes() === 0;
    }
}
