<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CurriculumVersion extends Model
{
    protected $fillable = ['label', 'active'];

    protected function casts(): array
    {
        return ['active' => 'boolean'];
    }

    public function modules(): HasMany
    {
        return $this->hasMany(Module::class);
    }

    public function cohortes(): HasMany
    {
        return $this->hasMany(Cohorte::class);
    }
}
