{{--
    JAMAIS de bonne ou mauvaise réponse affichée, jamais de score, jamais de
    total, pas même à la fin. L'explication est portée par la question et non
    par l'option : le texte lu est le même quel que soit le choix du parent.
    C'est ce qui rend l'absence de verdict structurelle, et non déclarative.
--}}
<x-layouts.parent titre="Les questions de la semaine" composant="questionsSemaine">

    <div class="mb-6">
        <ul class="flex space-x-2 rtl:space-x-reverse mb-3">
            <li>
                <a href="/parent/accueil" class="text-primary hover:underline">Accueil</a>
            </li>
            <li class="before:content-['/'] ltr:before:mr-1 rtl:before:ml-1">
                <span>Les questions de la semaine</span>
            </li>
        </ul>

        <h2 class="text-2xl font-bold dark:text-white-light">Les questions de la semaine</h2>
        <p class="text-white-dark mt-1">
            Il n'y a rien à réussir ici. À chaque fois, le programme dit ce qu'il
            propose, et pourquoi.
        </p>
    </div>

    <div x-show="chargement" class="panel mb-6">
        <p class="text-white-dark">Chargement…</p>
    </div>

    <div x-show="erreur" class="panel border-l-4 border-warning mb-6">
        <p x-text="erreur"></p>
    </div>

    <template x-if="question && ! termine">
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

            <div class="panel lg:col-span-2">
                {{-- Le rang situe, il ne note pas : « 2 sur 3 » dit où l'on en
                     est du parcours, pas combien de points on a. --}}
                <div class="flex items-center justify-between gap-3 mb-5">
                    <p class="chiffre text-xs text-white-dark">
                        <span x-text="rang + 1"></span> sur <span x-text="questions.length"></span>
                    </p>

                    <button type="button" class="btn btn-outline-primary btn-sm"
                            x-on:click="ecouter(question.enonce_audio)" aria-label="Écouter la question">
                        <svg class="w-4 h-4 ltr:mr-2 rtl:ml-2" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true">
                            <path d="M4 9v6h4l5 4V5L8 9H4z" />
                        </svg>
                        Écouter
                    </button>
                </div>

                <h5 class="text-2xl font-bold leading-snug dark:text-white-light" x-text="question.enonce"></h5>

                {{-- Les réponses. L'option choisie est simplement marquée comme
                     choisie — aucune n'est marquée juste ou fausse, et l'API ne
                     renvoie rien qui permettrait de le faire. --}}
                <div class="space-y-3 mt-6">
                    <template x-for="o in question.options" x-bind:key="o.id">
                        <button type="button" x-on:click="repondre(o)"
                                x-bind:disabled="explication !== null"
                                class="flex min-h-tactile w-full items-center gap-4 rounded-md border px-4 text-left transition"
                                x-bind:class="choix === o.id
                                    ? 'border-primary bg-primary-light text-primary font-semibold'
                                    : 'border-white-light dark:border-[#1b2e4b] hover:border-primary'"
                                x-bind:aria-pressed="choix === o.id">
                            <span class="chiffre shrink-0 rounded bg-jaune-sourd px-2 py-1 text-xs text-noir"
                                  x-text="o.pictogramme"></span>
                            <span class="flex-1 text-lg" x-text="o.libelle"></span>
                        </button>
                    </template>
                </div>
            </div>

            <div class="h-fit">
                <template x-if="! explication">
                    <div class="panel">
                        <p class="text-white-dark">
                            Choisissez une réponse. Le programme dira ensuite ce qu'il propose —
                            le même texte, quel que soit votre choix.
                        </p>
                    </div>
                </template>

                <template x-if="explication">
                    <div>
                        <div class="panel border-l-4 border-primary">
                            <p class="text-xs font-semibold uppercase tracking-wider text-white-dark">
                                Ce que propose le programme
                            </p>
                            <p class="text-lg leading-relaxed mt-3" x-text="explication.explication"></p>
                            <p class="text-white-dark text-xs mt-4" x-text="explication.reference"></p>
                        </div>

                        {{-- Sans compte, la réponse n'est comptée nulle part.
                             Ce n'est pas un mur : le texte du programme, qui est
                             tout l'intérêt de l'exercice, s'affiche quand même. --}}
                        <div class="panel mt-6" x-show="explication.anonyme">
                            <p class="text-white-dark">
                                Votre choix n'a été enregistré nulle part : il faut un code pour
                                cela. Le programme compte combien de parents choisissent chaque
                                réponse, jamais lesquels.
                            </p>
                        </div>

                        <button type="button" class="btn btn-primary btn-lg w-full mt-6" x-on:click="suivante()">
                            <span x-text="derniere ? 'Terminer' : 'Question suivante'">Question suivante</span>
                        </button>
                    </div>
                </template>
            </div>
        </div>
    </template>

    {{-- La fin. Aucun total, aucun bilan, aucun « vous avez eu 2 sur 3 ». --}}
    <template x-if="termine">
        <div class="panel max-w-xl">
            <h5 class="text-2xl font-bold dark:text-white-light">C'est tout pour cette semaine.</h5>
            <p class="text-lg mt-3">
                Vous pouvez en parler à la prochaine séance, ou avec votre binôme.
            </p>
            <a href="/parent/accueil" class="btn btn-outline-primary mt-6 inline-flex">
                Revenir à l'accueil
            </a>
        </div>
    </template>
</x-layouts.parent>
