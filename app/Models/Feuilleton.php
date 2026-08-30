<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Feuilleton extends Model
{
    protected $fillable = ['titre', 'langue_id', 'resume'];

    protected function casts(): array
    {
        return [];
    }

    public function langue(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(Langue::class);
    }

    public function episodes(): HasMany
    {
        return $this->hasMany(Episode::class)->orderBy('numero');
    }
}
