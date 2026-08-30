{{--
    Le tableau de bord du facilitateur.

    C'est le MÊME service serveur que celui des délégations, à la cinquième
    portée : la sienne. Les mêmes indicateurs, calculés par le même code — c'est
    ce qui fait qu'un facilitateur et son superviseur lisent les mêmes chiffres,
    et qu'aucune conversation ne commence par « pas selon mes calculs ».

    Seul écran du kit à demander du réseau : il regarde en arrière, il ne sert
    pas en séance. Hors ligne, il le dit.
--}}
<x-layouts.kit titre="Mon activité">

    <div x-data="tableauDeBordFacilitateur" x-cloak class="space-y-6">

        <div>
            <h1 class="text-3xl">Mon activité</h1>
            <p class="mt-1 text-gris-texte" x-text="facilitateur?.nom"></p>
        </div>

        <p x-show="chargement" class="text-gris-texte">Chargement…</p>

        <template x-if="horsLigne">
            <x-mvoe.vide>
                Ces chiffres viennent du serveur. Rebranchez-vous pour les voir.
                <x-slot:action>
                    <x-mvoe.bouton variante="second" href="/kit">Revenir à mon kit</x-mvoe.bouton>
                </x-slot:action>
            </x-mvoe.vide>
        </template>

        <template x-if="indicateurs">
            <div class="space-y-5">

                <dl class="grid grid-cols-2 gap-3">
                    <template x-for="c in [
                        { t: 'Cohortes', v: indicateurs.cohortes },
                        { t: 'Parents inscrits', v: indicateurs.parents_inscrits },
                        { t: 'Séances tenues', v: indicateurs.seances_tenues },
                        { t: 'Écarts relevés', v: indicateurs.ecarts_releves },
                    ]" x-bind:key="c.t">
                        <div class="rounded-carte border border-ligne p-4">
                            <dt class="intitule text-xs" x-text="c.t"></dt>
                            <dd class="chiffre mt-1 text-3xl" x-text="c.v"></dd>
                        </div>
                    </template>
                </dl>

                <div class="rounded-carte bg-jaune-sourd p-4">
                    <p class="text-lg">
                        <span class="chiffre text-2xl"
                              x-text="nombre(indicateurs.dose_moyenne_par_parent)"></span>
                        séances reçues en moyenne par parent inscrit.
                    </p>
                    <p class="mt-2 text-base">
                        Un parent rattrapé par son binôme a reçu la séance : il compte.
                    </p>
                </div>

                {{-- L'écart n'est pas une note. Le dire ici, sur SON écran, avant
                     que son superviseur ne le lise sur le sien. --}}
                <div class="rounded-carte border border-ligne p-4">
                    <p class="text-base">
                        Un écart entre ce que vous avez déclaré et ce que l'outil a
                        enregistré n'est pas une faute. Il montre un endroit du déroulé
                        qui résiste, et se lit avec vous.
                    </p>
                    <p class="mt-2 text-base" x-show="indicateurs.delai_moyen_remontee_jours !== null">
                        Vos séances remontent en
                        <span class="chiffre"
                              x-text="nombre(indicateurs.delai_moyen_remontee_jours)"></span>
                        jours en moyenne.
                    </p>
                </div>

                <x-mvoe.bouton variante="second" class="w-full" href="/kit">
                    Revenir à mon kit
                </x-mvoe.bouton>
            </div>
        </template>
    </div>
</x-layouts.kit>
