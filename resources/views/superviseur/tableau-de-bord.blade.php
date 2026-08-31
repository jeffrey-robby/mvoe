@php
    $cartes = [
        ['cle' => 'facilitateurs_actifs', 'titre' => 'Facilitateurs actifs', 'ton' => 'bg-primary-light text-primary dark:bg-primary dark:text-primary-light',
         'trace' => 'M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2M9 7a4 4 0 1 0 0 8 4 4 0 0 0 0-8zM22 21v-2a4 4 0 0 0-3-3.87M16 3.13a4 4 0 0 1 0 7.75'],
        ['cle' => 'cohortes', 'titre' => 'Cohortes', 'ton' => 'bg-success-light text-success dark:bg-success dark:text-success-light',
         'trace' => 'M3 7h18M3 12h18M3 17h18'],
        ['cle' => 'parents_inscrits', 'titre' => 'Parents inscrits', 'ton' => 'bg-info-light text-info dark:bg-info dark:text-info-light',
         'trace' => 'M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2M12 3a4 4 0 1 0 0 8 4 4 0 0 0 0-8z'],
        ['cle' => 'seances_tenues', 'titre' => 'Séances tenues', 'ton' => 'bg-secondary-light text-secondary dark:bg-secondary dark:text-secondary-light',
         'trace' => 'M8 2v4M16 2v4M3 10h18M5 4h14a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V6a2 2 0 0 1 2-2z'],
    ];

    $terrain = [
        ['cle' => 'activites', 'titre' => 'Activités', 'ton' => 'bg-warning-light text-warning dark:bg-warning dark:text-warning-light',
         'trace' => 'M12 2l3 7h7l-5.5 4.5L18 21l-6-4-6 4 1.5-7.5L2 9h7z'],
        ['cle' => 'parents_touches', 'titre' => 'Personnes touchées', 'ton' => 'bg-info-light text-info dark:bg-info dark:text-info-light',
         'trace' => 'M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2M9 3a4 4 0 1 0 0 8 4 4 0 0 0 0-8zM23 21v-2a4 4 0 0 0-3-3.87'],
        ['cle' => 'foyers_suivis', 'titre' => 'Foyers suivis', 'ton' => 'bg-success-light text-success dark:bg-success dark:text-success-light',
         'trace' => 'M3 10l9-7 9 7v10a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z M9 22V12h6v10'],
        ['cle' => 'groupes_actifs', 'titre' => 'Groupes de soutien', 'ton' => 'bg-secondary-light text-secondary dark:bg-secondary dark:text-secondary-light',
         'trace' => 'M12 2a5 5 0 0 1 5 5v3a5 5 0 0 1-10 0V7a5 5 0 0 1 5-5zM4 21a8 8 0 0 1 16 0'],
    ];
@endphp

<x-layouts.delegation titre="Tableau de bord">

    <div x-data="tableauDeBord" x-cloak>

        <ul class="flex space-x-2 rtl:space-x-reverse" x-show="fil.length > 1">
            <template x-for="(maillon, rang) in fil" x-bind:key="rang">
                <li class="flex items-center gap-2">
                    <span x-show="rang > 0" class="text-white-dark">/</span>
                    <a href="javascript:;" x-show="rang < fil.length - 1"
                       class="text-primary hover:underline"
                       x-on:click="revenir(maillon)" x-text="maillon.libelle"></a>
                    <span x-show="rang === fil.length - 1" x-text="maillon.libelle"></span>
                </li>
            </template>
        </ul>

        <div class="pt-5">

            <div class="flex flex-wrap items-end justify-between gap-3 mb-6">
                <div>
                    <h2 class="text-2xl font-bold dark:text-white-light"
                        x-text="portee?.libelle ?? 'Tableau de bord'">Tableau de bord</h2>
                    <p class="text-white-dark mt-1">
                        <span x-text="{
                            national: 'Programme national',
                            region: 'Délégation régionale',
                            departement: 'Délégation départementale',
                            arrondissement: 'Délégation d\'arrondissement',
                            facilitateur: 'Facilitateur',
                        }[portee?.niveau] ?? ''"></span>
                        <span x-show="portee?.arrondissements">
                            · <span class="chiffre" x-text="portee?.arrondissements"></span>
                            <span x-text="portee?.arrondissements > 1 ? 'arrondissements' : 'arrondissement'"></span>
                        </span>
                    </p>
                </div>

                <a href="/superviseur" class="btn btn-outline-primary sans-impression">Ouvrir le registre</a>
            </div>

            <div x-show="erreur" class="panel border-l-4 border-warning mb-6">
                <p x-text="erreur"></p>
            </div>

            <div x-show="chargement && ! indicateurs" class="panel">
                <p class="text-white-dark">Chargement…</p>
            </div>

            <template x-if="indicateurs">
                <div class="relative"
                     x-bind:aria-busy="chargement"
                     x-bind:class="chargement ? 'pointer-events-none opacity-40' : ''">

                    <p x-show="chargement" class="absolute -top-6 right-0 text-sm text-white-dark">Chargement…</p>

                    <div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-4 gap-6 mb-6">
                        @foreach ($cartes as $c)
                            <div class="panel h-full">
                                <div class="flex items-start justify-between">
                                    <div class="w-11 h-11 rounded-lg flex items-center justify-center {{ $c['ton'] }}">
                                        <svg width="22" height="22" viewBox="0 0 24 24" fill="none"
                                             stroke="currentColor" stroke-width="1.6" stroke-linecap="round"
                                             stroke-linejoin="round">
                                            <path d="{{ $c['trace'] }}" />
                                        </svg>
                                    </div>
                                    <h5 class="chiffre text-3xl font-bold ltr:text-right rtl:text-left dark:text-white-light"
                                        x-text="indicateurs.{{ $c['cle'] }}"></h5>
                                </div>
                                <div class="mt-4">
                                    <p class="text-base font-semibold dark:text-white-light">{{ $c['titre'] }}</p>

                                    @if ($c['cle'] === 'facilitateurs_actifs')
                                        <p class="text-xs text-white-dark mt-0.5">
                                            sur <span class="chiffre" x-text="indicateurs.facilitateurs_formes"></span> formés
                                        </p>
                                        <div class="w-full rounded-full h-2 bg-dark-light dark:bg-[#1b2e4b] shadow mt-3">
                                            <div class="bg-gradient-to-r from-[#4361ee] to-[#805dca] h-full rounded-full"
                                                 x-bind:style="'width: ' + (indicateurs.facilitateurs_formes
                                                     ? Math.round(100 * indicateurs.facilitateurs_actifs / indicateurs.facilitateurs_formes)
                                                     : 0) + '%'"></div>
                                        </div>
                                    @endif
                                </div>
                            </div>
                        @endforeach
                    </div>

                    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-6">
                        <div class="panel h-full">
                            <h5 class="chiffre text-3xl font-bold dark:text-white-light"
                                x-text="nombre(indicateurs.dose_moyenne_par_parent)"></h5>
                            <p class="text-base font-semibold mt-2 dark:text-white-light">
                                séances reçues par parent inscrit
                            </p>
                            <p class="text-white-dark mt-1">
                                Un parent rattrapé par son binôme a reçu la séance : il compte.
                            </p>
                        </div>

                        <div class="panel h-full">
                            <h5 class="chiffre text-3xl font-bold"
                                x-bind:class="indicateurs.ecarts_releves > 0 ? 'text-warning' : 'dark:text-white-light'"
                                x-text="indicateurs.ecarts_releves"></h5>
                            <p class="text-base font-semibold mt-2 dark:text-white-light">
                                écarts entre le déclaré et l'observé
                            </p>
                            <p class="text-white-dark mt-1">
                                Un écart n'est pas une faute : il montre un endroit du déroulé qui résiste.
                            </p>
                        </div>

                        <div class="panel h-full">
                            <h5 class="chiffre text-3xl font-bold dark:text-white-light"
                                x-text="nombre(indicateurs.delai_moyen_remontee_jours)"></h5>
                            <p class="text-base font-semibold mt-2 dark:text-white-light">
                                jours entre la séance et sa remontée
                            </p>
                            <p class="text-white-dark mt-1">
                                C'est la chaîne d'information elle-même qui se mesure ici.
                            </p>
                        </div>
                    </div>

                    <div class="panel mb-6" x-show="indicateurs.facilitateurs_jamais_actifs > 0">
                        <p class="text-white-dark">
                            <span class="chiffre font-semibold text-black dark:text-white-light"
                                  x-text="indicateurs.facilitateurs_jamais_actifs"></span>
                            facilitateurs formés n'ont jamais tenu de séance. Un facilitateur est compté
                            inactif après <span class="chiffre" x-text="seuilInactivite"></span> jours sans activité.
                        </p>
                    </div>

                    <div class="flex items-center justify-between mb-5">
                        <h5 class="font-semibold text-lg dark:text-white-light">Le terrain</h5>
                        <span class="text-white-dark text-xs">
                            Causeries, ateliers, porte-à-porte, visites, réunions de groupe.
                        </span>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-4 gap-6 mb-6">
                        @foreach ($terrain as $c)
                            <div class="panel h-full">
                                <div class="flex items-start justify-between">
                                    <div class="w-11 h-11 rounded-lg flex items-center justify-center {{ $c['ton'] }}">
                                        <svg width="22" height="22" viewBox="0 0 24 24" fill="none"
                                             stroke="currentColor" stroke-width="1.6" stroke-linecap="round"
                                             stroke-linejoin="round">
                                            <path d="{{ $c['trace'] }}" />
                                        </svg>
                                    </div>
                                    <h5 class="chiffre text-3xl font-bold ltr:text-right rtl:text-left dark:text-white-light"
                                        x-text="indicateurs.{{ $c['cle'] }}"></h5>
                                </div>
                                <div class="mt-4">
                                    <p class="text-base font-semibold dark:text-white-light">{{ $c['titre'] }}</p>

                                    @if ($c['cle'] === 'parents_touches')
                                        <p class="text-xs text-white-dark mt-0.5">
                                            <span class="chiffre" x-text="indicateurs.dont_femmes"></span> femmes ·
                                            <span class="chiffre" x-text="indicateurs.dont_hommes"></span> hommes
                                        </p>
                                        <div class="w-full rounded-full h-2 bg-dark-light dark:bg-[#1b2e4b] shadow mt-3 flex overflow-hidden">
                                            <div class="bg-gradient-to-r from-[#7579ff] to-[#b224ef] h-full"
                                                 x-bind:style="'width: ' + (indicateurs.parents_touches
                                                     ? Math.round(100 * indicateurs.dont_femmes / indicateurs.parents_touches)
                                                     : 0) + '%'"></div>
                                        </div>
                                    @endif

                                    @if ($c['cle'] === 'foyers_suivis')
                                        <p class="text-xs text-white-dark mt-0.5">
                                            <span class="chiffre" x-text="indicateurs.foyers_avec_difficulte"></span>
                                            avec une difficulté fonctionnelle
                                        </p>
                                    @endif

                                    @if ($c['cle'] === 'groupes_actifs')
                                        <p class="text-xs text-white-dark mt-0.5">
                                            actifs sur <span class="chiffre" x-text="indicateurs.groupes_soutien"></span>
                                        </p>
                                        <div class="w-full rounded-full h-2 bg-dark-light dark:bg-[#1b2e4b] shadow mt-3">
                                            <div class="bg-gradient-to-r from-[#3cba92] to-[#0ba360] h-full rounded-full"
                                                 x-bind:style="'width: ' + (indicateurs.groupes_soutien
                                                     ? Math.round(100 * indicateurs.groupes_actifs / indicateurs.groupes_soutien)
                                                     : 0) + '%'"></div>
                                        </div>
                                    @endif
                                </div>
                            </div>
                        @endforeach
                    </div>

                    <div class="panel mb-6 space-y-4">
                        <div class="flex items-start gap-3">
                            <span class="chiffre text-2xl font-bold shrink-0 dark:text-white-light"
                                  x-text="indicateurs.participants_handicap"></span>
                            <p class="text-white-dark">
                                <span class="text-black dark:text-white-light">participants en situation de handicap</span>,
                                sur <span class="chiffre" x-text="indicateurs.parents_touches"></span> personnes touchées.
                                Comptés activité par activité, jamais estimés.
                            </p>
                        </div>

                        <div class="flex items-start gap-3" x-show="indicateurs.signalements > 0">
                            <span class="chiffre text-2xl font-bold shrink-0"
                                  x-bind:class="indicateurs.signalements_a_traiter > 0 ? 'text-danger' : 'dark:text-white-light'"
                                  x-text="indicateurs.signalements_a_traiter"></span>
                            <p class="text-white-dark">
                                <span class="text-black dark:text-white-light">signalements attendent d'être traités</span>,
                                sur <span class="chiffre" x-text="indicateurs.signalements"></span> reçus.
                                Aucune autorité n'est prévenue automatiquement.
                            </p>
                        </div>

                        <div class="flex items-start gap-3"
                             x-show="indicateurs.groupes_soutien > indicateurs.groupes_actifs">
                            <span class="chiffre text-2xl font-bold shrink-0 text-warning"
                                  x-text="indicateurs.groupes_soutien - indicateurs.groupes_actifs"></span>
                            <p class="text-white-dark">
                                <span class="text-black dark:text-white-light">groupes de soutien ne se sont pas réunis depuis longtemps.</span>
                                Un groupe sans réunion n'est pas un groupe, c'est une ligne dans un rapport.
                            </p>
                        </div>
                    </div>

                    <template x-if="decoupage">
                        <div class="panel h-full w-full">
                            <div class="flex items-center justify-between mb-5">
                                <h5 class="font-semibold text-lg dark:text-white-light" x-text="decoupage.libelle"></h5>
                                <span class="text-white-dark text-xs" x-show="descendable">
                                    Ouvrez une ligne pour descendre d'un niveau.
                                </span>
                            </div>

                            <div class="table-responsive">
                                <table>
                                    <thead>
                                        <tr>
                                            <th class="ltr:rounded-l-md rtl:rounded-r-md"
                                                x-text="descendable ? decoupage.libelle : 'Nom'"></th>
                                            <th class="text-right"
                                                x-text="descendable ? 'Facilitateurs' : 'Dernière activité'"></th>
                                            <th class="text-right">Cohortes</th>
                                            <th class="text-right">Parents</th>
                                            <th class="text-right">Séances</th>
                                            <th class="text-right ltr:rounded-r-md rtl:rounded-l-md">Écarts</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <template x-for="ligne in decoupage.lignes" x-bind:key="ligne.id">
                                            <tr class="text-white-dark hover:text-black dark:hover:text-white-light/90 group"
                                                x-bind:class="ouvrable(ligne) ? 'cursor-pointer' : ''"
                                                x-on:click="ouvrir(ligne)">
                                                <td class="min-w-[180px] text-black dark:text-white">
                                                    <span class="font-semibold whitespace-nowrap"
                                                          x-bind:class="ouvrable(ligne) ? 'text-primary' : ''"
                                                          x-text="ligne.libelle"></span>
                                                    <span x-show="ligne.peuplee === false"
                                                          class="badge badge-neutre ltr:ml-2 rtl:mr-2">Pas encore déployée</span>
                                                    <span x-show="ligne.actif === false"
                                                          class="badge badge-neutre ltr:ml-2 rtl:mr-2">Inactif</span>
                                                </td>
                                                <td class="chiffre text-right" x-show="descendable">
                                                    <span class="text-black dark:text-white" x-text="ligne.facilitateurs_actifs"></span><span
                                                          x-text="'/' + ligne.facilitateurs_formes"></span>
                                                </td>
                                                <td class="chiffre text-right" x-show="! descendable"
                                                    x-text="ligne.jours_depuis_activite === null
                                                        ? 'jamais'
                                                        : 'il y a ' + ligne.jours_depuis_activite + ' j'"></td>
                                                <td class="chiffre text-right" x-text="ligne.cohortes"></td>
                                                <td class="chiffre text-right" x-text="ligne.parents_inscrits"></td>
                                                <td class="chiffre text-right" x-text="ligne.seances_tenues"></td>
                                                <td class="chiffre text-right"
                                                    x-bind:class="ligne.ecarts_releves > 0 ? 'text-warning font-semibold' : ''"
                                                    x-text="ligne.ecarts_releves"></td>
                                            </tr>
                                        </template>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </template>
                </div>
            </template>
        </div>
    </div>
</x-layouts.delegation>
