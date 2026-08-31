{{--
    Un facilitateur formé il y a deux ans ne se refait pas former : il rouvre
    ses modules. Cet écran fonctionne HORS LIGNE — on révise dans un car, sur un
    banc, en attendant que la salle se remplisse. Ce faisant il rouvre
    l'application, donc il reste actif dans le registre : c'est le seul
    dispositif de réactivation qui ne coûte ni déplacement ni convocation.
--}}
<x-layouts.kit titre="Ma formation">

    <div x-data="formationFacilitateur" x-cloak>

        <template x-if="! module">
            <div>
                <div class="mb-6">
                    <h2 class="text-2xl font-bold dark:text-white-light">Ma formation</h2>
                    <p class="text-white-dark mt-1">
                        Tout est lisible sans réseau. Reprenez où vous en étiez.
                    </p>
                </div>

                <div x-show="chargement" class="panel">
                    <p class="text-white-dark">Chargement…</p>
                </div>

                {{-- Un facilitateur qui vient d'être enregistré n'a pas encore de
                     cohorte, donc pas de paquet. Ses modules lui sont servis par
                     le réseau : il est encore assis en face de son superviseur. --}}
                <template x-if="! chargement && modules.length === 0">
                    <div class="panel">
                        <p class="text-white-dark" x-show="horsLigne">
                            Vos modules demandent du réseau la première fois. Ensuite, ils partent
                            avec votre paquet de cohorte.
                        </p>
                        <p class="text-white-dark" x-show="! horsLigne">
                            Aucun module n'est encore publié.
                        </p>
                        <a href="/kit" class="btn btn-outline-primary mt-5 inline-flex">Revenir à mon kit</a>
                    </div>
                </template>

                <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                    <template x-for="m in modules" x-bind:key="m.code">
                        <div class="panel h-full">
                            <div class="flex items-start justify-between gap-3">
                                <div>
                                    <p class="text-xs font-semibold uppercase tracking-wider text-white-dark"
                                       x-text="m.type_libelle"></p>
                                    <h5 class="font-semibold text-lg dark:text-white-light mt-1" x-text="m.titre"></h5>
                                </div>
                                <span class="badge bg-success shadow-md shrink-0"
                                      x-show="avancementDe(m) === 100">Terminé</span>
                            </div>

                            <p class="text-white-dark mt-2" x-text="m.objectif"></p>

                            <p class="chiffre text-white-dark text-xs mt-3">
                                <span x-text="m.sections.length"></span> sections ·
                                <span x-text="m.duree_minutes"></span> min
                            </p>

                            <div class="w-full rounded-full h-2 bg-dark-light dark:bg-[#1b2e4b] shadow mt-3"
                                 x-show="avancementDe(m) > 0">
                                <div class="bg-gradient-to-r from-[#3cba92] to-[#0ba360] h-full rounded-full"
                                     x-bind:style="'width: ' + avancementDe(m) + '%'"></div>
                            </div>

                            <button type="button" class="btn btn-primary w-full mt-5" x-on:click="ouvrir(m.code)">
                                <span x-text="avancementDe(m) > 0 ? 'Reprendre' : 'Ouvrir le module'">Ouvrir le module</span>
                            </button>
                        </div>
                    </template>
                </div>
            </div>
        </template>

        <template x-if="module">
            <div>
                <ul class="flex space-x-2 rtl:space-x-reverse mb-5">
                    <li>
                        <a href="javascript:;" class="text-primary hover:underline"
                           x-on:click="fermer()">Tous mes modules</a>
                    </li>
                    <li class="before:content-['/'] ltr:before:mr-1 rtl:before:ml-1">
                        <span x-text="module.titre"></span>
                    </li>
                </ul>

                <div class="mb-6">
                    <h2 class="text-2xl font-bold dark:text-white-light" x-text="module.titre"></h2>
                    <p class="text-white-dark mt-1" x-text="module.objectif"></p>
                </div>

                <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

                    <div class="lg:col-span-2 space-y-6">

                        {{-- Où il en est. Le compte des sections lues, pas une
                             barre décorative : c'est ce que son superviseur
                             verra aussi. --}}
                        <div class="panel">
                            <div class="flex flex-wrap items-center justify-between gap-3">
                                <p>
                                    Section <span class="chiffre" x-text="section"></span>
                                    sur <span class="chiffre" x-text="module.sections.length"></span>
                                </p>
                                <p class="chiffre font-semibold"><span x-text="avancement"></span> % lu</p>
                            </div>
                            <div class="w-full rounded-full h-2 bg-dark-light dark:bg-[#1b2e4b] shadow mt-3">
                                <div class="bg-gradient-to-r from-[#3cba92] to-[#0ba360] h-full rounded-full"
                                     x-bind:style="'width: ' + avancement + '%'"></div>
                            </div>
                        </div>

                        <template x-if="sectionCourante">
                            <div class="panel">
                                <h5 class="font-semibold text-lg dark:text-white-light" x-text="sectionCourante.titre"></h5>
                                <p class="chiffre text-white-dark text-xs mt-1">
                                    <span x-text="sectionCourante.duree_minutes"></span> min
                                </p>

                                <p class="text-lg leading-relaxed mt-5" x-text="sectionCourante.contenu_texte"></p>

                                {{-- L'interface reste utilisable quand l'audio manque. --}}
                                <template x-if="sectionCourante.fichier_audio">
                                    <audio class="w-full mt-5" controls preload="none"
                                           x-bind:src="sectionCourante.fichier_audio"></audio>
                                </template>

                                <div class="flex gap-3 mt-6">
                                    <button type="button" class="btn btn-outline-primary flex-1"
                                            x-on:click="precedente()" x-bind:disabled="section <= 1">
                                        Précédente
                                    </button>
                                    <button type="button" class="btn btn-primary flex-1"
                                            x-on:click="suivante()" x-bind:disabled="derniereSection">
                                        Suivante
                                    </button>
                                </div>
                            </div>
                        </template>

                        {{-- Terminé se constate, il ne se déclare pas : il a lu
                             les sections, cela suffit. --}}
                        <div class="panel border-l-4 border-success" x-show="avancement === 100">
                            <p>
                                Module terminé. Votre superviseur le verra à la prochaine
                                synchronisation.
                            </p>
                        </div>
                    </div>

                    <div class="panel h-fit">
                        <h5 class="font-semibold text-lg dark:text-white-light mb-5">Le sommaire</h5>

                        <div class="space-y-1">
                            <template x-for="s in module.sections" x-bind:key="s.ordre">
                                <button type="button" x-on:click="aller(s.ordre)"
                                        class="flex min-h-tactile w-full items-center gap-3 rounded-md px-3 text-left"
                                        x-bind:class="s.ordre === section
                                            ? 'bg-primary-light text-primary font-semibold'
                                            : 'hover:bg-white-light/50'">
                                    <span class="chiffre" x-text="s.ordre"></span>
                                    <span class="flex-1" x-text="s.titre"></span>
                                    <span class="badge bg-success shadow-md shrink-0"
                                          x-show="vues.includes(s.ordre)">Lu</span>
                                </button>
                            </template>
                        </div>
                    </div>
                </div>
            </div>
        </template>
    </div>
</x-layouts.kit>
