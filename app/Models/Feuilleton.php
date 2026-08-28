<?php

namespace App\Models;

use App\Enums\Langue;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Feuilleton extends Model
{
    protected $fillable = ['titre', 'langue', 'resume'];

    protected function casts(): array
    {
        return ['langue' => Langue::class];
    }

    public function episodes(): HasMany
    {
        return $this->hasMany(Episode::class)->orderBy('numero');
    }
}
