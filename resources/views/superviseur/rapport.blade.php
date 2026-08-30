{{--
    Le rapport trimestriel.

    C'est un DOCUMENT : la photographie d'un trimestre clos, faite pour être
    imprimée, signée et transmise. Pas un tableau de bord temps réel, pas un
    écran de graphiques — le cahier des charges l'exclut explicitement.

    Le tableau qui compte est celui des écarts par facilitateur : c'est ce
    qu'aucun formulaire papier ne peut produire, parce que le papier n'a qu'une
    seule source et n'a donc rien à confronter.
--}}
<x-layouts.delegation titre="Rapport">

    <div x-data="rapport" x-cloak class="space-y-6">

        {{-- Sélecteur de période. Disparaît à l'impression. --}}
        <div class="sans-impression flex flex-wrap items-end gap-3">
            <div>
                <label for="annee" class="etiquette">Année</label>
                <input id="annee" type="number" x-model.number="annee" min="2020" max="2100"
                       class="champ chiffre w-32">
            </div>

            <div>
                <label for="trim" class="etiquette">Trimestre</label>
                <select id="trim" x-model.number="trimestre"
                        class="champ">
                    <template x-for="t in [1, 2, 3, 4]" x-bind:key="t">
                        <option x-bind:value="t" x-text="t + 'ᵉ trimestre'"></option>
                    </template>
                </select>
            </div>

            <button type="button" class="btn btn-neutre" x-on:click="charger()">Afficher</button>
            <button type="button" class="btn btn-primary" x-on:click="exporter()" x-bind:disabled="! donnees">
                Exporter en PDF
            </button>
        </div>

        <template x-if="chargement">
            <p class="text-white-dark">Chargement…</p>
        </template>

        <p x-show="erreur" x-text="erreur" class="panel border-l-4 border-warning"></p>

        <template x-if="donnees">
            <article class="space-y-6">

                {{-- En-tête du document. --}}
                <header class="border-b-2 border-noir pb-4">
                    <p class="intitule">Programme national de parentalité positive</p>
                    <h1 class="mt-1 text-3xl">Rapport trimestriel</h1>
                    <p class="chiffre mt-2">
                        <span x-text="periode"></span> —
                        du <span x-text="donnees.periode.du.split('-').reverse().join('/')"></span>
                        au <span x-text="donnees.periode.au.split('-').reverse().join('/')"></span>
                    </p>
                    <p class="mt-1" x-show="donnees.portee">
                        Portée : <span class="font-semibold" x-text="donnees.portee?.libelle"></span>
                    </p>
                    <p class="mt-1 text-sm text-white-dark">
                        <span x-text="nom"></span> · document établi le <span x-text="genereLe"></span>
                    </p>
                </header>

                <template x-if="vide">
                    <x-mvoe.vide>Aucune séance n'a été tenue sur ce trimestre.</x-mvoe.vide>
                </template>

                <template x-if="! vide">
                    <div class="space-y-6">

                        {{-- Les chiffres du trimestre. --}}
                        <section class="insecable">
                            <h2 class="text-xl">Ce que dit le trimestre</h2>

                            <dl class="mt-3 grid grid-cols-2 gap-3 sm:grid-cols-4">
                                <template x-for="c in [
                                    { t: 'Séances tenues', v: donnees.synthese.seances_tenues },
                                    { t: 'Cohortes actives', v: donnees.synthese.cohortes_actives },
                                    { t: 'Dose moyenne', v: nombre(donnees.synthese.dose_moyenne_par_parent) },
                                    { t: 'Écarts relevés', v: donnees.synthese.ecarts_total },
                                ]" x-bind:key="c.t">
                                    <div class="panel text-center">
                                        <dt class="intitule text-xs" x-text="c.t"></dt>
                                        <dd class="chiffre text-3xl" x-text="c.v"></dd>
                                    </div>
                                </template>
                            </dl>

                            <p class="mt-3 max-w-prose text-sm text-white-dark">
                                La dose moyenne est le nombre de séances réellement reçues par parent
                                inscrit. Un parent rattrapé par son binôme a reçu la séance : il compte.
                            </p>
                        </section>

                        {{-- Le dispositif humain. --}}
                        <section class="insecable">
                            <h2 class="text-xl">Le dispositif</h2>

                            <p class="mt-2" x-text="phraseDispositif"></p>
                            <p class="mt-1" x-text="phraseDelai"></p>
                        </section>

                        {{-- Les cohortes. --}}
                        <section>
                            <h2 class="text-xl">Cohortes</h2>

                            <div class="panel mt-3 overflow-x-auto">
                                <table class="tableau">
                                    <thead>
                                        <tr>
                                            <th>Cohorte</th>
                                            <th>Arrondissement</th>
                                            <th class="text-right">Effectif</th>
                                            <th class="text-right">Plafond</th>
                                            <th class="text-right">Séances</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <template x-for="c in donnees.cohortes" x-bind:key="c.libelle">
                                            <tr>
                                                <td class="font-semibold" x-text="c.libelle"></td>
                                                <td x-text="c.arrondissement"></td>
                                                <td class="chiffre text-right" x-text="c.effectif"></td>
                                                <td class="chiffre text-right" x-text="c.ratio_max"></td>
                                                <td class="chiffre text-right" x-text="c.seances_tenues"></td>
                                            </tr>
                                        </template>
                                    </tbody>
                                </table>
                            </div>
                        </section>

                        {{-- LE tableau du rapport. --}}
                        <section>
                            <h2 class="text-xl">Écart entre le déclaré et l'observé</h2>

                            <p class="mt-2 max-w-prose text-sm">
                                Chaque séance porte deux sources indépendantes : ce que le facilitateur
                                a déclaré après la séance, et ce que l'outil a enregistré pendant.
                                L'écart est la différence entre les deux. Aucun formulaire papier ne
                                peut le produire, faute d'une seconde source à confronter.
                            </p>

                            <div class="panel mt-3 overflow-x-auto">
                                <table class="tableau">
                                    <thead>
                                        <tr>
                                            <th>Facilitateur</th>
                                            <th class="text-right">Séances</th>
                                            <th class="text-right">Séquences déclarées</th>
                                            <th class="text-right">Déclarées jamais ouvertes</th>
                                            <th class="text-right">Ouvertes déclarées non faites</th>
                                            <th class="text-right">Délai moyen</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <template x-for="f in donnees.facilitateurs" x-bind:key="f.nom">
                                            <tr>
                                                <td>
                                                    <span class="font-semibold" x-text="f.nom"></span>
                                                    <span class="block text-sm text-white-dark"
                                                          x-text="f.arrondissement"></span>
                                                </td>
                                                <td class="chiffre text-right" x-text="f.seances"></td>
                                                <td class="chiffre text-right"
                                                    x-text="f.sequences_declarees_realisees"></td>

                                                {{-- Les deux colonnes d'écart. Le chiffre reste lisible
                                                     quoi qu'il arrive ; l'appui typographique ne fait
                                                     qu'attirer l'œil, il ne porte pas l'information. --}}
                                                <td class="chiffre text-right"
                                                    x-bind:class="f.declarees_jamais_ouvertes > 0 ? 'text-warning-texte font-semibold' : ''"
                                                    x-text="f.declarees_jamais_ouvertes"></td>
                                                <td class="chiffre text-right"
                                                    x-bind:class="f.ouvertes_declarees_non_faites > 0 ? 'text-warning-texte font-semibold' : ''"
                                                    x-text="f.ouvertes_declarees_non_faites"></td>

                                                <td class="chiffre text-right"
                                                    x-text="nombre(f.delai_moyen_remontee_jours) + ' j'"></td>
                                            </tr>
                                        </template>
                                    </tbody>
                                </table>
                            </div>

                            <p class="mt-3 max-w-prose text-sm text-white-dark">
                                Un écart n'est pas une faute. Il indique un endroit du déroulé qui
                                résiste, et se lit avec le facilitateur, pas contre lui.
                            </p>
                        </section>

                        <footer class="border-t border-white-light pt-4 text-sm text-white-dark">
                            <p>
                                Document généré par Mvoé. Il ne contient aucune donnée nominative de
                                parent ni d'enfant : le programme n'en collecte pas.
                            </p>
                        </footer>
                    </div>
                </template>
            </article>
        </template>
    </div>
</x-layouts.delegation>
