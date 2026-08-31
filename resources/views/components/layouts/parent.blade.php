@props([
    'titre' => 'Mvoé',
    // Le composant Alpine est déclaré sur le conteneur, et non dans le contenu :
    // la barre du haut a besoin de la même portée que l'écran pour afficher le
    // sélecteur de langue et faire fonctionner le bouton de sortie.
    'composant',
    // L'écran d'entrée n'a ni navigation ni sélecteur de langue : on n'y est pas
    // encore entré, et la langue est justement ce qu'on y choisit.
    'barre' => true,
    // L'écran d'entrée occupe toute la page : il porte son propre fond.
    'plein' => false,
])

@php
    $liens = [
        ['/parent/accueil', 'Mes contenus', 'M4 6h16M4 12h16M4 18h10', null],
        ['/parent/feuilleton', 'Le feuilleton', 'M8 5v14l11-7z', null],
        ['/parent/questions', 'Les questions', 'M12 18h.01M9.1 9a3 3 0 1 1 4.2 3.2c-.8.4-1.3 1.1-1.3 2', null],
        ['/parent/facilitateur', 'Trouver un facilitateur', 'M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2M12 3a4 4 0 1 0 0 8 4 4 0 0 0 0-8z', null],
    ];
@endphp

@if ($plein)
    {{-- L'entrée : pas de coquille, l'écran porte son propre fond. --}}
    <!DOCTYPE html>
    <html lang="fr" dir="ltr">

    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
        <meta name="theme-color" content="#4361ee">
        <title>{{ $titre }} — Mvoé</title>
        <link rel="manifest" href="/manifest.webmanifest">
        <link rel="icon" href="/icones/mvoe-192.png" sizes="192x192">
        <link rel="apple-touch-icon" href="/icones/mvoe-192.png">
        @vite(['resources/css/kit.css', 'resources/js/app.js'])
    </head>

    <body x-data="{{ $composant }}" x-cloak class="terrain antialiased">
        {{ $slot }}
    </body>

    </html>
@else
    <x-layouts.coquille :titre="$titre"
                        :entrees="['resources/css/kit.css', 'resources/js/app.js']"
                        :donnees="$composant"
                        :alpine-template="false"
                        :liens="$liens"
                        accueil="/parent/accueil"
                        section="Espace parent"
                        note="Vos codes vous ont été remis par votre facilitateur. Personne ne s'inscrit seul."
                        :reglages="null"
                        :compte="false"
                        :manifeste="true">

        @if ($barre)
            <x-slot:outils>
                {{-- Le sélecteur ne propose QUE les langues réellement
                     disponibles pour le contenu ouvert : promettre une langue
                     qui n'est pas chargée, c'est promettre un contenu qui
                     n'existe pas. Le nom affiché est l'endonyme — personne ne
                     cherche « Bulu » écrit en français quand il ne lit pas le
                     français. --}}
                <div class="flex gap-1" role="group" aria-label="Langue">
                    <template x-for="l in languesOffertes()" x-bind:key="l.code">
                        <button type="button" class="btn btn-sm" x-on:click="changerLangue(l.code)"
                                x-bind:class="langue === l.code ? 'btn-primary' : 'btn-outline-primary'"
                                x-bind:aria-pressed="langue === l.code"
                                x-text="l.nom"></button>
                    </template>
                </div>

                <button type="button" x-on:click="sortir()"
                        class="flex items-center gap-2 p-2 rounded-full bg-white-light/40 dark:bg-dark/40 hover:text-primary hover:bg-white-light/90"
                        title="Sortir">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                         stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M14 4h5v16h-5M11 8l-4 4 4 4M7 12h9" />
                    </svg>
                </button>
            </x-slot:outils>
        @endif

        {{ $slot }}
    </x-layouts.coquille>
@endif
