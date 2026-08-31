{{--
    AUCUN modèle de langage n'intervient. L'assistant RETROUVE une unité validée
    du curriculum et la restitue mot pour mot, avec sa référence de module.

    LE REFUS EST SOIGNÉ AUTANT QUE LA RÉPONSE. Il n'a rien d'une erreur : sur un
    sujet de protection de l'enfance, savoir dire qu'on ne sait pas est une
    fonctionnalité.
--}}
<x-layouts.parent titre="Poser une question" composant="assistantParent">

    <div class="mb-6">
        <ul class="flex space-x-2 rtl:space-x-reverse mb-3">
            <li>
                <a href="/parent/accueil" class="text-primary hover:underline">Accueil</a>
            </li>
            <li class="before:content-['/'] ltr:before:mr-1 rtl:before:ml-1">
                <span>Poser une question</span>
            </li>
        </ul>

        <h2 class="text-2xl font-bold dark:text-white-light">Poser une question</h2>
        <p class="text-white-dark mt-1 max-w-prose">
            La réponse vient du programme, mot pour mot. Rien n'est rédigé pour vous :
            chaque phrase a été écrite puis validée par le ministère.
        </p>
    </div>

    <div x-show="erreur" class="panel border-l-4 border-warning mb-6">
        <p x-text="erreur"></p>
    </div>

    <div x-show="occupe" class="panel mb-6">
        <p class="text-white-dark">Recherche dans le programme…</p>
    </div>

    <template x-if="! resultat && ! occupe">
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

            {{-- L'entrée guidée vient EN PREMIER : elle sert le parent qui ne
                 sait pas écrire, et c'est la majorité de ceux à qui ce
                 programme s'adresse. --}}
            <div class="panel lg:col-span-2">
                <h5 class="font-semibold text-lg dark:text-white-light mb-5">Choisissez une situation</h5>

                <p x-show="chargement" class="text-white-dark">Chargement…</p>

                <div class="space-y-3">
                    <template x-for="s in situations" x-bind:key="s.id">
                        <div class="flex gap-2">
                            <button type="button" x-on:click="poserSituation(s)"
                                    class="flex min-h-tactile flex-1 items-center gap-3 rounded-md border border-white-light dark:border-[#1b2e4b] px-4 text-left transition hover:border-primary hover:text-primary">
                                <span class="chiffre shrink-0 rounded bg-jaune-sourd px-2 py-1 text-xs text-noir"
                                      x-text="s.pictogramme"></span>
                                <span class="flex-1" x-text="s.libelle"></span>
                            </button>

                            <button type="button" class="btn btn-outline-primary w-14 shrink-0"
                                    x-on:click="ecouter(s.fichier_audio)"
                                    x-bind:aria-label="'Écouter : ' + s.libelle">
                                <svg class="w-5 h-5" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true">
                                    <path d="M4 9v6h4l5 4V5L8 9H4z" />
                                </svg>
                            </button>
                        </div>
                    </template>
                </div>
            </div>

            <div class="panel h-fit">
                <h5 class="font-semibold text-lg dark:text-white-light mb-5">Ou écrivez votre question</h5>

                <form x-on:submit.prevent="poserTexte()">
                    <textarea id="texte" x-model="texte" rows="4" maxlength="500"
                              class="form-textarea" placeholder="Mon enfant refuse d'aller à l'école."></textarea>

                    <button type="submit" class="btn btn-primary btn-lg w-full mt-4"
                            x-bind:disabled="texte.trim() === ''">
                        Demander
                    </button>
                </form>

                <p class="text-white-dark text-[11px] mt-4">
                    Si le programme ne couvre pas votre question, il le dira et vous donnera
                    le numéro d'un facilitateur.
                </p>
            </div>
        </div>
    </template>

    <template x-if="resultat && resultat.trouve">
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

            <div class="panel lg:col-span-2">
                <p class="text-white-dark text-xs" x-text="question"></p>

                {{-- Restitué MOT POUR MOT. Aucune phrase n'est composée par la
                     machine : ce texte a été écrit puis validé par le ministère,
                     et c'est ce qui le rend vérifiable. --}}
                <h5 class="text-2xl font-bold leading-snug dark:text-white-light mt-3"
                    x-text="resultat.reponse"></h5>
                <p class="text-white-dark text-xs mt-4" x-text="resultat.reference"></p>

                <template x-if="resultat.texte">
                    <div class="rounded-md bg-jaune-sourd p-4 mt-6">
                        <p class="text-lg leading-relaxed text-noir" x-text="resultat.texte"></p>

                        <ul class="mt-4 flex flex-wrap gap-2">
                            <template x-for="p in (resultat.pictogrammes || [])" x-bind:key="p">
                                <li class="chiffre rounded bg-blanc px-3 py-2 text-sm text-noir" x-text="p"></li>
                            </template>
                        </ul>
                    </div>
                </template>
            </div>

            <div class="panel h-fit space-y-3">
                <button type="button" class="btn btn-primary btn-lg w-full"
                        x-on:click="ecouter(resultat.fichier_audio)"
                        x-bind:disabled="! resultat.fichier_audio">
                    <svg class="w-5 h-5 ltr:mr-2 rtl:ml-2" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true">
                        <path d="M4 9v6h4l5 4V5L8 9H4z" />
                    </svg>
                    Écouter
                </button>

                <button type="button" class="btn btn-outline-primary w-full" x-on:click="recommencer()">
                    Poser une autre question
                </button>
            </div>
        </div>
    </template>

    {{-- LE REFUS. Traité avec le même soin que la réponse : pas de rouge, pas
         d'« erreur », pas d'excuse. Le système dit ce qu'il ne sait pas et
         donne un numéro à appeler. C'est le comportement qu'il faut montrer,
         pas celui qu'il faut cacher. --}}
    <template x-if="resultat && ! resultat.trouve">
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

            <div class="panel lg:col-span-2">
                <p class="text-white-dark text-xs" x-text="question"></p>

                <h5 class="text-2xl font-bold leading-snug dark:text-white-light mt-3"
                    x-text="resultat.message"></h5>

                <p class="text-lg mt-4">
                    Le programme ne couvre pas encore ce sujet. Plutôt que de vous répondre
                    à côté, il préfère vous adresser à quelqu'un.
                </p>

                <button type="button" class="btn btn-outline-primary mt-6" x-on:click="recommencer()">
                    Poser une autre question
                </button>
            </div>

            <div class="panel h-fit">
                <h5 class="font-semibold text-lg dark:text-white-light mb-5">
                    Un facilitateur peut vous répondre
                </h5>

                <div class="space-y-4">
                    <template x-for="c in resultat.contacts" x-bind:key="c.telephone">
                        <div class="rounded-md border border-white-light dark:border-[#1b2e4b] p-4">
                            <p class="font-semibold dark:text-white-light" x-text="c.nom"></p>
                            <p class="text-white-dark text-xs" x-text="c.arrondissement"></p>

                            <a x-bind:href="lienTelephone(c.telephone)" class="btn btn-primary w-full mt-3">
                                <svg class="w-5 h-5 ltr:mr-2 rtl:ml-2 shrink-0" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true">
                                    <path d="M6 3h3l2 5-2 1a12 12 0 0 0 5 5l1-2 5 2v3a2 2 0 0 1-2 2A16 16 0 0 1 4 5a2 2 0 0 1 2-2z" />
                                </svg>
                                <span class="chiffre" x-text="c.telephone"></span>
                            </a>
                        </div>
                    </template>
                </div>
            </div>
        </div>
    </template>
</x-layouts.parent>
