<?php

namespace App\Models;

use App\Enums\StatutPresence;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Presence extends Model
{
    protected $fillable = ['uuid', 'seance_id', 'parent_id', 'statut'];

    protected function casts(): array
    {
        return ['statut' => StatutPresence::class];
    }

    public function seance(): BelongsTo
    {
        return $this->belongsTo(Seance::class);
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(ParentProgramme::class, 'parent_id');
    }
}
