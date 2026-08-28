{{--
    Les questions de la semaine.

    Trois questions énoncées à voix haute, deux ou trois réponses illustrées.
    Après chaque réponse, l'application dit ce que propose le programme et
    pourquoi.

    JAMAIS de bonne ou mauvaise réponse affichée, jamais de score, jamais de
    total, pas même à la fin. L'explication est portée par la question et non
    par l'option : le texte lu est le même quel que soit le choix du parent.
    C'est ce qui rend l'absence de verdict structurelle, et non déclarative.
--}}
<x-layouts.parent titre="Les questions de la semaine" composant="questionsSemaine">

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

            <h1 class="text-3xl">Les questions de la semaine</h1>
        </div>

        <p x-show="chargement" class="text-gris-texte">Chargement…</p>
        <p x-show="erreur" x-text="erreur" class="rounded-net border-2 border-noir px-3 py-2"></p>

        {{-- ---------------------------------------------------------------- --}}
        {{-- Une question.                                                     --}}
        <template x-if="question && ! termine">
            <div class="space-y-4">

                {{-- Le rang situe, il ne note pas : « 2 sur 3 » dit où l'on en
                     est du parcours, pas combien de points on a. --}}
                <p class="chiffre text-sm text-gris-texte">
                    <span x-text="rang + 1"></span> sur <span x-text="questions.length"></span>
                </p>

                <div class="flex items-start gap-3">
                    <p class="flex-1 text-2xl leading-snug" x-text="question.enonce"></p>

                    <button type="button" x-on:click="ecouter(question.enonce_audio)"
                            class="flex min-h-tactile w-16 shrink-0 items-center justify-center rounded-carte bg-jaune text-noir"
                            aria-label="Écouter la question">
                        <svg class="size-7" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true">
                            <path d="M4 9v6h4l5 4V5L8 9H4z"/>
                        </svg>
                    </button>
                </div>

                {{-- Les réponses. L'option choisie est simplement marquée comme
                     choisie — aucune n'est marquée juste ou fausse, et l'API ne
                     renvoie rien qui permettrait de le faire. --}}
                <div class="space-y-2">
                    <template x-for="o in question.options" x-bind:key="o.id">
                        <button type="button" x-on:click="repondre(o)"
                                x-bind:disabled="explication !== null"
                                class="flex min-h-tactile w-full items-center gap-4 rounded-carte border-[3px] border-noir px-4 py-4 text-left"
                                x-bind:class="choix === o.id ? 'bg-jaune' : 'bg-blanc'"
                                x-bind:aria-pressed="choix === o.id">
                            <span class="chiffre shrink-0 rounded-net bg-jaune-sourd px-2 py-1 text-xs"
                                  x-text="o.pictogramme"></span>
                            <span class="flex-1 text-lg" x-text="o.libelle"></span>
                        </button>
                    </template>
                </div>

                {{-- Ce que propose le programme, et pourquoi. --}}
                <template x-if="explication">
                    <div class="rounded-carte border-[3px] border-noir bg-jaune-sourd p-5">
                        <p class="intitule">Ce que propose le programme</p>
                        <p class="mt-2 text-xl leading-relaxed" x-text="explication.explication"></p>
                        <p class="mt-3 text-sm text-gris-texte" x-text="explication.reference"></p>
                    </div>
                </template>

                <template x-if="explication">
                    <x-mvoe.bouton class="w-full py-4 text-xl" x-on:click="suivante()">
                        <span x-text="derniere ? 'Terminer' : 'Question suivante'">Question suivante</span>
                    </x-mvoe.bouton>
                </template>
            </div>
        </template>

        {{-- ---------------------------------------------------------------- --}}
        {{-- La fin. Aucun total, aucun bilan, aucun « vous avez eu 2 sur 3 ». --}}
        <template x-if="termine">
            <div class="space-y-4">
                <div class="rounded-carte border-[3px] border-noir p-5">
                    <p class="text-2xl">C'est tout pour cette semaine.</p>
                    <p class="mt-3 text-lg">
                        Vous pouvez en parler à la prochaine séance, ou avec votre binôme.
                    </p>
                </div>

                <x-mvoe.bouton variante="second" class="w-full py-4" href="/parent/accueil">
                    Revenir à l'accueil
                </x-mvoe.bouton>
            </div>
        </template>
    </div>
</x-layouts.parent>
