@props([
    'titre' => '',
    'accueil' => '/superviseur/tableau-de-bord',
    // Le lien du menu de compte : le kit n'a pas d'écran de paramètres.
    'reglages' => '/superviseur/parametres',
    // L'expression Alpine appelée par « Fermer la session ».
    'deconnexion' => 'fermerSession()',
    // L'espace parent n'a pas de compte nommé : il porte ses propres outils.
    'compte' => true,
])

<header class="z-40 sans-impression" :class="{ 'dark': $store.app.semidark && $store.app.menu === 'horizontal' }">
    <div class="shadow-sm">
        <div class="relative bg-white flex w-full items-center px-5 py-2.5 dark:bg-[#0e1726]">

            <div class="horizontal-logo flex lg:hidden justify-between items-center ltr:mr-2 rtl:ml-2">
                <a href="{{ $accueil }}" class="main-logo flex items-center shrink-0">
                    <img class="w-8 ltr:-ml-1 rtl:-mr-1 inline rounded" src="/icones/mvoe-192.png" alt="Mvoé">
                    <span class="text-2xl ltr:ml-1.5 rtl:mr-1.5 font-semibold align-middle hidden md:inline dark:text-white-light">Mvoé</span>
                </a>
                <a href="javascript:;"
                   class="collapse-icon flex-none dark:text-[#d0d2d6] hover:text-primary dark:hover:text-primary flex lg:hidden ltr:ml-2 rtl:mr-2 p-2 rounded-full bg-white-light/40 dark:bg-dark/40 hover:bg-white-light/90 dark:hover:bg-dark/60"
                   @click="$store.app.toggleSidebar()">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none">
                        <path d="M20 7L4 7" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" />
                        <path opacity="0.5" d="M20 12L4 12" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" />
                        <path d="M20 17L4 17" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" />
                    </svg>
                </a>
            </div>

            <div class="hidden sm:block">
                <h1 class="text-lg font-semibold dark:text-white-light">{{ $titre }}</h1>
            </div>

            <div class="sm:flex-1 ltr:sm:ml-0 ltr:ml-auto sm:rtl:mr-0 rtl:mr-auto flex items-center space-x-1.5 lg:space-x-2 rtl:space-x-reverse justify-end dark:text-[#d0d2d6]">

                <div>
                    <a href="javascript:;" x-cloak x-show="$store.app.theme === 'light'"
                       class="flex items-center p-2 rounded-full bg-white-light/40 dark:bg-dark/40 hover:text-primary hover:bg-white-light/90 dark:hover:bg-dark/60"
                       @click="$store.app.toggleTheme('dark')" title="Passer en sombre">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none">
                            <circle cx="12" cy="12" r="5" stroke="currentColor" stroke-width="1.5" />
                            <path d="M12 2v2M12 20v2M4.93 4.93l1.41 1.41M17.66 17.66l1.41 1.41M2 12h2M20 12h2M6.34 17.66l-1.41 1.41M19.07 4.93l-1.41 1.41"
                                  stroke="currentColor" stroke-width="1.5" stroke-linecap="round" />
                        </svg>
                    </a>
                    <a href="javascript:;" x-cloak x-show="$store.app.theme === 'dark'"
                       class="flex items-center p-2 rounded-full bg-white-light/40 dark:bg-dark/40 hover:text-primary hover:bg-white-light/90 dark:hover:bg-dark/60"
                       @click="$store.app.toggleTheme('light')" title="Passer en clair">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none">
                            <path d="M12 3a9 9 0 1 0 9 9 7 7 0 0 1-9-9z" stroke="currentColor" stroke-width="1.5" stroke-linejoin="round" />
                        </svg>
                    </a>
                </div>

                {{-- Ce que chaque espace tient à garder sous les yeux : la file
                     des signalements pour un superviseur, l'état du réseau et le
                     compteur de synchronisation pour un facilitateur. --}}
                @if (isset($outils))
                    {{ $outils }}
                @else
                    <a href="/superviseur/signalements"
                       class="hidden sm:block p-2 rounded-full bg-white-light/40 dark:bg-dark/40 hover:text-primary hover:bg-white-light/90 dark:hover:bg-dark/60"
                       title="Signalements">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                             stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M12 4l9 16H3zM12 10v4M12 17h.01" />
                        </svg>
                    </a>
                @endif

                @if ($compte)
                <div class="dropdown shrink-0 flex" x-data="dropdown" @click.outside="open = false">
                    <a href="javascript:;" class="relative group block" @click="toggle">
                        <span class="w-9 h-9 rounded-full flex items-center justify-center bg-primary text-white font-semibold uppercase"
                              x-text="(nom ?? 'M').slice(0, 1)">M</span>
                    </a>
                    <ul x-cloak x-show="open" x-transition x-transition.duration.300ms
                        class="ltr:right-0 rtl:left-0 top-11 text-dark dark:text-white-dark !py-0 w-[240px] font-semibold dark:text-white-light/90 absolute z-50 bg-white dark:bg-[#1b2e4b] rounded-md shadow-md">
                        <li>
                            <div class="px-4 py-4">
                                <h4 class="text-base truncate" x-text="nom ?? 'Session'">Session</h4>
                                <span class="text-xs text-white-dark truncate block" x-text="portee ?? ''"></span>
                            </div>
                        </li>
                        @if ($reglages)
                            <li class="border-t border-white-light dark:border-white-light/10">
                                <a href="{{ $reglages }}" class="!py-3">Paramètres</a>
                            </li>
                        @endif
                        <li class="border-t border-white-light dark:border-white-light/10">
                            <a href="javascript:;" class="text-danger !py-3" @click="{{ $deconnexion }}">
                                Fermer la session
                            </a>
                        </li>
                    </ul>
                </div>
                @endif
            </div>
        </div>
    </div>
</header>
