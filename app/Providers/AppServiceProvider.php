<?php

namespace App\Providers;

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Vite;
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
        $this->interdireViteEnDirect();
    }

    /**
     * Un « npm run dev » lance par megarde sur le serveur depose
     * `public/hot`. Tant que ce fichier existe, Laravel sert TOUT le CSS et
     * le JS depuis 127.0.0.1:5174 — c'est-a-dire depuis la machine du
     * VISITEUR, ou rien n'ecoute. Les pages s'affichent alors sans une seule
     * regle de style, et rien dans les journaux ne le signale : le serveur
     * repond 200.
     *
     * Hors du poste de developpement, on pointe donc le drapeau vers un
     * chemin qui n'existera jamais. Le mode direct devient inatteignable, et
     * le manifeste de build reste le seul chemin possible.
     */
    private function interdireViteEnDirect(): void
    {
        if (! $this->app->environment('local')) {
            Vite::useHotFile(storage_path('framework/vite-en-direct-interdit'));
        }
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
        RateLimiter::for('connexion', function (Request $request) {
            // Ce qu'on protège, c'est UN code contre mille essais. La limite
            // doit donc porter sur l'identifiant visé, pas sur l'adresse IP :
            // une délégation entière sort par une seule IP, et un plafond
            // journalier par IP y bloquerait tout le bureau à cause d'un seul
            // agent qui se trompe de code.
            $vise = (string) (
                $request->input('telephone')
                ?? $request->input('email')
                ?? $request->input('code_parent')
                ?? 'anonyme'
            );

            return [
                Limit::perMinute(5)->by($vise),
                Limit::perDay(30)->by($vise),

                // L'IP garde un plafond, mais assez large pour un bureau
                // partagé ou une salle de démonstration.
                Limit::perMinute(20)->by($request->ip()),
                Limit::perDay(300)->by($request->ip()),
            ];
        });

        // L'assistant journalise chaque question : sans limite, on remplirait
        // la table `appariements` de bruit.
        RateLimiter::for('assistant', fn (Request $request) => Limit::perMinute(20)
            ->by($request->user()?->id ?? $request->ip()));

        RateLimiter::for('api', fn (Request $request) => Limit::perMinute(120)
            ->by($request->user()?->id ?? $request->ip()));
    }
}
