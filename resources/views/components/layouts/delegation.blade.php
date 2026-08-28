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
    <meta name="theme-color" content="#121212">
    <title>{{ $titre }} — Mvoé</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="h-full bg-blanc text-noir antialiased">

    @if ($navigation)
        {{-- La barre disparaît à l'impression : le rapport doit sortir comme
             un document, pas comme une capture d'écran. --}}
        <header class="sur-noir bg-noir text-blanc sans-impression">
            <div class="mx-auto flex max-w-4xl flex-wrap items-center gap-x-6 gap-y-2 px-4 py-3">
                <span class="intitule">Délégation</span>

                <nav class="flex flex-wrap gap-x-5 gap-y-1">
                    @foreach ([
                        ['/superviseur', 'Registre'],
                        ['/superviseur/rapport', 'Rapport'],
                        ['/superviseur/parametres', 'Paramètres'],
                    ] as [$lien, $libelle])
                        <a href="{{ $lien }}"
                           @class([
                               'flex min-h-tactile items-center text-base',
                               'font-semibold underline underline-offset-4' => request()->path() === ltrim($lien, '/'),
                           ])>{{ $libelle }}</a>
                    @endforeach
                </nav>
            </div>
        </header>
    @endif

    <main class="mx-auto max-w-4xl px-4 py-6">
        {{ $slot }}
    </main>

</body>
</html>
