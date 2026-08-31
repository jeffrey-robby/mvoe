<x-layouts.kit titre="Pointage">

    <div x-data="pointage" x-cloak>

        <template x-if="! seance">
            <div class="panel">
                <h5 class="font-semibold text-lg dark:text-white-light">Aucune séance n'est ouverte</h5>
                <p class="text-white-dark mt-1">Le pointage se fait pendant la séance.</p>
                <a href="/kit" class="btn btn-outline-primary mt-5 inline-flex">Revenir à mon kit</a>
            </div>
        </template>

        <template x-if="seance">
            <div>
                <div class="flex flex-wrap items-end justify-between gap-3 mb-6">
                    <div>
                        <h2 class="text-2xl font-bold dark:text-white-light">Pointer les présences</h2>
                        <p class="text-white-dark mt-1">
                            Un appui fait défiler : présent, absent, rattrapé par son binôme.
                        </p>
                    </div>

                    <button type="button" class="btn btn-primary" x-on:click="retour()">
                        Revenir au déroulé
                    </button>
                </div>

                <div class="panel mb-6">
                    <div class="flex flex-wrap items-center justify-between gap-3 mb-5">
                        <h5 class="font-semibold text-lg dark:text-white-light">La cohorte</h5>

                        {{-- Le compteur dit où on en est, sans presser personne. --}}
                        <p class="chiffre text-white-dark">
                            <span class="text-black dark:text-white font-semibold" x-text="pointes"></span>
                            sur <span x-text="total"></span> pointés
                        </p>
                    </div>

                    <div class="w-full rounded-full h-2 bg-dark-light dark:bg-[#1b2e4b] shadow mb-6">
                        <div class="bg-gradient-to-r from-[#4361ee] to-[#805dca] h-full rounded-full"
                             x-bind:style="'width: ' + (total ? Math.round(100 * pointes / total) : 0) + '%'"></div>
                    </div>

                    {{-- Aucun parent n'est supposé présent : chacun démarre
                         « à pointer », et un parent oublié reste visiblement non
                         pointé plutôt que d'être remonté comme présent. --}}
                    <div class="grid grid-cols-4 gap-2 sm:grid-cols-5 xl:grid-cols-8">
                        <template x-for="p in parents" x-bind:key="p.code_parent">
                            <button type="button"
                                    x-on:click="pointer(p.code_parent)"
                                    x-bind:aria-label="(libelleDe(p.code_parent) || p.code_parent)
                                        + ' : ' + etiquettes[statuts[p.code_parent]]
                                        + '. Appuyer pour changer.'"
                                    class="flex flex-col items-center gap-1.5 rounded-md p-2 text-center hover:bg-jaune-sourd">

                                <span class="relative flex size-tactile items-center justify-center">
                                    <span class="pastille" x-bind:data-etat="statuts[p.code_parent]"
                                          aria-hidden="true"></span>

                                    {{-- Deux maillons : « reçu par son binôme »,
                                         dit par une forme et non par une couleur. --}}
                                    <svg class="absolute size-5" viewBox="0 0 24 24" fill="none"
                                         stroke="#121212" stroke-width="2.5" stroke-linecap="square"
                                         x-show="statuts[p.code_parent] === 'rattrape_binome'"
                                         aria-hidden="true">
                                        <path d="M9 12h6M8 8H6a4 4 0 0 0 0 8h2M16 8h2a4 4 0 0 1 0 8h-2" />
                                    </svg>
                                </span>

                                <span class="chiffre text-xs leading-none text-white-dark"
                                      x-text="p.code_parent"></span>

                                <template x-if="libelleDe(p.code_parent)">
                                    <span class="max-w-20 truncate text-sm leading-tight"
                                          x-text="libelleDe(p.code_parent)"></span>
                                </template>

                                <span class="intitule text-[0.6875rem]"
                                      x-bind:class="statuts[p.code_parent] === 'a_pointer' ? 'text-white-dark' : ''"
                                      x-text="etiquettes[statuts[p.code_parent]]"></span>
                            </button>
                        </template>
                    </div>
                </div>

                <div class="panel">
                    <div class="flex flex-wrap items-center justify-between gap-3 mb-5">
                        <h5 class="font-semibold text-lg dark:text-white-light">Mes repères</h5>
                        <button type="button" class="btn btn-outline-primary btn-sm"
                                x-on:click="modeLibelles = ! modeLibelles"
                                x-text="modeLibelles ? 'Terminer' : 'Modifier'">Modifier</button>
                    </div>

                    <p class="text-white-dark max-w-prose">
                        Écrivez ce qui vous aide à reconnaître chaque parent. Ces mots restent sur
                        ce téléphone : ils n'entrent dans aucun événement, ne sont jamais envoyés,
                        et sont effacés en fin de cycle.
                    </p>

                    <div x-cloak x-show="modeLibelles" class="mt-5 grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <template x-for="p in parents" x-bind:key="'l-' + p.code_parent">
                            <div class="flex items-center gap-3">
                                <label class="chiffre w-16 shrink-0 !mb-0"
                                       x-bind:for="'lib-' + p.code_parent"
                                       x-text="p.code_parent"></label>

                                <input type="text" class="form-input"
                                       x-bind:id="'lib-' + p.code_parent"
                                       x-bind:value="libelleDe(p.code_parent) ?? ''"
                                       x-on:change="definirLibelle(p.code_parent, $event.target.value)"
                                       placeholder="Odile, marché">
                            </div>
                        </template>
                    </div>
                </div>
            </div>
        </template>
    </div>
</x-layouts.kit>
