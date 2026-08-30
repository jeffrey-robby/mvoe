@props([
    'titre' => 'Délégation',
    // La page de connexion n'a pas de navigation : on n'y est pas encore.
    'navigation' => true,
])

<!DOCTYPE html>
<html lang="fr" class="h-full">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="theme-color" content="#4361ee">
    <title>{{ $titre }} — Mvoé</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
{{--
    L'administration. Fond gris très clair, panneaux blancs, densité de bureau :
    c'est un outil qu'on utilise assis, à la souris, sur un grand écran. Le
    système « terrain » ne s'applique pas ici, et réciproquement.
--}}
<body class="h-full antialiased">

@if ($navigation)
    @php
        $liens = [
            ['/superviseur/tableau-de-bord', 'Tableau de bord',
                'M4 19h16M7 16V9M12 16V5M17 16v-4', null],
            ['/superviseur', 'Registre', 'M4 6h16M4 12h16M4 18h10', null],
            ['/superviseur/enregistrer', 'Enregistrer', 'M12 5v14M5 12h14', 'peutEnregistrer'],
            ['/superviseur/signalements', 'Signalements',
                'M12 4l9 16H3zM12 10v4M12 17h.01', null],
            ['/superviseur/campagnes', 'Campagnes',
                'M4 6h16M4 12h10M4 18h6M18 11l3 3-3 3', null],
            ['/superviseur/bibliotheque', 'Bibliothèque',
                'M5 4h4v16H5zM11 4h4v16h-4zM17 6l3 14', 'estNational'],
            ['/superviseur/canaux', 'Canaux',
                'M12 3v18M5 8a10 10 0 0 1 14 0M8 12a6 6 0 0 1 8 0', 'estNational'],
            ['/superviseur/rapport', 'Rapport', 'M8 4h9l3 3v13H8zM8 9h8M8 13h8M8 17h5', null],
            ['/superviseur/parametres', 'Paramètres', 'M4 8h16M4 16h16M9 5v6M15 13v6', null],
        ];
        $courant = fn (string $lien) => request()->path() === ltrim($lien, '/');
    @endphp

    <div class="flex min-h-full" x-data="enteteDelegation">

        {{-- La barre latérale. Masquée sous 1024 px : la navigation passe alors
             dans la barre du haut, parce qu'un tiroir sur un petit écran cache
             plus qu'il ne montre. --}}
        <aside class="sans-impression hidden w-[260px] shrink-0 border-r border-white-light bg-white lg:block">
            <div class="flex h-16 items-center gap-3 px-5">
                <span class="flex size-8 items-center justify-center rounded bg-primary text-white
                             [font-family:var(--font-titre)] font-bold">M</span>
                <span class="[font-family:var(--font-titre)] text-lg font-bold">Mvoé</span>
            </div>

            <nav class="px-3 pb-6">
                <p class="intitule px-2.5 pb-2 pt-4 text-white-dark"
                   x-text="portee ?? 'Délégation'">Délégation</p>

                @foreach ($liens as [$lien, $libelle, $trace, $condition])
                    <a href="{{ $lien }}"
                       @if ($condition) x-cloak x-show="{{ $condition }}" @endif
                       @class([
                           'mb-1 flex items-center gap-3 rounded-md p-2.5 text-base',
                           'bg-black/[0.08] font-semibold text-black' => $courant($lien),
                           'text-dark hover:bg-black/[0.06] hover:text-black' => ! $courant($lien),
                       ])>
                        <svg class="size-5 shrink-0 opacity-60" viewBox="0 0 24 24" fill="none"
                             stroke="currentColor" stroke-width="2" stroke-linecap="round"
                             aria-hidden="true">
                            <path d="{{ $trace }}"/>
                        </svg>
                        {{ $libelle }}
                    </a>
                @endforeach
            </nav>
        </aside>

        <div class="flex min-w-0 flex-1 flex-col">

            {{-- La barre du haut. Elle disparaît à l'impression : un rapport
                 doit sortir comme un document, pas comme une capture d'écran. --}}
            <header class="sans-impression sticky top-0 z-20 flex h-16 items-center gap-4
                           border-b border-white-light bg-white px-4 lg:px-6">
                <span class="[font-family:var(--font-titre)] text-lg font-bold lg:hidden">Mvoé</span>

                {{-- Navigation de repli sous 1024 px. --}}
                <nav class="flex flex-wrap gap-x-4 gap-y-1 lg:hidden">
                    @foreach ($liens as [$lien, $libelle, $trace, $condition])
                        <a href="{{ $lien }}"
                           @if ($condition) x-cloak x-show="{{ $condition }}" @endif
                           @class([
                               'text-base',
                               'font-semibold underline underline-offset-4' => $courant($lien),
                               'text-dark' => ! $courant($lien),
                           ])>{{ $libelle }}</a>
                    @endforeach
                </nav>

                <p class="ml-auto hidden text-base text-white-dark lg:block">
                    <span x-cloak x-show="nom" x-text="nom"></span>
                    <span x-cloak x-show="nom"> · </span>{{ $titre }}
                </p>
            </header>

            <main class="mx-auto w-full max-w-5xl flex-1 px-4 py-6 lg:px-6">
                {{ $slot }}
            </main>
        </div>
    </div>
@else
    <main class="mx-auto w-full max-w-5xl px-4 py-6">
        {{ $slot }}
    </main>
@endif

</body>
</html>
