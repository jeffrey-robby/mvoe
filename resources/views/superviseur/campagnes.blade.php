<x-layouts.delegation titre="Campagnes">

    <div x-data="campagnes" x-cloak>

        <ul class="flex space-x-2 rtl:space-x-reverse">
            <li>
                <a href="/superviseur/tableau-de-bord" class="text-primary hover:underline">Tableau de bord</a>
            </li>
            <li class="before:content-['/'] ltr:before:mr-1 rtl:before:ml-1">
                <span>Campagnes</span>
            </li>
        </ul>

        <div class="pt-5">

            <div class="flex flex-wrap items-end justify-between gap-3 mb-6">
                <div>
                    <h2 class="text-2xl font-bold dark:text-white-light">Campagnes</h2>
                    <p class="text-white-dark mt-1 max-w-prose">
                        Une campagne pousse des modules, dans des langues, sur des territoires.
                        Elle ne remplace pas les séances : elle les accompagne.
                    </p>
                </div>

                <button type="button" class="btn btn-primary" x-show="peutCreer"
                        x-on:click="formulaireOuvert = true">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                         stroke-width="1.8" stroke-linecap="round" class="ltr:mr-2 rtl:ml-2">
                        <path d="M12 5v14M5 12h14" />
                    </svg>
                    Créer une campagne
                </button>
            </div>

            <div x-show="chargement && campagnes.length === 0" class="panel mb-6">
                <p class="text-white-dark">Chargement…</p>
            </div>

            <div x-show="erreur" class="panel border-l-4 border-warning mb-6">
                <p x-text="erreur"></p>
            </div>

            <template x-if="! chargement && campagnes.length === 0">
                <div class="panel mb-6">
                    <p class="text-white-dark" x-show="peutCreer">
                        Aucune campagne. Créez-en une pour pousser un module sur une région.
                    </p>
                    <p class="text-white-dark" x-show="! peutCreer">
                        Aucune campagne ne concerne votre territoire pour l'instant.
                    </p>
                </div>
            </template>

            <div class="space-y-6">
                <template x-for="c in campagnes" x-bind:key="c.id">
                    <div class="panel">

                        <div class="flex flex-wrap items-start justify-between gap-3 mb-5">
                            <div>
                                <h5 class="font-semibold text-lg dark:text-white-light" x-text="c.titre"></h5>
                                <p class="chiffre text-white-dark text-xs mt-1">
                                    <span x-text="c.date_debut.split('-').reverse().join('/')"></span>
                                    <span class="[font-family:inherit]"> au </span>
                                    <span x-text="c.date_fin.split('-').reverse().join('/')"></span>
                                </p>
                            </div>
                            <span class="badge shadow-md"
                                  x-bind:class="c.statut === 'declenchee' ? 'bg-success' : 'bg-slate-400'"
                                  x-text="c.statut_libelle"></span>
                        </div>

                        <p class="text-white-dark max-w-prose" x-show="c.objet" x-text="c.objet"></p>

                        <div class="grid grid-cols-1 sm:grid-cols-3 gap-5 mt-5">
                            <div>
                                <p class="text-xs font-semibold uppercase tracking-wider text-white-dark mb-2">Modules</p>
                                <div class="flex flex-wrap gap-1.5">
                                    <template x-for="m in c.modules" x-bind:key="m">
                                        <span class="badge badge-outline-primary" x-text="m"></span>
                                    </template>
                                </div>
                            </div>
                            <div>
                                <p class="text-xs font-semibold uppercase tracking-wider text-white-dark mb-2">Langues</p>
                                <div class="flex flex-wrap gap-1.5">
                                    <template x-for="l in c.langues" x-bind:key="l">
                                        <span class="badge badge-outline-info" x-text="l"></span>
                                    </template>
                                </div>
                            </div>
                            <div>
                                <p class="text-xs font-semibold uppercase tracking-wider text-white-dark mb-2">Régions</p>
                                <div class="flex flex-wrap gap-1.5">
                                    <template x-for="r in c.regions" x-bind:key="r">
                                        <span class="badge badge-outline-secondary" x-text="r"></span>
                                    </template>
                                </div>
                            </div>
                        </div>

                        <div class="mt-6 pt-5 border-t border-white-light dark:border-[#1b2e4b]">
                            <p class="text-xs font-semibold uppercase tracking-wider text-white-dark mb-3">
                                Qui a pris connaissance
                            </p>

                            <div class="grid grid-cols-2 lg:grid-cols-4 gap-5">
                                <template x-for="n in c.avancement" x-bind:key="n.niveau">
                                    <div>
                                        <div class="flex items-center justify-between mb-2">
                                            <h6 class="text-xs font-semibold dark:text-white-light" x-text="{
                                                region: 'Régions',
                                                departement: 'Départements',
                                                arrondissement: 'Arrondissements',
                                                facilitateur: 'Facilitateurs',
                                            }[n.niveau]"></h6>
                                            <p class="chiffre text-white-dark text-xs">
                                                <span class="text-black dark:text-white" x-text="n.recues"></span><span
                                                      x-text="'/' + n.affectees"></span>
                                            </p>
                                        </div>
                                        <div class="w-full rounded-full h-2 bg-dark-light dark:bg-[#1b2e4b] shadow">
                                            <div class="bg-gradient-to-r from-[#4361ee] to-[#805dca] h-full rounded-full"
                                                 x-bind:style="'width: ' + (n.affectees
                                                     ? Math.round(100 * n.recues / n.affectees) : 0) + '%'"></div>
                                        </div>
                                    </div>
                                </template>
                            </div>

                            <p class="text-white-dark text-[11px] mt-4 max-w-prose">
                                <span class="chiffre" x-text="recues(c)"></span> échelons sur
                                <span class="chiffre" x-text="affectees(c)"></span> ont ouvert la campagne.
                                Ce n'est pas un taux d'exécution du programme : c'est le nombre de gens qui
                                savent qu'elle existe.
                            </p>
                        </div>

                        <button type="button" class="btn btn-primary mt-5" x-show="! peutCreer"
                                x-on:click="accuser(c)" x-bind:disabled="occupe">
                            J'ai pris connaissance
                        </button>
                    </div>
                </template>
            </div>

            <div class="fixed inset-0 bg-[black]/60 z-[999] hidden overflow-y-auto"
                 x-bind:class="formulaireOuvert && '!block'">
                <div class="flex items-start justify-center min-h-screen px-4" x-on:click.self="fermerLeFormulaire()">
                    <template x-if="formulaireOuvert">
                        <div x-transition x-transition.duration.300
                             class="panel border-0 p-0 rounded-lg overflow-hidden my-8 w-full max-w-2xl">

                            <div class="flex bg-[#fbfbfb] dark:bg-[#121c2c] items-center justify-between px-5 py-3">
                                <div class="font-bold text-lg">Nouvelle campagne</div>
                                <button type="button" class="text-white-dark hover:text-dark"
                                        x-on:click="fermerLeFormulaire()">
                                    <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                         stroke-width="1.5" stroke-linecap="round" class="w-6 h-6">
                                        <line x1="18" y1="6" x2="6" y2="18" />
                                        <line x1="6" y1="6" x2="18" y2="18" />
                                    </svg>
                                </button>
                            </div>

                            <div class="p-5 space-y-5">
                                <div>
                                    <label for="titre">Titre</label>
                                    <input id="titre" x-model="titre" type="text" maxlength="160"
                                           placeholder="Rentrée scolaire — discipline positive" class="form-input">
                                </div>

                                <div>
                                    <label for="objet">
                                        Pourquoi maintenant
                                        <span class="text-white-dark font-normal">— facultatif</span>
                                    </label>
                                    <textarea id="objet" x-model="objet" rows="2" maxlength="1000"
                                              class="form-textarea"></textarea>
                                </div>

                                <div>
                                    <label>Modules à pousser</label>
                                    <div class="flex flex-wrap gap-2">
                                        <template x-for="m in modules" x-bind:key="m.id">
                                            <button type="button" class="btn btn-sm"
                                                    x-on:click="basculer('moduleIds', m.id)"
                                                    x-bind:aria-pressed="moduleIds.includes(m.id)"
                                                    x-bind:class="moduleIds.includes(m.id) ? 'btn-primary' : 'btn-outline-primary'"
                                                    x-text="m.titre"></button>
                                        </template>
                                    </div>
                                    <span class="text-white-dark text-[11px] inline-block mt-1">
                                        Seuls les modules validés sont proposés : on ne lance pas une campagne
                                        sur un brouillon.
                                    </span>
                                </div>

                                <div>
                                    <label>Langues</label>
                                    <div class="flex flex-wrap gap-2">
                                        <template x-for="l in langues" x-bind:key="l.id">
                                            <button type="button" class="btn btn-sm"
                                                    x-on:click="basculer('langueIds', l.id)"
                                                    x-bind:aria-pressed="langueIds.includes(l.id)"
                                                    x-bind:class="langueIds.includes(l.id) ? 'btn-primary' : 'btn-outline-primary'"
                                                    x-text="l.nom"></button>
                                        </template>
                                    </div>
                                </div>

                                <div>
                                    <label>Régions</label>
                                    <div class="flex flex-wrap gap-2">
                                        <template x-for="r in regions" x-bind:key="r.id">
                                            <button type="button" class="btn btn-sm"
                                                    x-on:click="basculer('regionIds', r.id)"
                                                    x-bind:aria-pressed="regionIds.includes(r.id)"
                                                    x-bind:class="regionIds.includes(r.id) ? 'btn-primary' : 'btn-outline-primary'">
                                                <span x-text="r.libelle"></span>
                                                <span class="text-[10px] ltr:ml-1 rtl:mr-1" x-show="! r.peuplee">
                                                    · pas déployée
                                                </span>
                                            </button>
                                        </template>
                                    </div>
                                    <span class="text-white-dark text-[11px] inline-block mt-1">
                                        Les neuf régions non déployées restent proposées : le système est
                                        national par construction. Une campagne y sera enregistrée sans
                                        destinataire, et l'avancement le dira.
                                    </span>
                                </div>

                                <div class="grid grid-cols-1 sm:grid-cols-2 gap-5">
                                    <div>
                                        <label for="debut">Du</label>
                                        <input id="debut" x-model="dateDebut" type="date" class="form-input chiffre">
                                    </div>
                                    <div>
                                        <label for="fin">Au</label>
                                        <input id="fin" x-model="dateFin" type="date" class="form-input chiffre">
                                    </div>
                                </div>

                                <div class="flex justify-end items-center mt-8">
                                    <button type="button" class="btn btn-outline-danger"
                                            x-on:click="fermerLeFormulaire()">Annuler</button>
                                    <button type="button" class="btn btn-primary ltr:ml-4 rtl:mr-4"
                                            x-on:click="creer()" x-bind:disabled="! peutValider || occupe">
                                        <span x-text="occupe ? 'Un instant…' : 'Déclencher la campagne'">Déclencher la campagne</span>
                                    </button>
                                </div>
                            </div>
                        </div>
                    </template>
                </div>
            </div>
        </div>
    </div>
</x-layouts.delegation>
