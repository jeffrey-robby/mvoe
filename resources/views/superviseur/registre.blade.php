<x-layouts.delegation titre="Registre">

    <div x-data="registre" x-cloak>

        <ul class="flex space-x-2 rtl:space-x-reverse">
            <li>
                <a href="/superviseur/tableau-de-bord" class="text-primary hover:underline">Tableau de bord</a>
            </li>
            <li class="before:content-['/'] before:mr-1 rtl:before:ml-1">
                <span>Registre</span>
            </li>
        </ul>

        <div class="pt-5">

            <div class="flex flex-wrap items-end justify-between gap-4 mb-6">
                <div>
                    <h2 class="text-2xl font-bold dark:text-white-light">Registre des facilitateurs</h2>
                    <p class="text-white-dark mt-1">
                        <span x-text="portee?.libelle"></span>
                        <span x-show="portee?.arrondissements">
                            · <span class="chiffre" x-text="portee?.arrondissements"></span>
                            <span x-text="portee?.arrondissements > 1 ? 'arrondissements' : 'arrondissement'"></span>
                        </span>
                        <span x-show="portee && ! portee?.arrondissements">· national</span>
                    </p>
                </div>

                <a href="/superviseur/enregistrer" class="btn btn-primary sans-impression">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                         stroke-width="1.8" stroke-linecap="round" class="ltr:mr-2 rtl:ml-2">
                        <path d="M12 5v14M5 12h14" />
                    </svg>
                    Enregistrer un facilitateur
                </a>
            </div>

            <div x-show="chargement" class="panel mb-6">
                <p class="text-white-dark">Chargement…</p>
            </div>

            <div x-show="erreur" class="panel border-l-4 border-danger mb-6">
                <p x-text="erreur"></p>
            </div>

            <template x-if="synthese">
                <div>
                    <div class="grid grid-cols-2 xl:grid-cols-4 gap-6 mb-6">
                        <div class="panel h-full">
                            <div class="flex items-start justify-between">
                                <div class="w-11 h-11 rounded-lg flex items-center justify-center bg-primary-light text-primary dark:bg-primary dark:text-primary-light">
                                    <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                         stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round">
                                        <path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2M9 7a4 4 0 1 0 0 8 4 4 0 0 0 0-8z" />
                                    </svg>
                                </div>
                                <h5 class="chiffre text-3xl font-bold ltr:text-right rtl:text-left dark:text-white-light"
                                    x-text="synthese.formes"></h5>
                            </div>
                            <p class="text-base font-semibold mt-4 dark:text-white-light">Formés</p>
                        </div>

                        <div class="panel h-full">
                            <div class="flex items-start justify-between">
                                <div class="w-11 h-11 rounded-lg flex items-center justify-center bg-success-light text-success dark:bg-success dark:text-success-light">
                                    <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                         stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                                        <path d="M20 6L9 17l-5-5" />
                                    </svg>
                                </div>
                                <h5 class="chiffre text-3xl font-bold text-success ltr:text-right rtl:text-left"
                                    x-text="synthese.actifs"></h5>
                            </div>
                            <p class="text-base font-semibold mt-4 dark:text-white-light">Actifs</p>
                            <div class="w-full rounded-full h-2 bg-dark-light dark:bg-[#1b2e4b] shadow mt-3">
                                <div class="bg-gradient-to-r from-[#3cba92] to-[#0ba360] h-full rounded-full"
                                     x-bind:style="'width: ' + (synthese.formes
                                         ? Math.round(100 * synthese.actifs / synthese.formes) : 0) + '%'"></div>
                            </div>
                        </div>

                        <div class="panel h-full">
                            <div class="flex items-start justify-between">
                                <div class="w-11 h-11 rounded-lg flex items-center justify-center bg-warning-light text-warning dark:bg-warning dark:text-warning-light">
                                    <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                         stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round">
                                        <circle cx="12" cy="12" r="9" />
                                        <path d="M12 7v5l3 2" />
                                    </svg>
                                </div>
                                <h5 class="chiffre text-3xl font-bold text-warning ltr:text-right rtl:text-left"
                                    x-text="synthese.inactifs"></h5>
                            </div>
                            <p class="text-base font-semibold mt-4 dark:text-white-light">Inactifs</p>
                        </div>

                        <div class="panel h-full">
                            <div class="flex items-start justify-between">
                                <div class="w-11 h-11 rounded-lg flex items-center justify-center bg-danger-light text-danger dark:bg-danger dark:text-danger-light">
                                    <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                         stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                                        <path d="M18 6L6 18M6 6l12 12" />
                                    </svg>
                                </div>
                                <h5 class="chiffre text-3xl font-bold text-danger ltr:text-right rtl:text-left"
                                    x-text="synthese.jamais_actifs"></h5>
                            </div>
                            <p class="text-base font-semibold mt-4 dark:text-white-light">Jamais actifs</p>
                            <p class="text-xs text-white-dark mt-0.5">compris dans les inactifs</p>
                        </div>
                    </div>

                    <div class="panel h-full w-full">
                        <div class="flex flex-wrap items-end justify-between gap-4 mb-5">
                            <p class="text-white-dark max-w-prose">
                                Est considéré comme inactif un facilitateur sans séance remontée depuis plus de
                                <span class="chiffre" x-text="synthese.seuil_inactivite_jours"></span> jours.
                                Ce seuil se règle dans la configuration, pas dans le code.
                            </p>

                            <div class="sans-impression">
                                <label for="arr" class="text-xs font-semibold uppercase tracking-wider text-white-dark">Arrondissement</label>
                                <select id="arr" x-model="arrondissement" class="form-select w-64 mt-1">
                                    <option value="">Tous ceux de ma portée</option>
                                    <template x-for="a in arrondissements" x-bind:key="a">
                                        <option x-bind:value="a" x-text="a"></option>
                                    </template>
                                </select>
                            </div>
                        </div>

                        <div class="table-responsive">
                            <table>
                                <thead>
                                    <tr>
                                        <th class="ltr:rounded-l-md rtl:rounded-r-md">Facilitateur</th>
                                        <th>Arrondissement</th>
                                        <th>Formé le</th>
                                        <th>Dernière activité</th>
                                        <th class="text-right">Séances</th>
                                        <th class="text-right">Formation</th>
                                        <th class="ltr:rounded-r-md rtl:rounded-l-md">Statut</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <template x-for="f in liste" x-bind:key="f.id">
                                        <tr class="text-white-dark hover:text-black dark:hover:text-white-light/90 group">
                                            <td class="min-w-[190px] text-black dark:text-white">
                                                <span class="font-semibold whitespace-nowrap" x-text="f.nom"></span>
                                                <span class="chiffre block text-xs text-white-dark" x-text="f.telephone"></span>
                                                <span class="block text-xs text-white-dark" x-text="f.type_juridique"></span>
                                            </td>
                                            <td class="min-w-[140px]">
                                                <span class="text-black dark:text-white whitespace-nowrap" x-text="f.arrondissement"></span>
                                                <span class="block text-xs" x-text="f.departement"></span>
                                            </td>
                                            <td class="chiffre whitespace-nowrap"
                                                x-text="f.date_formation_initiale.split('-').reverse().join('/')"></td>
                                            <td class="chiffre whitespace-nowrap">
                                                <span class="text-black dark:text-white" x-text="derniereActivite(f)"></span>
                                                <template x-if="f.jours_depuis_activite !== null">
                                                    <span class="block text-xs" x-text="'il y a ' + f.jours_depuis_activite + ' j'"></span>
                                                </template>
                                            </td>
                                            <td class="chiffre text-right text-black dark:text-white" x-text="f.seances_animees"></td>
                                            <td class="chiffre text-right whitespace-nowrap">
                                                <span class="text-black dark:text-white" x-text="f.modules_termines"></span><span
                                                      x-text="'/' + synthese.modules_diffusables"></span>
                                                <span class="block text-xs" x-show="f.modules_ouverts > f.modules_termines">
                                                    <span x-text="f.modules_ouverts - f.modules_termines"></span> en cours
                                                </span>
                                            </td>
                                            <td>
                                                <span class="badge shadow-md dark:group-hover:bg-transparent"
                                                      x-bind:class="f.actif ? 'bg-success' : 'bg-slate-400'"
                                                      x-text="f.actif ? 'Actif' : 'Inactif'"></span>
                                            </td>
                                        </tr>
                                    </template>
                                </tbody>
                            </table>
                        </div>

                        <template x-if="liste.length === 0">
                            <p class="py-8 text-center text-white-dark">
                                Aucun facilitateur dans cet arrondissement.
                            </p>
                        </template>
                    </div>
                </div>
            </template>
        </div>
    </div>
</x-layouts.delegation>
