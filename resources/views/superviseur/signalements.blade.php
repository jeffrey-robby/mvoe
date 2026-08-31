<x-layouts.delegation titre="Signalements">

    <div x-data="signalements" x-cloak>

        <ul class="flex space-x-2 rtl:space-x-reverse">
            <li>
                <a href="/superviseur/tableau-de-bord" class="text-primary hover:underline">Tableau de bord</a>
            </li>
            <li class="before:content-['/'] ltr:before:mr-1 rtl:before:ml-1">
                <span>Signalements</span>
            </li>
        </ul>

        <div class="pt-5">

            <div class="mb-6">
                <h2 class="text-2xl font-bold dark:text-white-light">Signalements</h2>
                <p class="text-white-dark mt-1 max-w-prose">
                    Aucune autorité n'est prévenue automatiquement. Ces situations vous sont remontées
                    pour que vous en décidiez. Aucune ligne ne porte l'identité d'un enfant, d'un parent
                    ou d'un foyer.
                </p>
            </div>

            <div x-show="chargement && ! synthese" class="panel mb-6">
                <p class="text-white-dark">Chargement…</p>
            </div>

            <div x-show="erreur" class="panel border-l-4 border-warning mb-6">
                <p x-text="erreur"></p>
            </div>

            <template x-if="synthese">
                <div>
                    <div class="grid grid-cols-2 xl:grid-cols-4 gap-6 mb-6">
                        <div class="panel h-full">
                            <div class="flex items-start justify-between">
                                <div class="w-11 h-11 rounded-lg flex items-center justify-center bg-danger-light text-danger dark:bg-danger dark:text-danger-light">
                                    <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                         stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round">
                                        <circle cx="12" cy="12" r="9" />
                                        <path d="M12 7v6M12 16h.01" />
                                    </svg>
                                </div>
                                <h5 class="chiffre text-3xl font-bold ltr:text-right rtl:text-left"
                                    x-bind:class="synthese.a_traiter > 0 ? 'text-danger' : 'dark:text-white-light'"
                                    x-text="synthese.a_traiter"></h5>
                            </div>
                            <p class="text-base font-semibold mt-4 dark:text-white-light">À traiter</p>
                        </div>

                        <div class="panel h-full">
                            <div class="flex items-start justify-between">
                                <div class="w-11 h-11 rounded-lg flex items-center justify-center bg-warning-light text-warning dark:bg-warning dark:text-warning-light">
                                    <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                         stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round">
                                        <path d="M12 4l9 16H3zM12 10v4M12 17h.01" />
                                    </svg>
                                </div>
                                <h5 class="chiffre text-3xl font-bold ltr:text-right rtl:text-left"
                                    x-bind:class="synthese.graves_non_traites > 0 ? 'text-danger' : 'dark:text-white-light'"
                                    x-text="synthese.graves_non_traites"></h5>
                            </div>
                            <p class="text-base font-semibold mt-4 dark:text-white-light">Dont graves</p>
                        </div>

                        <div class="panel h-full">
                            <div class="flex items-start justify-between">
                                <div class="w-11 h-11 rounded-lg flex items-center justify-center bg-primary-light text-primary dark:bg-primary dark:text-primary-light">
                                    <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                         stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round">
                                        <path d="M4 6h16M4 12h16M4 18h10" />
                                    </svg>
                                </div>
                                <h5 class="chiffre text-3xl font-bold ltr:text-right rtl:text-left dark:text-white-light"
                                    x-text="synthese.total"></h5>
                            </div>
                            <p class="text-base font-semibold mt-4 dark:text-white-light">Reçus en tout</p>
                        </div>

                        <div class="panel h-full">
                            <div class="flex items-start justify-between">
                                <div class="w-11 h-11 rounded-lg flex items-center justify-center bg-success-light text-success dark:bg-success dark:text-success-light">
                                    <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                         stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round">
                                        <circle cx="12" cy="12" r="9" />
                                        <path d="M12 7v5l3 2" />
                                    </svg>
                                </div>
                                <h5 class="chiffre text-3xl font-bold ltr:text-right rtl:text-left dark:text-white-light"
                                    x-text="synthese.delai_moyen_traitement_jours ?? '—'"></h5>
                            </div>
                            <p class="text-base font-semibold mt-4 dark:text-white-light">Délai moyen</p>
                            <p class="text-xs text-white-dark mt-0.5">jours entre la remontée et la décision</p>
                        </div>
                    </div>

                    <div class="panel h-full w-full">
                        <div class="flex flex-wrap items-center justify-between gap-3 mb-5">
                            <h5 class="font-semibold text-lg dark:text-white-light">La file</h5>

                            <div class="inline-flex">
                                <button type="button" x-on:click="filtre = 'ouverts'"
                                        x-bind:aria-pressed="filtre === 'ouverts'"
                                        x-bind:class="filtre === 'ouverts' ? 'btn-primary' : 'btn-outline-primary'"
                                        class="btn ltr:rounded-r-none rtl:rounded-l-none">Ce qui attend</button>
                                <button type="button" x-on:click="filtre = 'tous'"
                                        x-bind:aria-pressed="filtre === 'tous'"
                                        x-bind:class="filtre === 'tous' ? 'btn-primary' : 'btn-outline-primary'"
                                        class="btn ltr:rounded-l-none rtl:rounded-r-none">Tout l'historique</button>
                            </div>
                        </div>

                        <template x-if="affiches.length === 0">
                            <p class="py-8 text-center text-white-dark">
                                Rien n'attend dans votre file. Les signalements traités restent
                                consultables dans l'historique.
                            </p>
                        </template>

                        <template x-if="affiches.length > 0">
                            <div class="table-responsive">
                                <table>
                                    <thead>
                                        <tr>
                                            <th class="ltr:rounded-l-md rtl:rounded-r-md">Situation</th>
                                            <th>Gravité</th>
                                            <th>Arrondissement</th>
                                            <th>Signalé par</th>
                                            <th class="text-right">Attente</th>
                                            <th class="ltr:rounded-r-md rtl:rounded-l-md">Statut</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <template x-for="s in affiches" x-bind:key="s.uuid">
                                            <tr class="cursor-pointer text-white-dark hover:text-black dark:hover:text-white-light/90 group"
                                                x-on:click="ouvrir(s)">
                                                <td class="min-w-[170px] text-black dark:text-white">
                                                    <span class="font-semibold text-primary whitespace-nowrap"
                                                          x-text="s.type_libelle"></span>
                                                    <span class="chiffre block text-xs text-white-dark"
                                                          x-text="s.soumis_le.split('-').reverse().join('/')"></span>
                                                </td>
                                                <td>
                                                    <span class="badge shadow-md dark:group-hover:bg-transparent"
                                                          x-bind:class="s.gravite === 'elevee' ? 'bg-danger' : 'bg-slate-400'"
                                                          x-text="s.gravite_libelle"></span>
                                                </td>
                                                <td class="whitespace-nowrap" x-text="s.arrondissement"></td>
                                                <td class="whitespace-nowrap" x-text="s.facilitateur"></td>
                                                <td class="chiffre text-right"
                                                    x-bind:class="s.ouvert && s.jours_attente > 14 ? 'text-danger font-semibold' : ''"
                                                    x-text="s.jours_attente + ' j'"></td>
                                                <td>
                                                    <span class="badge shadow-md dark:group-hover:bg-transparent"
                                                          x-bind:class="s.ouvert ? 'bg-warning' : 'bg-success'"
                                                          x-text="s.statut_libelle"></span>
                                                </td>
                                            </tr>
                                        </template>
                                    </tbody>
                                </table>
                            </div>
                        </template>
                    </div>

                    <div class="fixed inset-0 bg-[black]/60 z-[999] hidden overflow-y-auto"
                         x-bind:class="ouvert && '!block'">
                        <div class="flex items-start justify-center min-h-screen px-4" x-on:click.self="fermer()">
                            <template x-if="ouvert">
                                <div x-transition x-transition.duration.300
                                     class="panel border-0 p-0 rounded-lg overflow-hidden my-8 w-full max-w-xl">

                                    <div class="flex bg-[#fbfbfb] dark:bg-[#121c2c] items-center justify-between px-5 py-3">
                                        <div class="font-bold text-lg" x-text="ouvert.type_libelle"></div>
                                        <button type="button" class="text-white-dark hover:text-dark" x-on:click="fermer()">
                                            <svg width="24" height="24" viewBox="0 0 24 24" fill="none"
                                                 stroke="currentColor" stroke-width="1.5" stroke-linecap="round"
                                                 class="w-6 h-6">
                                                <line x1="18" y1="6" x2="6" y2="18" />
                                                <line x1="6" y1="6" x2="18" y2="18" />
                                            </svg>
                                        </button>
                                    </div>

                                    <div class="p-5">
                                        <p class="text-white-dark">
                                            Gravité <span x-text="ouvert.gravite_libelle.toLowerCase()"></span>
                                            · <span x-text="ouvert.arrondissement"></span>
                                            · signalé par <span x-text="ouvert.facilitateur"></span>,
                                            il y a <span class="chiffre" x-text="ouvert.jours_attente"></span> jours.
                                        </p>

                                        <div class="mt-5">
                                            <label>Décision</label>
                                            <div class="inline-flex flex-wrap">
                                                <template x-for="(s, rang) in [
                                                    { valeur: 'examine', libelle: 'Examiné' },
                                                    { valeur: 'oriente', libelle: 'Orienté' },
                                                    { valeur: 'clos', libelle: 'Clos' },
                                                ]" x-bind:key="s.valeur">
                                                    <button type="button" x-on:click="statut = s.valeur"
                                                            x-bind:aria-pressed="statut === s.valeur"
                                                            x-bind:class="[
                                                                statut === s.valeur ? 'btn-primary' : 'btn-outline-primary',
                                                                rang === 0 ? 'ltr:rounded-r-none rtl:rounded-l-none' : '',
                                                                rang === 1 ? 'rounded-none' : '',
                                                                rang === 2 ? 'ltr:rounded-l-none rtl:rounded-r-none' : '',
                                                            ]"
                                                            class="btn" x-text="s.libelle"></button>
                                                </template>
                                            </div>
                                        </div>

                                        <div class="mt-5">
                                            <label for="suite">
                                                Suite donnée
                                                <span class="text-white-dark font-normal" x-show="! suiteRequise">— facultative à ce stade</span>
                                            </label>
                                            <textarea id="suite" x-model="suite" rows="3" class="form-textarea"
                                                      placeholder="Ce que le facilitateur lira."></textarea>
                                            <span class="text-white-dark text-[11px] inline-block mt-1">
                                                Le facilitateur verra ce texte. Un signalement sans retour est un
                                                signalement qu'il ne refera pas.
                                            </span>
                                        </div>

                                        <div class="flex justify-end items-center mt-8">
                                            <button type="button" class="btn btn-outline-danger" x-on:click="fermer()">
                                                Annuler
                                            </button>
                                            <button type="button" class="btn btn-primary ltr:ml-4 rtl:mr-4"
                                                    x-on:click="traiter()"
                                                    x-bind:disabled="! peutValider || occupe">
                                                <span x-text="occupe ? 'Un instant…' : 'Enregistrer la décision'">Enregistrer la décision</span>
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </template>
                        </div>
                    </div>
                </div>
            </template>
        </div>
    </div>
</x-layouts.delegation>
