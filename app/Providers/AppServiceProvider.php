<?php

namespace App\Providers;

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        $this->limiteursDeDebit();
    }

    /**
     * Les codes d'accès du système sont courts par nécessité : 4 chiffres pour
     * un parent, 6 pour un facilitateur, remis en main propre à des gens qui ne
     * retiendront pas un mot de passe. Un code court ne tient que si l'on ne
     * peut pas l'essayer mille fois : la limite de débit fait ici le travail
     * que la longueur du code ne peut pas faire.
     */
    private function limiteursDeDebit(): void
    {
        RateLimiter::for('connexion', fn (Request $request) => [
            Limit::perMinute(5)->by($request->ip()),
            Limit::perDay(30)->by($request->ip()),
        ]);

        // L'assistant journalise chaque question : sans limite, on remplirait
        // la table `appariements` de bruit.
        RateLimiter::for('assistant', fn (Request $request) => Limit::perMinute(20)
            ->by($request->user()?->id ?? $request->ip()));

        RateLimiter::for('api', fn (Request $request) => Limit::perMinute(120)
            ->by($request->user()?->id ?? $request->ip()));
    }
}
