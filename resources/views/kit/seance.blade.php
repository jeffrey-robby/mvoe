<x-layouts.kit titre="Séance">

    <div x-data="seance" x-cloak>

        <template x-if="! module">
            <div class="panel">
                <h5 class="font-semibold text-lg dark:text-white-light">Ce module n'est pas dans votre paquet</h5>
                <p class="text-white-dark mt-1">
                    Téléchargez le paquet de la cohorte que vous animez aujourd'hui.
                </p>
                <a href="/kit" class="btn btn-outline-primary mt-5 inline-flex">Revenir à mon kit</a>
            </div>
        </template>

        <template x-if="module">
            <div>
                <div class="flex flex-wrap items-end justify-between gap-3 mb-6">
                    <div>
                        <p class="text-xs font-semibold uppercase tracking-wider text-white-dark">
                            Module <span class="chiffre" x-text="module.numero"></span>
                        </p>
                        <h2 class="text-2xl font-bold dark:text-white-light" x-text="module.titre"></h2>
                        <p class="chiffre text-white-dark text-xs mt-1">
                            <span x-text="module.sequences.length"></span> séquences ·
                            <span x-text="module.duree_totale_minutes"></span> min
                        </p>
                    </div>

                    <div class="flex flex-wrap gap-3" x-cloak x-show="demarree">
                        <button type="button" class="btn btn-outline-primary" x-on:click="pointer()">
                            Pointer les présences
                        </button>
                        <button type="button" class="btn btn-outline-danger" x-on:click="terminer()">
                            Terminer la séance
                        </button>
                    </div>
                </div>

                <template x-if="! demarree">
                    <div class="panel mb-6">
                        <h5 class="font-semibold text-lg dark:text-white-light">Le déroulé est prêt</h5>
                        <p class="text-white-dark mt-1 max-w-prose">
                            Rien ici ne demande le réseau. Chaque séquence ouverte est écrite sur
                            l'appareil avant d'apparaître à l'écran.
                        </p>
                        <button type="button" class="btn btn-primary btn-lg mt-5" x-on:click="demarrer()">
                            Démarrer la séance
                        </button>
                    </div>
                </template>

                <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

                    <div class="lg:col-span-2 space-y-6">

                        {{-- La séquence en cours. Le jaune plein reste à la
                             colonne et au brise-glace ; ici c'est un panneau
                             comme les autres, avec le chronomètre en vedette. --}}
                        <template x-if="demarree && sequenceCourante && ! sequenceCourante.est_brise_glace">
                            <div class="panel border-l-4 border-warning">
                                <div class="flex flex-wrap items-start justify-between gap-4 mb-5">
                                    <div>
                                        <p class="text-xs font-semibold uppercase tracking-wider text-white-dark">
                                            Séquence <span class="chiffre" x-text="sequenceCourante.ordre"></span>
                                            sur <span class="chiffre" x-text="sequences.length"></span>
                                        </p>
                                        <h5 class="font-semibold text-lg dark:text-white-light mt-1"
                                            x-text="sequenceCourante.titre"></h5>
                                    </div>

                                    <div class="shrink-0 ltr:text-right rtl:text-left">
                                        <time class="chiffre block text-5xl leading-none font-bold"
                                              x-text="chrono">00:00</time>
                                        <p class="chiffre text-xs text-white-dark mt-1">
                                            prévu <span x-text="sequenceCourante.duree_minutes"></span> min
                                        </p>
                                    </div>
                                </div>

                                {{-- Le dépassement est une information, jamais un
                                     reproche : la barre déborde en orange, sans
                                     alerte et sans compte à rebours qui stresse
                                     quelqu'un en train d'animer. --}}
                                <div class="w-full rounded-full h-2 bg-dark-light dark:bg-[#1b2e4b] shadow">
                                    <div class="h-full rounded-full"
                                         x-bind:class="depassement > 0
                                             ? 'bg-gradient-to-r from-[#f09819] to-[#ff5858]'
                                             : 'bg-gradient-to-r from-[#4361ee] to-[#805dca]'"
                                         x-bind:style="'width: ' + Math.min(100, Math.round(100 * secondes
                                             / Math.max(1, sequenceCourante.duree_minutes * 60))) + '%'"></div>
                                </div>

                                <p class="chiffre text-xs text-warning mt-2" x-cloak x-show="depassement > 0">
                                    + <span x-text="Math.round(depassement / 60)"></span> min sur le temps prévu
                                </p>

                                <template x-if="sequenceCourante.consigne">
                                    <p class="text-lg mt-5" x-text="sequenceCourante.consigne"></p>
                                </template>

                                <template x-if="sequenceCourante.unites.length">
                                    <div class="mt-5 space-y-2">
                                        <template x-for="u in sequenceCourante.unites" x-bind:key="u.id">
                                            <button type="button" x-on:click="ouvrirUnite(u)"
                                                    class="flex min-h-tactile w-full items-center gap-3 rounded-md border border-white-light dark:border-[#1b2e4b] px-3 text-left transition hover:border-primary hover:text-primary">
                                                <svg class="w-5 h-5 shrink-0" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true">
                                                    <path d="M4 9v6h4l5 4V5L8 9H4z" />
                                                </svg>
                                                <span x-text="u.message_cle"></span>
                                            </button>
                                        </template>
                                    </div>
                                </template>

                                <button type="button" class="btn btn-primary btn-lg w-full mt-6"
                                        x-cloak x-show="! estDerniere" x-on:click="suivante()">
                                    Séquence suivante
                                </button>

                                <button type="button" class="btn btn-primary btn-lg w-full mt-6"
                                        x-cloak x-show="estDerniere" x-on:click="terminer()">
                                    Terminer la séance
                                </button>
                            </div>
                        </template>

                        {{-- Le brise-glace. Bande jaune pleine sur toute la
                             largeur, AUCUN contrôle dedans, AUCUN chronomètre.
                             C'est le moment où l'outil se retire et où la salle
                             prend le relais. La trace d'ouverture continue
                             pourtant d'être enregistrée : l'outil se tait, il
                             n'oublie pas. --}}
                        <template x-if="demarree && sequenceCourante && sequenceCourante.est_brise_glace">
                            <div>
                                <div class="bande-brise-glace rounded-md px-6 py-16 text-center text-2xl"
                                     x-text="sequenceCourante.consigne || sequenceCourante.titre"></div>

                                <button type="button" class="btn btn-outline-primary w-full mt-4"
                                        x-on:click="suivante()">
                                    Reprendre le déroulé
                                </button>
                            </div>
                        </template>

                        <template x-if="uniteOuverte">
                            <div class="panel">
                                <div class="flex items-start justify-between gap-3 mb-5">
                                    <h5 class="font-semibold text-lg dark:text-white-light"
                                        x-text="uniteOuverte.message_cle"></h5>
                                    <button type="button" class="text-white-dark hover:text-dark"
                                            x-on:click="uniteOuverte = null">
                                        <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                             stroke-width="1.5" stroke-linecap="round" class="w-6 h-6">
                                            <line x1="18" y1="6" x2="6" y2="18" />
                                            <line x1="6" y1="6" x2="18" y2="18" />
                                        </svg>
                                    </button>
                                </div>

                                <div class="flex flex-wrap gap-2" role="group" aria-label="Langue">
                                    <template x-for="l in ['fr', 'en', 'bulu']" x-bind:key="l">
                                        <button type="button" class="btn btn-sm" x-on:click="langue = l"
                                                x-bind:class="langue === l ? 'btn-primary' : 'btn-outline-primary'"
                                                x-bind:aria-pressed="langue === l"
                                                x-text="{ fr: 'Français', en: 'English', bulu: 'Bulu' }[l]"></button>
                                    </template>
                                </div>

                                <div class="flex flex-wrap gap-2 mt-3" role="group" aria-label="Modalité">
                                    <button type="button" class="btn btn-sm" x-on:click="modalite = 'audio'"
                                            x-bind:class="modalite === 'audio' ? 'btn-primary' : 'btn-outline-primary'"
                                            x-bind:aria-pressed="modalite === 'audio'">Écouter</button>
                                    <button type="button" class="btn btn-sm" x-on:click="modalite = 'texte_picto'"
                                            x-bind:class="modalite === 'texte_picto' ? 'btn-primary' : 'btn-outline-primary'"
                                            x-bind:aria-pressed="modalite === 'texte_picto'">Texte et images</button>
                                </div>

                                {{-- Repli sur le français annoncé, jamais masqué. --}}
                                <div x-cloak x-show="versionManquante"
                                     class="flex items-center rounded border border-warning bg-warning-light p-3.5 text-warning mt-4">
                                    <span>Cette version n'existe pas encore. Voici la version française.</span>
                                </div>

                                <template x-if="realisation && modalite === 'audio'">
                                    <div class="mt-4">
                                        <template x-if="realisation.fichier_audio">
                                            <audio class="w-full" controls preload="none"
                                                   x-bind:src="realisation.fichier_audio"></audio>
                                        </template>

                                        {{-- L'interface reste utilisable quand l'audio manque. --}}
                                        <template x-if="! realisation.fichier_audio">
                                            <div class="flex items-center rounded border border-warning bg-warning-light p-3.5 text-warning">
                                                <span>
                                                    L'enregistrement n'est pas encore disponible.
                                                    Passez à la version texte et images.
                                                </span>
                                            </div>
                                        </template>
                                    </div>
                                </template>

                                <template x-if="realisation && modalite === 'texte_picto'">
                                    <div class="mt-4">
                                        <p class="text-lg" x-text="realisation.contenu_texte"></p>

                                        <ul class="mt-3 flex flex-wrap gap-2">
                                            <template x-for="p in (realisation.pictogrammes || [])" x-bind:key="p">
                                                <li class="chiffre rounded-net bg-jaune-sourd px-2 py-1 text-sm"
                                                    x-text="p"></li>
                                            </template>
                                        </ul>
                                    </div>
                                </template>
                            </div>
                        </template>
                    </div>

                    {{-- La colonne de séance. Chaque séquence occupe une hauteur
                         PROPORTIONNELLE à sa durée officielle : on y descend
                         physiquement au fil de la séance. C'est l'élément
                         signature du projet ; il ne suit pas la grille du
                         template, et c'est délibéré. --}}
                    <div class="panel h-fit">
                        <h5 class="font-semibold text-lg dark:text-white-light mb-5">Le déroulé</h5>

                        <div class="space-y-2">
                            <template x-for="(s, i) in sequences" x-bind:key="s.id">
                                <button type="button" class="bloc-sequence w-full text-left"
                                        x-bind:style="'--minutes: ' + s.duree_minutes"
                                        x-bind:data-etat="etats[s.id]"
                                        x-bind:aria-current="etats[s.id] === 'en_cours' ? 'step' : false"
                                        x-bind:disabled="! demarree"
                                        x-on:click="ouvrir(i)">

                                    <span class="flex items-start justify-between gap-3">
                                        <span class="text-base font-semibold [font-family:var(--font-titre)]">
                                            <span class="chiffre" x-text="s.ordre + '.'"></span>
                                            <span x-text="s.titre"></span>
                                        </span>
                                        <span class="chiffre shrink-0 text-xs" x-text="s.duree_minutes + ' min'"></span>
                                    </span>

                                    {{-- L'état est écrit, jamais porté par la seule couleur. --}}
                                    <span class="intitule text-xs"
                                          x-bind:class="etats[s.id] === 'a_venir' ? 'text-gris-texte' : ''"
                                          x-text="{ passee: 'Terminée', en_cours: 'En cours', a_venir: 'À venir' }[etats[s.id]]"></span>
                                </button>
                            </template>
                        </div>

                        <p class="text-white-dark text-[11px] mt-5">
                            La hauteur de chaque bloc est proportionnelle à sa durée officielle.
                        </p>
                    </div>
                </div>
            </div>
        </template>
    </div>
</x-layouts.kit>
