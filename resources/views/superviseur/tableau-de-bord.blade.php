{{--
    Le tableau de bord. Un seul, pour les cinq niveaux.

    Cet écran ne sait pas à quel niveau il se trouve, et n'a pas à le savoir :
    le serveur lui rend toujours la même forme — une portée, des indicateurs, et
    le découpage du niveau en dessous. Le ministère y lit dix régions, une
    délégation régionale ses quatre départements, un superviseur ses
    facilitateurs. C'est le même gabarit, le même code, la même requête.
--}}
<x-layouts.delegation titre="Tableau de bord">

    <div x-data="tableauDeBord" x-cloak class="space-y-6">

        {{-- Le fil d'Ariane. Il commence à la portée du compte : au-dessus, il
             n'a rien à voir, et un lien mort serait pire qu'aucun lien. --}}
        <nav x-show="fil.length > 1" class="flex flex-wrap items-center gap-1 text-sm"
             aria-label="Fil d'Ariane">
            <template x-for="(maillon, rang) in fil" x-bind:key="rang">
                <span class="flex items-center gap-1">
                    <button type="button" x-show="rang < fil.length - 1"
                            x-on:click="revenir(maillon)"
                            class="rounded px-1 text-primary underline underline-offset-2"
                            x-text="maillon.libelle"></button>

                    <span x-show="rang === fil.length - 1" class="px-1 font-semibold"
                          x-text="maillon.libelle"></span>

                    <span x-show="rang < fil.length - 1" class="text-white-dark">›</span>
                </span>
            </template>
        </nav>

        <div class="flex flex-wrap items-end justify-between gap-3">
            <div>
                <h1 class="text-3xl" x-text="portee?.libelle ?? 'Tableau de bord'">Tableau de bord</h1>
                <p class="mt-1 text-white-dark">
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

            <a href="/superviseur" class="btn btn-neutre sans-impression">Ouvrir le registre</a>
        </div>

        <p x-show="erreur" x-text="erreur" class="panel border-l-4 border-warning"></p>

        {{-- Au tout premier chargement il n'y a rien à estomper : on annonce. --}}
        <p x-show="chargement && ! indicateurs" class="text-white-dark">Chargement…</p>

        {{-- Pendant un chargement, le contenu ne bouge pas d'un pixel : il
             s'estompe sur place. Une ligne « Chargement… » insérée dans le flux
             décalait le tableau vers le bas, et le clic suivant tombait sur la
             mauvaise ligne — exactement au moment où l'on enchaîne les
             descentes. --}}
        <template x-if="indicateurs">
            <div class="relative space-y-6"
                 x-bind:aria-busy="chargement"
                 x-bind:class="chargement ? 'pointer-events-none opacity-40' : ''">

                <p x-show="chargement"
                   class="absolute -top-7 right-0 text-sm text-white-dark">Chargement…</p>

                {{-- Les quatre chiffres qu'on regarde en premier. --}}
                <dl class="grid grid-cols-2 gap-3 lg:grid-cols-4">
                    <template x-for="carte in [
                        { t: 'Facilitateurs actifs', v: indicateurs.facilitateurs_actifs,
                          s: 'sur ' + indicateurs.facilitateurs_formes + ' formés' },
                        { t: 'Cohortes', v: indicateurs.cohortes, s: null },
                        { t: 'Parents inscrits', v: indicateurs.parents_inscrits, s: null },
                        { t: 'Séances tenues', v: indicateurs.seances_tenues, s: null },
                    ]" x-bind:key="carte.t">
                        <div class="panel">
                            <dt class="intitule text-white-dark" x-text="carte.t"></dt>
                            <dd class="chiffre mt-1 text-3xl" x-text="carte.v"></dd>
                            <p class="text-sm text-white-dark" x-show="carte.s" x-text="carte.s"></p>
                        </div>
                    </template>
                </dl>

                {{-- Les trois indicateurs qui demandent une phrase pour être
                     compris. Un chiffre sans sa définition se lit de travers. --}}
                <div class="panel space-y-3">
                    <div class="flex flex-wrap items-baseline gap-x-2">
                        <span class="chiffre text-2xl"
                              x-text="nombre(indicateurs.dose_moyenne_par_parent)"></span>
                        <span class="text-base">séances reçues en moyenne par parent inscrit.</span>
                        <span class="text-base text-white-dark">
                            Un parent rattrapé par son binôme a reçu la séance : il compte.
                        </span>
                    </div>

                    <div class="flex flex-wrap items-baseline gap-x-2">
                        <span class="chiffre text-2xl"
                              x-bind:class="indicateurs.ecarts_releves > 0 ? 'text-warning-texte' : ''"
                              x-text="indicateurs.ecarts_releves"></span>
                        <span class="text-base">écarts entre le déclaré et l'observé.</span>
                        <span class="text-base text-white-dark">
                            Un écart n'est pas une faute : il montre un endroit du déroulé qui résiste.
                        </span>
                    </div>

                    <div class="flex flex-wrap items-baseline gap-x-2">
                        <span class="chiffre text-2xl"
                              x-text="nombre(indicateurs.delai_moyen_remontee_jours)"></span>
                        <span class="text-base">jours en moyenne entre la séance et sa remontée.</span>
                        <span class="text-base text-white-dark">
                            C'est la chaîne d'information elle-même qui se mesure ici.
                        </span>
                    </div>

                    <p class="text-sm text-white-dark" x-show="indicateurs.facilitateurs_jamais_actifs > 0">
                        <span class="chiffre" x-text="indicateurs.facilitateurs_jamais_actifs"></span>
                        facilitateurs formés n'ont jamais tenu de séance. Un facilitateur est
                        compté inactif après
                        <span class="chiffre" x-text="seuilInactivite"></span> jours sans activité.
                    </p>
                </div>

                {{-- Le terrain. Sans ces chiffres, un tableau de bord ne
                     montrerait que les séances de cohorte, et l'on conclurait
                     qu'un facilitateur qui fait des causeries ne fait rien. --}}
                <section>
                    <h2 class="text-xl">Le terrain</h2>

                    <dl class="mt-3 grid grid-cols-2 gap-3 lg:grid-cols-4">
                        <template x-for="c in [
                            { t: 'Activités', v: indicateurs.activites, s: null },
                            { t: 'Personnes touchées', v: indicateurs.parents_touches,
                              s: indicateurs.dont_femmes + ' femmes · ' + indicateurs.dont_hommes + ' hommes' },
                            { t: 'Foyers suivis', v: indicateurs.foyers_suivis,
                              s: indicateurs.foyers_avec_difficulte + ' avec une difficulté' },
                            { t: 'Groupes de soutien', v: indicateurs.groupes_actifs,
                              s: 'actifs sur ' + indicateurs.groupes_soutien },
                        ]" x-bind:key="c.t">
                            <div class="panel">
                                <dt class="intitule text-white-dark" x-text="c.t"></dt>
                                <dd class="chiffre mt-1 text-3xl" x-text="c.v"></dd>
                                <p class="text-sm text-white-dark" x-show="c.s" x-text="c.s"></p>
                            </div>
                        </template>
                    </dl>

                    <div class="panel mt-3 space-y-3">
                        {{-- Le chiffre qui rend le critère mesurable plutôt que
                             déclaratif : on ne l'écrit pas dans un rapport, on le
                             compte, activité par activité. --}}
                        <p class="flex flex-wrap items-baseline gap-x-2">
                            <span class="chiffre text-2xl" x-text="indicateurs.participants_handicap"></span>
                            <span class="text-base">
                                participants en situation de handicap,
                                sur <span class="chiffre" x-text="indicateurs.parents_touches"></span>
                                personnes touchées.
                            </span>
                            <span class="text-base text-white-dark">
                                Comptés activité par activité, jamais estimés.
                            </span>
                        </p>

                        <p class="flex flex-wrap items-baseline gap-x-2"
                           x-show="indicateurs.signalements > 0">
                            <span class="chiffre text-2xl"
                                  x-bind:class="indicateurs.signalements_a_traiter > 0 ? 'text-danger-texte' : ''"
                                  x-text="indicateurs.signalements_a_traiter"></span>
                            <span class="text-base">
                                signalements attendent d'être traités,
                                sur <span class="chiffre" x-text="indicateurs.signalements"></span> reçus.
                            </span>
                            <span class="text-base text-white-dark">
                                Aucune autorité n'est prévenue automatiquement.
                            </span>
                        </p>

                        <p class="flex flex-wrap items-baseline gap-x-2"
                           x-show="indicateurs.groupes_soutien > indicateurs.groupes_actifs">
                            <span class="chiffre text-2xl text-warning-texte"
                                  x-text="indicateurs.groupes_soutien - indicateurs.groupes_actifs"></span>
                            <span class="text-base">
                                groupes de soutien ne se sont pas réunis depuis longtemps.
                            </span>
                            <span class="text-base text-white-dark">
                                Un groupe sans réunion n'est pas un groupe, c'est une ligne dans un rapport.
                            </span>
                        </p>
                    </div>
                </section>

                {{-- Le découpage du niveau en dessous. C'est la descente : les
                     mêmes indicateurs, un cran plus bas, calculés par le même
                     code — c'est ce qui fait que la somme des lignes fait le
                     total au-dessus. --}}
                <template x-if="decoupage">
                    <section>
                        <h2 class="text-xl" x-text="decoupage.libelle"></h2>

                        <p class="mt-1 text-sm text-white-dark" x-show="descendable">
                            Ouvrez une ligne pour descendre d'un niveau.
                        </p>

                        <div class="panel mt-3 overflow-x-auto">
                            <table class="tableau">
                                <thead>
                                    <tr>
                                        <th x-text="descendable ? decoupage.libelle : 'Nom'"></th>
                                        <th class="text-right"
                                            x-text="descendable ? 'Facilitateurs' : 'Dernière activité'"></th>
                                        <th class="text-right">Cohortes</th>
                                        <th class="text-right">Parents</th>
                                        <th class="text-right">Séances</th>
                                        <th class="text-right">Écarts</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <template x-for="ligne in decoupage.lignes" x-bind:key="ligne.id">
                                        <tr x-bind:class="ouvrable(ligne) ? 'cursor-pointer' : ''"
                                            x-on:click="ouvrir(ligne)">
                                            <td>
                                                <span class="font-semibold"
                                                      x-bind:class="ouvrable(ligne) ? 'text-primary underline underline-offset-2' : ''"
                                                      x-text="ligne.libelle"></span>

                                                {{-- Une région non peuplée n'est pas une région
                                                     absente : le dire vaut mieux que laisser
                                                     croire à une erreur. --}}
                                                <span x-show="ligne.peuplee === false"
                                                      class="badge badge-neutre ml-2">Pas encore déployée</span>

                                                <span x-show="ligne.actif === false"
                                                      class="badge badge-neutre ml-2">Inactif</span>
                                            </td>
                                            {{-- Sur un territoire, la part active de l'équipe.
                                                 Sur une personne, « 1/1 » ne dirait rien :
                                                 c'est la date de sa dernière séance qui
                                                 compte, et « jamais » se dit. --}}
                                            <td class="chiffre text-right" x-show="descendable">
                                                <span x-text="ligne.facilitateurs_actifs"></span><span
                                                      class="text-white-dark"
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
                                                x-bind:class="ligne.ecarts_releves > 0 ? 'text-warning-texte font-semibold' : ''"
                                                x-text="ligne.ecarts_releves"></td>
                                        </tr>
                                    </template>
                                </tbody>
                            </table>
                        </div>
                    </section>
                </template>
            </div>
        </template>
    </div>
</x-layouts.delegation>
