{{--
    Le feuilleton.

    Épisodes numérotés, en audio. La reprise se fait là où le parent s'était
    arrêté, SANS JAMAIS LUI REPROCHER SON ABSENCE : pas de « vous avez manqué
    deux épisodes », pas de pourcentage d'avancement, pas de série à ne pas
    briser. Un épisode déjà commencé dit simplement « à reprendre ».

    La position de lecture vit dans l'onglet, jamais sur le serveur : un
    historique d'écoute sur un téléphone partagé dirait à quelqu'un d'autre ce
    que ce parent écoute, et quand.
--}}
<x-layouts.parent titre="Le feuilleton" composant="feuilletonParent">

    <div class="space-y-4">

        <div class="flex items-center gap-3">
            <a href="/parent/accueil"
               class="flex size-tactile shrink-0 items-center justify-center rounded-net border-2 border-noir"
               aria-label="Revenir">
                <svg class="size-6" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                     stroke-width="2.5" stroke-linecap="square" aria-hidden="true">
                    <path d="M15 5 8 12l7 7"/>
                </svg>
            </a>

            <h1 class="text-3xl">Le feuilleton</h1>
        </div>

        <p x-show="chargement" class="text-gris-texte">Chargement…</p>
        <p x-show="erreur" x-text="erreur" class="rounded-net border-2 border-noir px-3 py-2"></p>

        <template x-if="! chargement && ! feuilleton">
            <x-mvoe.vide>Le feuilleton n'est pas encore disponible.</x-mvoe.vide>
        </template>

        <template x-if="feuilleton">
            <div class="space-y-4">

                <div>
                    <p class="text-2xl font-semibold [font-family:var(--font-titre)]"
                       x-text="feuilleton.titre"></p>
                    <p class="mt-2 text-base text-gris-texte" x-text="feuilleton.resume"></p>
                </div>

                <ul class="space-y-2">
                    <template x-for="e in episodes" x-bind:key="e.id">
                        <li>
                            <button type="button" x-on:click="ouvrir(e)"
                                    class="flex min-h-tactile w-full items-center gap-4 rounded-carte border-[3px] border-noir bg-blanc px-4 py-4 text-left hover:bg-jaune"
                                    x-bind:class="episodeCourant?.id === e.id ? 'bg-jaune' : ''">
                                <span class="chiffre shrink-0 text-2xl" x-text="e.numero"></span>

                                <span class="flex-1">
                                    <span class="block text-lg" x-text="e.titre"></span>
                                    <span class="chiffre block text-sm text-gris-texte"
                                          x-text="e.duree_lisible"></span>
                                </span>

                                {{-- « À reprendre », pas « 37 % » : on ne mesure
                                     pas l'assiduité de quelqu'un. --}}
                                <span x-show="commence(e)" class="intitule shrink-0 text-xs">
                                    À reprendre
                                </span>
                            </button>
                        </li>
                    </template>
                </ul>

                {{-- Le lecteur de l'épisode ouvert. --}}
                <template x-if="episodeCourant">
                    <div class="rounded-carte border-[3px] border-noir p-4">
                        <div class="flex items-start justify-between gap-3">
                            <p class="text-xl font-semibold [font-family:var(--font-titre)]">
                                <span class="chiffre" x-text="episodeCourant.numero + '.'"></span>
                                <span x-text="episodeCourant.titre"></span>
                            </p>

                            <button type="button" x-on:click="fermer()"
                                    class="intitule shrink-0 underline underline-offset-4">Fermer</button>
                        </div>

                        <template x-if="episodeCourant.fichier_audio">
                            <audio class="mt-3 w-full" controls preload="metadata"
                                   x-bind:src="episodeCourant.fichier_audio"
                                   x-on:loadedmetadata="reprendre($event.target, episodeCourant)"
                                   x-on:timeupdate.throttle.2000ms="noter($event.target, episodeCourant)"
                                   x-on:pause="noter($event.target, episodeCourant)"></audio>
                        </template>

                        <template x-if="! episodeCourant.fichier_audio">
                            <p class="mt-3 rounded-net bg-jaune-sourd px-3 py-3 text-base">
                                Cet épisode n'est pas encore enregistré.
                            </p>
                        </template>

                        <p x-show="commence(episodeCourant)"
                           class="chiffre mt-2 text-sm text-gris-texte">
                            Reprise à <span x-text="minutes(positionDe(episodeCourant))"></span>
                        </p>
                    </div>
                </template>
            </div>
        </template>
    </div>
</x-layouts.parent>
