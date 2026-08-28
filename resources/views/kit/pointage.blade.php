{{--
    Le pointage.

    Vingt parents en grille, trois états d'un seul geste. Aucun parent n'est
    supposé présent : chacun démarre « à pointer », et un parent oublié reste
    visiblement non pointé plutôt que d'être remonté comme présent.

    Les libellés locaux affichés ici — « Odile, marché » — vivent uniquement
    sur cet appareil. Ils n'entrent dans aucun événement et ne remontent nulle
    part : le serveur ne connaîtra jamais l'identité de ces vingt personnes.
--}}
<x-layouts.kit titre="Pointage">

    <div x-data="pointage" x-cloak>

        <template x-if="! seance">
            <x-mvoe.vide>
                Aucune séance n'est ouverte.
                <x-slot:action>
                    <x-mvoe.bouton variante="second" href="/kit">Revenir à mon kit</x-mvoe.bouton>
                </x-slot:action>
            </x-mvoe.vide>
        </template>

        <template x-if="seance">
            <div class="space-y-4">

                <div class="flex items-baseline justify-between gap-4">
                    <h1 class="text-3xl">Pointer</h1>

                    {{-- Le compteur dit où on en est, sans presser personne. --}}
                    <p class="chiffre shrink-0 text-lg">
                        <span x-text="pointes"></span> sur <span x-text="total"></span> pointés
                    </p>
                </div>

                <div class="grid grid-cols-4 gap-1 sm:grid-cols-5">
                    <template x-for="p in parents" x-bind:key="p.code_parent">
                        <button type="button"
                                x-on:click="pointer(p.code_parent)"
                                x-bind:aria-label="(libelleDe(p.code_parent) || p.code_parent)
                                    + ' : ' + etiquettes[statuts[p.code_parent]]
                                    + '. Appuyer pour changer.'"
                                class="flex flex-col items-center gap-1.5 rounded-net p-2 text-center hover:bg-jaune-sourd">

                            <span class="relative flex size-tactile items-center justify-center">
                                <span class="pastille" x-bind:data-etat="statuts[p.code_parent]"
                                      aria-hidden="true"></span>

                                {{-- Deux maillons : « reçu par son binôme »,
                                     dit par une forme et non par une couleur. --}}
                                <svg class="absolute size-5" viewBox="0 0 24 24" fill="none"
                                     stroke="#121212" stroke-width="2.5" stroke-linecap="square"
                                     x-show="statuts[p.code_parent] === 'rattrape_binome'"
                                     aria-hidden="true">
                                    <path d="M9 12h6M8 8H6a4 4 0 0 0 0 8h2M16 8h2a4 4 0 0 1 0 8h-2"/>
                                </svg>
                            </span>

                            <span class="chiffre text-xs leading-none text-gris-texte"
                                  x-text="p.code_parent"></span>

                            <template x-if="libelleDe(p.code_parent)">
                                <span class="max-w-20 truncate text-sm leading-tight"
                                      x-text="libelleDe(p.code_parent)"></span>
                            </template>

                            <span class="intitule text-[0.6875rem]"
                                  x-bind:class="statuts[p.code_parent] === 'a_pointer' ? 'text-gris-texte' : ''"
                                  x-text="etiquettes[statuts[p.code_parent]]"></span>
                        </button>
                    </template>
                </div>

                <p class="text-sm text-gris-texte">
                    Un appui fait défiler : présent, absent, rattrapé par son binôme.
                </p>

                {{-- --------------------------------------------------------- --}}
                {{-- Les libellés locaux.                                       --}}
                <div class="rounded-carte border border-ligne p-4">
                    <div class="flex items-center justify-between gap-3">
                        <x-mvoe.intitule>Mes repères</x-mvoe.intitule>
                        <button type="button" x-on:click="modeLibelles = ! modeLibelles"
                                class="intitule underline underline-offset-4"
                                x-text="modeLibelles ? 'Terminer' : 'Modifier'">Modifier</button>
                    </div>

                    <p class="mt-2 text-sm text-gris-texte">
                        Écrivez ce qui vous aide à reconnaître chaque parent. Ces mots restent sur
                        ce téléphone : ils ne sont jamais envoyés et sont effacés en fin de cycle.
                    </p>

                    <div x-cloak x-show="modeLibelles" class="mt-3 space-y-2">
                        <template x-for="p in parents" x-bind:key="'l-' + p.code_parent">
                            <div class="flex items-center gap-3">
                                <label class="chiffre w-16 shrink-0 text-sm"
                                       x-bind:for="'lib-' + p.code_parent"
                                       x-text="p.code_parent"></label>

                                <input type="text"
                                       x-bind:id="'lib-' + p.code_parent"
                                       x-bind:value="libelleDe(p.code_parent) ?? ''"
                                       x-on:change="definirLibelle(p.code_parent, $event.target.value)"
                                       placeholder="Odile, marché"
                                       class="min-h-tactile w-full rounded-net border-2 border-noir px-3 text-base">
                            </div>
                        </template>
                    </div>
                </div>

                <div class="flex flex-wrap gap-2">
                    <x-mvoe.bouton class="flex-1" x-on:click="retour()">
                        Revenir au déroulé
                    </x-mvoe.bouton>
                </div>
            </div>
        </template>
    </div>
</x-layouts.kit>
