<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Deux parents liés, pour que l'un rattrape l'autre.
 *
 * Le soutien entre pairs passe par ce lien physique : c'est lui qui autorise le
 * statut « rattrapé par binôme » au pointage. Il n'existe aucun fil de
 * discussion entre parents, et il n'en existera pas.
 */
class Binome extends Model
{
    protected $fillable = ['gsp_id', 'parent_a_id', 'parent_b_id'];

    /** Le groupe de soutien au sein duquel le binôme a été constitué. */
    public function groupe(): BelongsTo
    {
        return $this->belongsTo(GroupeSoutien::class, 'gsp_id');
    }

    public function parentA(): BelongsTo
    {
        return $this->belongsTo(ParentProgramme::class, 'parent_a_id');
    }

    public function parentB(): BelongsTo
    {
        return $this->belongsTo(ParentProgramme::class, 'parent_b_id');
    }
}
