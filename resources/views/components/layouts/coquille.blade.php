@props([
    'titre' => 'Mvoé',

    // Les entrées Vite. C'est la seule chose qui distingue vraiment les trois
    // espaces : l'administration parle au réseau, le kit et l'espace parent
    // embarquent leurs polices et leur magasin hors ligne.
    'entrees' => ['resources/css/app.css', 'resources/js/admin.js'],

    // Le composant Alpine posé sur le conteneur. La barre latérale et l'en-tête
    // y lisent le nom et la portée du compte.
    'donnees' => 'enteteDelegation',

    // L'administration s'appuie sur l'Alpine du template ; le kit et l'espace
    // parent gardent le leur, pour ouvrir leur magasin local avant le montage.
    'alpineTemplate' => true,

    // Navigation.
    'liens' => null,
    'accueil' => '/superviseur/tableau-de-bord',
    'section' => 'Délégation',
    'note' => "Aucun compte ne se crée ici sans qu'un niveau supérieur l'enregistre.",

    // En-tête.
    'reglages' => '/superviseur/parametres',
    'deconnexion' => 'fermerSession()',
    'compte' => true,

    // Ce qui rend l'application installable sur un téléphone.
    'manifeste' => false,

    // Le systeme « terrain » : corps a 17 px et contour de focus epais, pour un
    // ecran tenu d'une main en plein soleil. L'administration s'en passe : elle
    // se lit assis, a la souris, sur un grand ecran.
    'terrain' => false,
])

<!DOCTYPE html>
<html lang="fr" dir="ltr">

<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <title>{{ $titre }} — Mvoé</title>
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <meta name="theme-color" content="#4361ee">
    <link rel="icon" type="image/png" href="/icones/mvoe-192.png">

    @if ($manifeste)
        <link rel="manifest" href="/manifest.webmanifest">
        <link rel="apple-touch-icon" href="/icones/mvoe-192.png">
    @else
        <link rel="preconnect" href="https://fonts.googleapis.com">
        <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
        <link href="https://fonts.googleapis.com/css2?family=Archivo:wght@600;700;800&family=IBM+Plex+Sans:wght@400;500;600;700&family=IBM+Plex+Mono:wght@400;500;600&display=swap" rel="stylesheet">
    @endif

    <script src="/assets/js/perfect-scrollbar.min.js"></script>
    <script defer src="/assets/js/popper.min.js"></script>
    <script defer src="/assets/js/tippy-bundle.umd.min.js"></script>

    @vite($entrees)
</head>

{{-- La coquille est IDENTIQUE dans les trois espaces : meme corps, meme barre
     laterale, meme en-tete. Le systeme terrain ne s'applique qu'au CONTENU —
     poser ses 17 px sur le corps les propagerait a la navigation, et le kit
     n'aurait plus la meme chrome que la delegation. --}}
<body x-data="main"
      class="antialiased relative font-nunito text-sm font-normal overflow-x-hidden"
      :class="[$store.app.sidebar ? 'toggle-sidebar' : '', $store.app.theme === 'dark' || $store.app.isDarkMode ? 'dark' : '', $store.app.menu, $store.app.layout, $store.app.rtlClass]">

    <div x-cloak class="fixed inset-0 bg-[black]/60 z-50 lg:hidden" :class="{ 'hidden': !$store.app.sidebar }"
         @click="$store.app.toggleSidebar()"></div>

    {{-- La portée est posée une fois, ici : la barre latérale et l'en-tête la
         lisent tous les deux, et ne peuvent donc pas se contredire. --}}
    <div class="main-container text-black dark:text-white-dark min-h-screen"
         :class="[$store.app.navbar]" x-data="{{ $donnees }}">

        <x-common.sidebar :liens="$liens" :accueil="$accueil" :section="$section" :note="$note" />

        <div class="main-content flex flex-col min-h-screen">
            <x-common.header :titre="$titre" :accueil="$accueil" :reglages="$reglages"
                             :deconnexion="$deconnexion" :compte="$compte">
                @isset($outils)
                    <x-slot:outils>{{ $outils }}</x-slot:outils>
                @endisset
            </x-common.header>

            <div @class(['p-6 animate__animated', 'terrain' => $terrain])
                 :class="[$store.app.animation]">
                {{ $slot }}
            </div>

            <x-common.footer />
        </div>
    </div>

    @if ($alpineTemplate)
        <script src="/assets/js/alpine-collaspe.min.js"></script>
        <script src="/assets/js/alpine-persist.min.js"></script>
        <script defer src="/assets/js/alpine-ui.min.js"></script>
        <script defer src="/assets/js/alpine-focus.min.js"></script>
        <script defer src="/assets/js/alpine.min.js"></script>
        <script src="/assets/js/custom.js"></script>
    @endif
</body>

</html>
