@props([
    'titre' => 'Mvoé',
    // Le compteur lit la file locale tout seul. `compteurDemo` ne sert qu'à
    // la page du système de design, pour montrer l'état chargé.
    'compteurDemo' => null,
    'retour' => null,
])

<!DOCTYPE html>
<html lang="fr" class="h-full">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <meta name="theme-color" content="#121212">
    <meta name="description" content="Le kit du facilitateur Mvoé. Fonctionne entièrement hors ligne.">

    {{-- Ce qui rend l'application installable sur un téléphone Android
         depuis le navigateur, sans passer par un magasin d'applications. --}}
    <link rel="manifest" href="/manifest.webmanifest">
    <link rel="icon" href="/icones/mvoe-192.png" sizes="192x192">
    <link rel="apple-touch-icon" href="/icones/mvoe-192.png">
    <title>{{ $titre }} — Mvoé</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
{{-- `terrain` : corps a 17 px, fond blanc franc, cibles a 48 px.
     La grille du template ne s'applique pas ici. --}}
<body class="terrain h-full antialiased">

    {{-- Barre d'état permanente. Elle ne défile pas : l'état de la
         synchronisation et du réseau doit rester lisible pendant toute la
         séance, sans avoir à remonter en haut de page. --}}
    <header class="sur-noir sticky top-0 z-30 bg-noir text-blanc">
        <div class="mx-auto flex max-w-3xl items-center gap-3 px-4 py-3">
            @if ($retour)
                <a href="{{ $retour }}"
                   class="-ml-2 flex size-tactile shrink-0 items-center justify-center rounded-net"
                   aria-label="Revenir">
                    <svg class="size-6" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                         stroke-width="2.5" stroke-linecap="square" aria-hidden="true">
                        <path d="M15 5 8 12l7 7"/>
                    </svg>
                </a>
            @endif

            <span class="intitule truncate">{{ $titre }}</span>

            <div class="ml-auto flex shrink-0 items-center gap-2">
                <x-mvoe.etat-reseau/>
                <x-mvoe.compteur-sync :demo="$compteurDemo"/>
            </div>
        </div>
    </header>

    <main class="mx-auto max-w-3xl px-4 py-6">
        {{ $slot }}
    </main>

</body>
</html>
