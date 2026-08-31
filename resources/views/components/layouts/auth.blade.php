@props([
    'titre' => 'Connexion',
    // La connexion du kit partage cette coquille mais son propre point d'entrée.
    'entree' => 'resources/js/admin.js',
])

<!DOCTYPE html>
<html lang="fr" dir="ltr">

<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <title>{{ $titre }} — Mvoé</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="theme-color" content="#4361ee">
    <link rel="icon" type="image/png" href="/icones/mvoe-192.png">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Archivo:wght@600;700;800&family=IBM+Plex+Sans:wght@400;500;600;700&family=IBM+Plex+Mono:wght@400;500;600&display=swap" rel="stylesheet">

    @vite(['resources/css/app.css', $entree])
</head>

<body x-data="main"
      class="antialiased relative font-nunito text-sm font-normal overflow-x-hidden"
      :class="[$store.app.sidebar ? 'toggle-sidebar' : '', $store.app.theme === 'dark' || $store.app.isDarkMode ? 'dark' : '', $store.app.menu, $store.app.layout, $store.app.rtlClass]">

    <div class="main-container text-black dark:text-white-dark min-h-screen">
        {{ $slot }}
    </div>

    <script src="/assets/js/alpine-collaspe.min.js"></script>
    <script src="/assets/js/alpine-persist.min.js"></script>
    <script defer src="/assets/js/alpine-ui.min.js"></script>
    <script defer src="/assets/js/alpine-focus.min.js"></script>
    <script defer src="/assets/js/alpine.min.js"></script>
    <script src="/assets/js/custom.js"></script>
</body>

</html>
