<?php

namespace App\Models;

use App\Enums\Canal;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Une diffusion par un canal.
 *
 * Pour la radio, `atteste_par` porte le nom de qui a signé que l'émission est
 * bien passée. Sans attestation, une diffusion déclarée n'est qu'une intention.
 */
class Diffusion extends Model
{
    use \App\Models\Concerns\LimiteParPortee;

    protected $fillable = [
        'canal', 'unite_id', 'langue_id', 'campagne_id', 'arrondissement_id',
        'cible', 'date', 'volume', 'aboutis', 'statut', 'atteste_par',
    ];

    protected function casts(): array
    {
        return ['canal' => Canal::class, 'date' => 'datetime'];
    }

    public function langue(): BelongsTo
    {
        return $this->belongsTo(Langue::class);
    }

    public function unite(): BelongsTo
    {
        return $this->belongsTo(UniteDigitale::class, 'unite_id');
    }

    public function campagne(): BelongsTo
    {
        return $this->belongsTo(Campagne::class);
    }

    public function attestee(): bool
    {
        return filled($this->atteste_par);
    }
}
