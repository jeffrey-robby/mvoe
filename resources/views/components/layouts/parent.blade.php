@props([
    'titre' => 'Mvoé',
    // Le composant Alpine est déclaré sur le <body>, et non dans le contenu :
    // la barre du haut a besoin de la même portée que l'écran pour afficher le
    // sélecteur de langue et faire fonctionner le bouton de sortie. Ces deux
    // éléments sont exigés sur CHAQUE écran ; les laisser à la charge de chaque
    // vue reviendrait à parier qu'on n'en oubliera aucune.
    'composant',
    // L'écran d'entrée n'a ni sélecteur de langue ni bouton de sortie :
    // on n'y est pas encore entré, et la langue est justement ce qu'on y choisit.
    'barre' => true,
])

<!DOCTYPE html>
<html lang="fr" class="h-full">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <meta name="theme-color" content="#121212">
    <title>{{ $titre }} — Mvoé</title>

    <link rel="manifest" href="/manifest.webmanifest">
    <link rel="icon" href="/icones/mvoe-192.png" sizes="192x192">
    <link rel="apple-touch-icon" href="/icones/mvoe-192.png">

    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
{{--
    Interface allégée : tout est plus grand que dans le kit. L'écran sera tenu
    d'une main, souvent en plein soleil, parfois par quelqu'un qui lit mal.
--}}
<body x-data="{{ $composant }}" x-cloak class="h-full bg-blanc text-lg text-noir antialiased">

    @if ($barre)
        {{-- Sélecteur de langue permanent et bouton de sortie. --}}
        <header class="sur-noir sticky top-0 z-30 bg-noir text-blanc">
            <div class="mx-auto flex max-w-2xl items-center gap-2 px-4 py-2">
                <div class="flex gap-1" role="group" aria-label="Langue">
                    <template x-for="l in langues" x-bind:key="l.code">
                        <button type="button" x-on:click="changerLangue(l.code)"
                                class="min-h-tactile rounded-net px-3 text-base font-semibold [font-family:var(--font-titre)]"
                                x-bind:class="langue === l.code ? 'bg-jaune text-noir' : 'border border-blanc/40'"
                                x-bind:aria-pressed="langue === l.code"
                                x-text="l.libelle"></button>
                    </template>
                </div>

                <button type="button" x-on:click="sortir()"
                        class="ml-auto flex min-h-tactile items-center gap-2 rounded-net border border-blanc/40 px-3">
                    <svg class="size-5 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                         stroke-width="2.5" stroke-linecap="square" aria-hidden="true">
                        <path d="M14 4h5v16h-5M11 8l-4 4 4 4M7 12h9"/>
                    </svg>
                    <span class="intitule text-xs">Sortir</span>
                </button>
            </div>
        </header>
    @endif

    <main class="mx-auto max-w-2xl px-4 py-6">
        {{ $slot }}
    </main>

</body>
</html>
