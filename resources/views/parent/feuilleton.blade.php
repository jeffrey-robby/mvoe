{{--
    La reprise se fait là où le parent s'était arrêté, SANS JAMAIS LUI REPROCHER
    SON ABSENCE : pas de « vous avez manqué deux épisodes », pas de pourcentage,
    pas de série à ne pas briser. La position de lecture vit dans l'onglet,
    jamais sur le serveur — un historique d'écoute sur un téléphone partagé
    dirait à quelqu'un d'autre ce que ce parent écoute, et quand.
--}}
<x-layouts.parent titre="Le feuilleton" composant="feuilletonParent">

    <div class="mb-6">
        <ul class="flex space-x-2 rtl:space-x-reverse mb-3">
            <li>
                <a href="/parent/accueil" class="text-primary hover:underline">Accueil</a>
            </li>
            <li class="before:content-['/'] ltr:before:mr-1 rtl:before:ml-1">
                <span>Le feuilleton</span>
            </li>
        </ul>

        <h2 class="text-2xl font-bold dark:text-white-light">Le feuilleton</h2>
        <p class="text-white-dark mt-1" x-show="feuilleton" x-text="feuilleton?.resume"></p>
    </div>

    <div x-show="chargement" class="panel mb-6">
        <p class="text-white-dark">Chargement…</p>
    </div>

    <div x-show="erreur" class="panel border-l-4 border-warning mb-6">
        <p x-text="erreur"></p>
    </div>

    <template x-if="! chargement && ! feuilleton">
        <div class="panel">
            <p class="text-white-dark">Le feuilleton n'est pas encore disponible.</p>
        </div>
    </template>

    <template x-if="feuilleton">
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

            <div class="panel h-fit">
                <h5 class="font-semibold text-lg dark:text-white-light mb-5" x-text="feuilleton.titre"></h5>

                <div class="space-y-2">
                    <template x-for="e in episodes" x-bind:key="e.id">
                        <button type="button" x-on:click="ouvrir(e)"
                                class="flex min-h-tactile w-full items-center gap-3 rounded-md px-3 text-left transition"
                                x-bind:class="episodeCourant?.id === e.id
                                    ? 'bg-primary-light text-primary font-semibold'
                                    : 'border border-white-light dark:border-[#1b2e4b] hover:border-primary'">
                            <span class="chiffre shrink-0 text-lg" x-text="e.numero"></span>

                            <span class="flex-1 min-w-0">
                                <span class="block truncate" x-text="e.titre"></span>
                                <span class="chiffre block text-xs text-white-dark" x-text="e.duree_lisible"></span>
                            </span>

                            {{-- « À reprendre », pas « 37 % » : on ne mesure pas
                                 l'assiduité de quelqu'un. --}}
                            <span class="badge bg-warning shadow-md shrink-0" x-show="commence(e)">
                                À reprendre
                            </span>
                        </button>
                    </template>
                </div>
            </div>

            <div class="lg:col-span-2">
                <template x-if="! episodeCourant">
                    <div class="panel">
                        <p class="text-white-dark">
                            Choisissez un épisode. Vous pouvez l'arrêter et le reprendre plus tard.
                        </p>
                    </div>
                </template>

                <template x-if="episodeCourant">
                    <div class="panel">
                        <div class="flex items-start justify-between gap-3 mb-5">
                            <div>
                                <p class="text-xs font-semibold uppercase tracking-wider text-white-dark">
                                    Épisode <span class="chiffre" x-text="episodeCourant.numero"></span>
                                </p>
                                <h5 class="font-semibold text-lg dark:text-white-light mt-1"
                                    x-text="episodeCourant.titre"></h5>
                            </div>

                            <button type="button" class="text-white-dark hover:text-dark" x-on:click="fermer()">
                                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                     stroke-width="1.5" stroke-linecap="round" class="w-6 h-6">
                                    <line x1="18" y1="6" x2="6" y2="18" />
                                    <line x1="6" y1="6" x2="18" y2="18" />
                                </svg>
                            </button>
                        </div>

                        <template x-if="episodeCourant.fichier_audio">
                            <audio class="w-full" controls preload="metadata"
                                   x-bind:src="episodeCourant.fichier_audio"
                                   x-on:loadedmetadata="reprendre($event.target, episodeCourant)"
                                   x-on:timeupdate.throttle.2000ms="noter($event.target, episodeCourant)"
                                   x-on:pause="noter($event.target, episodeCourant)"></audio>
                        </template>

                        <template x-if="! episodeCourant.fichier_audio">
                            <div class="flex items-center rounded border border-warning bg-warning-light p-3.5 text-warning">
                                <span>Cet épisode n'est pas encore enregistré.</span>
                            </div>
                        </template>

                        <p class="chiffre text-white-dark text-xs mt-3" x-show="commence(episodeCourant)">
                            Reprise à <span x-text="minutes(positionDe(episodeCourant))"></span>
                        </p>
                    </div>
                </template>
            </div>
        </div>
    </template>
</x-layouts.parent>
