{{--
    « Poser une question » — l'assistant à corpus fermé.

    AUCUN modèle de langage n'intervient. L'assistant RETROUVE une unité validée
    du curriculum et la restitue mot pour mot, avec sa référence de module.
    Toute phrase lue ici a été écrite puis validée par le ministère, et reste
    vérifiable ligne à ligne contre le guide officiel.

    Deux entrées, une seule mécanique : la liste de situations fréquentes — pour
    qui ne sait pas écrire — et le champ libre passent par le même appariement.
    Les libellés de situations ne sont pas des réponses pré-écrites : plusieurs
    ne trouvent rien, et c'est voulu.

    LE REFUS EST SOIGNÉ AUTANT QUE LA RÉPONSE. Il n'a rien d'une erreur : sur un
    sujet de protection de l'enfance, savoir dire qu'on ne sait pas est une
    fonctionnalité.
--}}
<x-layouts.parent titre="Poser une question" composant="assistantParent">

    <div class="space-y-5">

        <div class="flex items-center gap-3">
            <a href="/parent/accueil"
               class="flex size-tactile shrink-0 items-center justify-center rounded-net border-2 border-noir"
               aria-label="Revenir">
                <svg class="size-6" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                     stroke-width="2.5" stroke-linecap="square" aria-hidden="true">
                    <path d="M15 5 8 12l7 7"/>
                </svg>
            </a>

            <h1 class="text-3xl">Poser une question</h1>
        </div>

        <p x-show="erreur" x-text="erreur" class="rounded-net border-2 border-noir px-3 py-2"></p>

        {{-- ---------------------------------------------------------------- --}}
        {{-- Les deux entrées, tant qu'aucune question n'a été posée.          --}}
        <template x-if="! resultat && ! occupe">
            <div class="space-y-6">

                {{-- Entrée guidée. Elle vient EN PREMIER : elle sert le parent
                     qui ne sait pas écrire, et c'est la majorité de ceux à qui
                     ce programme s'adresse. --}}
                <div class="space-y-2">
                    <p class="intitule">Choisissez une situation</p>

                    <p x-show="chargement" class="text-gris-texte">Chargement…</p>

                    <template x-for="s in situations" x-bind:key="s.id">
                        <div class="flex gap-2">
                            <button type="button" x-on:click="poserSituation(s)"
                                    class="flex min-h-tactile flex-1 items-center gap-3 rounded-carte border-[3px] border-noir bg-blanc px-4 py-4 text-left hover:bg-jaune">
                                <span class="chiffre shrink-0 rounded-net bg-jaune-sourd px-2 py-1 text-xs"
                                      x-text="s.pictogramme"></span>
                                <span class="flex-1 text-lg" x-text="s.libelle"></span>
                            </button>

                            <button type="button" x-on:click="ecouter(s.fichier_audio)"
                                    class="flex min-h-tactile w-16 items-center justify-center rounded-carte bg-jaune text-noir"
                                    x-bind:aria-label="'Écouter : ' + s.libelle">
                                <svg class="size-7" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true">
                                    <path d="M4 9v6h4l5 4V5L8 9H4z"/>
                                </svg>
                            </button>
                        </div>
                    </template>
                </div>

                {{-- Entrée libre, pour ceux qui écrivent. --}}
                <form x-on:submit.prevent="poserTexte()" class="space-y-2">
                    <label for="texte" class="intitule block">Ou écrivez votre question</label>

                    <textarea id="texte" x-model="texte" rows="3" maxlength="500"
                              class="w-full rounded-net border-[3px] border-noir px-3 py-3 text-lg"></textarea>

                    <x-mvoe.bouton type="submit" class="w-full py-4 text-xl"
                                   x-bind:disabled="texte.trim() === ''">
                        Demander
                    </x-mvoe.bouton>
                </form>
            </div>
        </template>

        <p x-show="occupe" class="text-gris-texte">Recherche dans le programme…</p>

        {{-- ---------------------------------------------------------------- --}}
        {{-- La réponse trouvée.                                               --}}
        <template x-if="resultat && resultat.trouve">
            <div class="space-y-4">

                <p class="text-base text-gris-texte" x-text="question"></p>

                {{-- Restitué MOT POUR MOT. Aucune phrase n'est composée par la
                     machine : ce texte a été écrit puis validé par le ministère,
                     et c'est ce qui le rend vérifiable. --}}
                <div class="rounded-carte border-[3px] border-noir p-5">
                    <p class="text-2xl leading-snug" x-text="resultat.reponse"></p>

                    <p class="mt-4 text-sm text-gris-texte">
                        <span x-text="resultat.reference"></span>
                    </p>
                </div>

                <div class="flex gap-2">
                    <button type="button" x-on:click="ecouter(resultat.fichier_audio)"
                            x-bind:disabled="! resultat.fichier_audio"
                            class="flex min-h-tactile flex-1 items-center justify-center gap-3 rounded-carte bg-jaune px-4 text-noir disabled:opacity-40">
                        <svg class="size-7 shrink-0" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true">
                            <path d="M4 9v6h4l5 4V5L8 9H4z"/>
                        </svg>
                        <span class="text-lg font-semibold [font-family:var(--font-titre)]">Écouter</span>
                    </button>
                </div>

                <template x-if="resultat.texte">
                    <div class="rounded-carte bg-jaune-sourd p-4">
                        <p class="text-lg leading-relaxed" x-text="resultat.texte"></p>

                        <ul class="mt-3 flex flex-wrap gap-2">
                            <template x-for="p in (resultat.pictogrammes || [])" x-bind:key="p">
                                <li class="chiffre rounded-net bg-blanc px-3 py-2 text-sm" x-text="p"></li>
                            </template>
                        </ul>
                    </div>
                </template>

                <x-mvoe.bouton variante="second" class="w-full py-4" x-on:click="recommencer()">
                    Poser une autre question
                </x-mvoe.bouton>
            </div>
        </template>

        {{-- ---------------------------------------------------------------- --}}
        {{-- LE REFUS.                                                         --}}
        {{--                                                                   --}}
        {{-- Traité avec le même soin que la réponse : pas de rouge, pas de    --}}
        {{-- « erreur », pas d'excuse. Le système dit ce qu'il ne sait pas et   --}}
        {{-- donne un numéro à appeler. C'est le comportement qu'il faut        --}}
        {{-- montrer, pas celui qu'il faut cacher.                             --}}
        <template x-if="resultat && ! resultat.trouve">
            <div class="space-y-4">

                <p class="text-base text-gris-texte" x-text="question"></p>

                <div class="rounded-carte border-[3px] border-noir p-5">
                    <p class="text-2xl leading-snug" x-text="resultat.message"></p>
                    <p class="mt-3 text-lg">
                        Le programme ne couvre pas encore ce sujet. Plutôt que de vous répondre
                        à côté, il préfère vous adresser à quelqu'un.
                    </p>
                </div>

                <div>
                    <p class="intitule">Un facilitateur peut vous répondre</p>

                    <ul class="mt-2 space-y-2">
                        <template x-for="c in resultat.contacts" x-bind:key="c.telephone">
                            <li class="rounded-carte border-[3px] border-noir p-4">
                                <p class="text-xl font-semibold [font-family:var(--font-titre)]"
                                   x-text="c.nom"></p>
                                <p class="text-base text-gris-texte" x-text="c.arrondissement"></p>

                                <a x-bind:href="lienTelephone(c.telephone)"
                                   class="mt-3 flex min-h-tactile items-center justify-center gap-3 rounded-net bg-jaune px-4 text-noir">
                                    <svg class="size-6 shrink-0" viewBox="0 0 24 24" fill="currentColor"
                                         aria-hidden="true">
                                        <path d="M6 3h3l2 5-2 1a12 12 0 0 0 5 5l1-2 5 2v3a2 2 0 0 1-2 2A16 16 0 0 1 4 5a2 2 0 0 1 2-2z"/>
                                    </svg>
                                    <span class="chiffre text-xl" x-text="c.telephone"></span>
                                </a>
                            </li>
                        </template>
                    </ul>
                </div>

                <x-mvoe.bouton variante="second" class="w-full py-4" x-on:click="recommencer()">
                    Poser une autre question
                </x-mvoe.bouton>
            </div>
        </template>
    </div>
</x-layouts.parent>
