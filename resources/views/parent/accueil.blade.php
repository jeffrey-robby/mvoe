{{--
    Accueil de l'espace parent.

    Trois grandes cartes, chacune audible d'un appui, plus un lien discret vers
    l'annuaire. Rien d'autre : ni actualites, ni compteur, ni progression, ni
    « vous avez ecoute 3 unites cette semaine ». Le programme ne fait pas la
    lecon aux parents.
--}}
<x-layouts.parent titre="Accueil" composant="accueilParent">

    <div class="space-y-4">

        <h1 class="text-3xl">Mvoé</h1>

        <div class="space-y-3">
            <template x-for="carte in cartes" x-bind:key="carte.cle">
                <div class="flex gap-2">
                    <a x-bind:href="carte.lien"
                       class="flex min-h-tactile flex-1 items-center rounded-carte border-[3px] border-noir bg-blanc px-5 py-6 text-2xl font-semibold [font-family:var(--font-titre)] hover:bg-jaune"
                       x-text="carte.titre"></a>

                    {{-- Chaque carte s'ecoute : aucun parcours ne depend de la
                         capacite a lire. --}}
                    <button type="button" x-on:click="ecouter(audioCarte(carte.cle))"
                            class="flex min-h-tactile w-16 items-center justify-center rounded-carte bg-jaune text-noir"
                            x-bind:aria-label="'Écouter : ' + carte.titre">
                        <svg class="size-7" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true">
                            <path d="M4 9v6h4l5 4V5L8 9H4z"/>
                        </svg>
                    </button>
                </div>
            </template>
        </div>

        {{-- Deux liens discrets. Ils ne concurrencent pas les trois cartes,
             mais restent atteignables a tout moment.

             Les questions de la semaine sont ici, et non en quatrieme carte :
             le cahier des charges en prescrit trois, et les questions figurent
             en deuxieme position sur la liste de ce qu'on coupe si le temps
             manque. Leur place dans l'interface reflete cette priorite. --}}
        <div class="flex items-center gap-2 pt-2">
            <a href="/parent/questions"
               class="flex min-h-tactile items-center text-base underline underline-offset-4">
                Les questions de la semaine
            </a>

            <button type="button" x-on:click="ecouter(audioCarte('questions'))"
                    class="flex min-h-tactile w-12 items-center justify-center rounded-net border-2 border-noir"
                    aria-label="Écouter : les questions de la semaine">
                <svg class="size-5" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true">
                    <path d="M4 9v6h4l5 4V5L8 9H4z"/>
                </svg>
            </button>
        </div>

        <div class="flex items-center gap-2">
            <a href="/parent/facilitateur"
               class="flex min-h-tactile items-center text-base underline underline-offset-4">
                Trouver un facilitateur
            </a>

            <button type="button" x-on:click="ecouter(audioCarte('facilitateur'))"
                    class="flex min-h-tactile w-12 items-center justify-center rounded-net border-2 border-noir"
                    aria-label="Écouter : trouver un facilitateur">
                <svg class="size-5" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true">
                    <path d="M4 9v6h4l5 4V5L8 9H4z"/>
                </svg>
            </button>
        </div>
    </div>
</x-layouts.parent>
