<x-layouts.delegation titre="Bibliothèque">

    <div x-data="bibliotheque" x-cloak>

        <ul class="flex space-x-2 rtl:space-x-reverse">
            <li>
                <a href="/superviseur/tableau-de-bord" class="text-primary hover:underline">Tableau de bord</a>
            </li>
            <li class="before:content-['/'] ltr:before:mr-1 rtl:before:ml-1">
                <span>Bibliothèque</span>
            </li>
        </ul>

        <div class="pt-5">

            <div class="mb-6">
                <h2 class="text-2xl font-bold dark:text-white-light">Bibliothèque</h2>
                <p class="text-white-dark mt-1 max-w-prose">
                    Les contenus du programme et les langues dans lesquelles ils existent.
                    Rien ne part sur le terrain avant d'être validé ici.
                </p>
            </div>

            <div x-show="chargement && ! contenusParents" class="panel mb-6">
                <p class="text-white-dark">Chargement…</p>
            </div>

            <div x-show="erreur" class="panel border-l-4 border-warning mb-6">
                <p x-text="erreur"></p>
            </div>

            <template x-if="contenusParents">
                <div>
                    <div class="grid grid-cols-2 xl:grid-cols-4 gap-6 mb-6">
                        <div class="panel h-full">
                            <div class="flex items-start justify-between">
                                <div class="w-11 h-11 rounded-lg flex items-center justify-center bg-primary-light text-primary dark:bg-primary dark:text-primary-light">
                                    <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                         stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round">
                                        <path d="M4 19.5A2.5 2.5 0 0 1 6.5 17H20M6.5 2H20v20H6.5A2.5 2.5 0 0 1 4 19.5v-15A2.5 2.5 0 0 1 6.5 2z" />
                                    </svg>
                                </div>
                                <h5 class="chiffre text-3xl font-bold ltr:text-right rtl:text-left dark:text-white-light"
                                    x-text="contenusParents.unites"></h5>
                            </div>
                            <p class="text-base font-semibold mt-4 dark:text-white-light">Unités parents</p>
                        </div>

                        <div class="panel h-full">
                            <div class="flex items-start justify-between">
                                <div class="w-11 h-11 rounded-lg flex items-center justify-center bg-info-light text-info dark:bg-info dark:text-info-light">
                                    <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                         stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round">
                                        <path d="M9 18V5l12-2v13" />
                                        <circle cx="6" cy="18" r="3" />
                                        <circle cx="18" cy="16" r="3" />
                                    </svg>
                                </div>
                                <h5 class="chiffre text-3xl font-bold ltr:text-right rtl:text-left dark:text-white-light"
                                    x-text="contenusParents.realisations"></h5>
                            </div>
                            <p class="text-base font-semibold mt-4 dark:text-white-light">Réalisations</p>
                            <p class="text-xs text-white-dark mt-0.5">audio, texte + pictogrammes, vidéo</p>
                        </div>

                        <div class="panel h-full">
                            <div class="flex items-start justify-between">
                                <div class="w-11 h-11 rounded-lg flex items-center justify-center bg-secondary-light text-secondary dark:bg-secondary dark:text-secondary-light">
                                    <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                         stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round">
                                        <path d="M22 10L12 5 2 10l10 5 10-5zM6 12v5c0 1.7 2.7 3 6 3s6-1.3 6-3v-5" />
                                    </svg>
                                </div>
                                <h5 class="chiffre text-3xl font-bold ltr:text-right rtl:text-left dark:text-white-light"
                                    x-text="contenusFacilitateurs.modules"></h5>
                            </div>
                            <p class="text-base font-semibold mt-4 dark:text-white-light">Modules facilitateur</p>
                        </div>

                        <div class="panel h-full">
                            <div class="flex items-start justify-between">
                                <div class="w-11 h-11 rounded-lg flex items-center justify-center bg-success-light text-success dark:bg-success dark:text-success-light">
                                    <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                         stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                                        <path d="M20 6L9 17l-5-5" />
                                    </svg>
                                </div>
                                <h5 class="chiffre text-3xl font-bold ltr:text-right rtl:text-left dark:text-white-light"
                                    x-text="contenusFacilitateurs.diffusables"></h5>
                            </div>
                            <p class="text-base font-semibold mt-4 dark:text-white-light">Dont diffusables</p>
                            <div class="w-full rounded-full h-2 bg-dark-light dark:bg-[#1b2e4b] shadow mt-3">
                                <div class="bg-gradient-to-r from-[#3cba92] to-[#0ba360] h-full rounded-full"
                                     x-bind:style="'width: ' + (contenusFacilitateurs.modules
                                         ? Math.round(100 * contenusFacilitateurs.diffusables / contenusFacilitateurs.modules)
                                         : 0) + '%'"></div>
                            </div>
                        </div>
                    </div>

                    <div class="panel mb-6">
                        <div class="flex items-center justify-between mb-5">
                            <h5 class="font-semibold text-lg dark:text-white-light">Ce qui attend votre validation</h5>
                            <span class="badge bg-warning shadow-md" x-show="file.length > 0"
                                  x-text="file.length + (file.length > 1 ? ' contenus' : ' contenu')"></span>
                        </div>

                        <template x-if="file.length === 0">
                            <p class="py-6 text-center text-white-dark">
                                Rien n'attend. Tous les contenus du programme sont validés.
                            </p>
                        </template>

                        <div class="space-y-4">
                            <template x-for="m in file" x-bind:key="m.code">
                                <div class="rounded-md border border-white-light dark:border-[#1b2e4b] p-4">
                                    <div class="flex flex-wrap items-start justify-between gap-2">
                                        <div>
                                            <p class="text-base font-semibold dark:text-white-light" x-text="m.titre"></p>
                                            <p class="text-white-dark text-xs mt-0.5">
                                                <span x-text="m.type_libelle"></span> ·
                                                <span class="chiffre" x-text="m.sections"></span> sections ·
                                                <span class="chiffre" x-text="m.code"></span>
                                            </p>
                                        </div>
                                        <span class="badge bg-warning shadow-md" x-text="m.statut_libelle"></span>
                                    </div>

                                    <p class="text-white-dark mt-3 max-w-prose" x-text="m.objectif"></p>

                                    <div class="flex flex-wrap gap-3 mt-4">
                                        <button type="button" class="btn btn-primary"
                                                x-on:click="valider(m.code, 'valide')" x-bind:disabled="occupe">
                                            Valider et diffuser
                                        </button>
                                        <button type="button" class="btn btn-outline-primary"
                                                x-on:click="valider(m.code, 'brouillon')" x-bind:disabled="occupe">
                                            Renvoyer en brouillon
                                        </button>
                                    </div>
                                </div>
                            </template>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-6">

                        <div class="panel lg:col-span-2">
                            <div class="mb-5">
                                <h5 class="font-semibold text-lg dark:text-white-light">Les langues du programme</h5>
                                <p class="text-white-dark mt-1">
                                    Ajouter une langue ne suffit pas à la rendre disponible : il faut charger
                                    les réalisations correspondantes. Retirer une langue la sort de l'interface
                                    <strong class="text-black dark:text-white-light">sans rien supprimer</strong>.
                                </p>
                            </div>

                            <div class="table-responsive">
                                <table>
                                    <thead>
                                        <tr>
                                            <th class="ltr:rounded-l-md rtl:rounded-r-md">Langue</th>
                                            <th>Code</th>
                                            <th class="text-right">Réalisations</th>
                                            <th>État</th>
                                            <th class="ltr:rounded-r-md rtl:rounded-l-md"></th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <template x-for="l in langues" x-bind:key="l.id">
                                            <tr class="text-white-dark hover:text-black dark:hover:text-white-light/90 group">
                                                <td class="text-black dark:text-white">
                                                    <span class="font-semibold whitespace-nowrap" x-text="l.nom"></span>
                                                    <span class="block text-xs text-white-dark"
                                                          x-show="l.nom !== l.libelle" x-text="l.libelle"></span>
                                                </td>
                                                <td class="chiffre" x-text="l.code"></td>
                                                <td class="chiffre text-right"
                                                    x-bind:class="l.realisations === 0 ? 'text-warning font-semibold' : 'text-black dark:text-white'"
                                                    x-text="l.realisations"></td>
                                                <td>
                                                    <span class="badge shadow-md dark:group-hover:bg-transparent"
                                                          x-bind:class="l.actif ? 'bg-success' : 'bg-slate-400'"
                                                          x-text="l.actif ? 'Active' : 'Retirée'"></span>
                                                </td>
                                                <td class="text-right">
                                                    <button type="button" class="btn btn-outline-primary btn-sm"
                                                            x-on:click="basculerLangue(l)" x-bind:disabled="occupe"
                                                            x-text="l.actif ? 'Retirer' : 'Remettre'"></button>
                                                </td>
                                            </tr>
                                        </template>
                                    </tbody>
                                </table>
                            </div>
                        </div>

                        <div class="panel h-fit">
                            <h5 class="font-semibold text-lg dark:text-white-light mb-5">Enregistrer une langue</h5>

                            <div class="space-y-5">
                                <div>
                                    <label for="code">Code</label>
                                    <input id="code" x-model="nouvelleLangue.code" type="text" maxlength="20"
                                           placeholder="ful" class="form-input chiffre">
                                </div>
                                <div>
                                    <label for="libelle">Nom en français</label>
                                    <input id="libelle" x-model="nouvelleLangue.libelle" type="text" maxlength="80"
                                           placeholder="Fulfulde" class="form-input">
                                </div>
                                <div>
                                    <label for="endonyme">Nom dans la langue</label>
                                    <input id="endonyme" x-model="nouvelleLangue.endonyme" type="text" maxlength="80"
                                           placeholder="Fulfulde" class="form-input">
                                    <span class="text-white-dark text-[11px] inline-block mt-1">
                                        C'est ce nom qui s'affiche au parent : personne ne cherche « Fulfulde »
                                        écrit en français quand il ne lit pas le français.
                                    </span>
                                </div>

                                <button type="button" class="btn btn-primary w-full !mt-6"
                                        x-on:click="ajouterUneLangue()"
                                        x-bind:disabled="! peutAjouterUneLangue || occupe">
                                    Enregistrer la langue
                                </button>
                            </div>
                        </div>
                    </div>

                    <div class="panel">
                        <div class="mb-5">
                            <h5 class="font-semibold text-lg dark:text-white-light">
                                Couverture des unités, langue par langue
                            </h5>
                            <p class="text-white-dark mt-1 max-w-prose">
                                Une unité chargée en français et pas en bulu n'atteint pas les locuteurs bulu,
                                quel que soit le nombre total de réalisations. C'est le seul chiffre qui dise
                                où porter l'effort.
                            </p>
                        </div>

                        <div class="space-y-5">
                            <template x-for="c in contenusParents.couverture" x-bind:key="c.langue">
                                <div>
                                    <div class="flex items-center justify-between mb-2">
                                        <h6 class="font-semibold dark:text-white-light" x-text="c.nom"></h6>
                                        <p class="chiffre text-white-dark">
                                            <span class="text-black dark:text-white" x-text="c.unites_couvertes"></span><span
                                                  x-text="'/' + c.unites_total"></span>
                                            <span class="text-warning font-semibold ltr:ml-2 rtl:mr-2"
                                                  x-show="c.manquantes > 0"
                                                  x-text="c.manquantes + ' manquantes'"></span>
                                        </p>
                                    </div>
                                    <div class="w-full rounded-full h-2 bg-dark-light dark:bg-[#1b2e4b] shadow">
                                        <div class="h-full rounded-full"
                                             x-bind:class="c.manquantes > 0
                                                 ? 'bg-gradient-to-r from-[#f09819] to-[#ff5858]'
                                                 : 'bg-gradient-to-r from-[#3cba92] to-[#0ba360]'"
                                             x-bind:style="'width: ' + (c.unites_total
                                                 ? Math.round(100 * c.unites_couvertes / c.unites_total)
                                                 : 0) + '%'"></div>
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
