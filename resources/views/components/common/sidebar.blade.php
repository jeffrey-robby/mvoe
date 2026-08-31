@props([
    // Chaque entrée : [url, libellé, tracé SVG, expression Alpine de visibilité].
    'liens' => null,
    'accueil' => '/superviseur/tableau-de-bord',
    'section' => 'Délégation',
    'note' => "Aucun compte ne se crée ici sans qu'un niveau supérieur l'enregistre.",
])

@php
    $liens ??= [
        ['/superviseur/tableau-de-bord', 'Tableau de bord', 'M4 19h16M7 16V9M12 16V5M17 16v-4', null],
        ['/superviseur', 'Registre', 'M4 6h16M4 12h16M4 18h10', null],
        ['/superviseur/enregistrer', 'Enregistrer un facilitateur', 'M12 5v14M5 12h14', 'peutEnregistrer'],
        ['/superviseur/signalements', 'Signalements', 'M12 4l9 16H3zM12 10v4M12 17h.01', null],
        ['/superviseur/campagnes', 'Campagnes', 'M4 6h16M4 12h10M4 18h6M18 11l3 3-3 3', null],
        ['/superviseur/bibliotheque', 'Bibliothèque', 'M5 4h4v16H5zM11 4h4v16h-4zM17 6l3 14', 'estNational'],
        ['/superviseur/canaux', 'Canaux', 'M12 3v18M5 8a10 10 0 0 1 14 0M8 12a6 6 0 0 1 8 0', 'estNational'],
        ['/superviseur/rapport', 'Rapport', 'M8 4h9l3 3v13H8zM8 9h8M8 13h8M8 17h5', null],
        ['/superviseur/parametres', 'Paramètres', 'M4 8h16M4 16h16M9 5v6M15 13v6', null],
    ];

    $courant = fn (string $lien) => request()->path() === ltrim($lien, '/');
@endphp

<div :class="{ 'dark text-white-dark': $store.app.semidark }">
    <nav class="sidebar sans-impression fixed min-h-screen h-full top-0 bottom-0 w-[260px] shadow-[5px_0_25px_0_rgba(94,92,154,0.1)] z-50 transition-all duration-300">
        <div class="bg-white dark:bg-[#0e1726] h-full">
            <div class="flex justify-between items-center px-4 py-3">
                <a href="{{ $accueil }}" class="main-logo flex items-center shrink-0">
                    <img class="w-8 ml-[5px] flex-none rounded" src="/icones/mvoe-192.png" alt="Mvoé">
                    <span class="text-2xl ltr:ml-1.5 rtl:mr-1.5 font-semibold align-middle lg:inline dark:text-white-light">Mvoé</span>
                </a>
                <a href="javascript:;"
                   class="collapse-icon w-8 h-8 rounded-full flex items-center hover:bg-gray-500/10 dark:hover:bg-dark-light/10 dark:text-white-light transition duration-300 rtl:rotate-180"
                   @click="$store.app.toggleSidebar()">
                    <svg class="w-5 h-5 m-auto" viewBox="0 0 24 24" fill="none">
                        <path d="M13 19L7 12L13 5" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" />
                        <path opacity="0.5" d="M16.9998 19L10.9998 12L16.9998 5" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" />
                    </svg>
                </a>
            </div>

            <ul class="perfect-scrollbar relative font-semibold space-y-0.5 h-[calc(100vh-80px)] overflow-y-auto overflow-x-hidden p-4 py-0">

                {{-- La portée du compte, écrite en tête de la navigation.
                     Sur un poste partagé, savoir « au nom de quel territoire
                     je regarde » importe plus que savoir qui est connecté. --}}
                <h2 class="py-3 px-7 flex items-center uppercase font-extrabold bg-white-light/30 dark:bg-dark dark:bg-opacity-[0.08] -mx-4 mb-1">
                    <svg class="w-4 h-5 flex-none ltr:mr-2 rtl:ml-2" viewBox="0 0 24 24" fill="none"
                         stroke="currentColor" stroke-width="1.6" stroke-linecap="round">
                        <line x1="5" y1="12" x2="19" y2="12" />
                    </svg>
                    <span class="truncate" x-text="portee ?? '{{ $section }}'">{{ $section }}</span>
                </h2>

                @foreach ($liens as [$lien, $libelle, $trace, $condition])
                    <li class="nav-item" @if ($condition) x-cloak x-show="{{ $condition }}" @endif>
                        <a href="{{ $lien }}" class="group @if ($courant($lien)) active @endif">
                            <div class="flex items-center">
                                <svg class="group-hover:!text-primary shrink-0" width="20" height="20"
                                     viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6"
                                     stroke-linecap="round" stroke-linejoin="round">
                                    <path d="{{ $trace }}" />
                                </svg>
                                <span class="ltr:pl-3 rtl:pr-3 text-black dark:text-[#506690] dark:group-hover:text-white-dark">{{ $libelle }}</span>
                            </div>
                        </a>
                    </li>
                @endforeach

                @if ($note)
                    <li class="nav-item pt-4 pb-6">
                        <p class="px-2 text-xs font-normal leading-relaxed text-white-dark">{{ $note }}</p>
                    </li>
                @endif
            </ul>
        </div>
    </nav>
</div>
