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
                <label for="annee" class="intitule block">Année</label>
                <input id="annee" type="number" x-model.number="annee" min="2020" max="2100"
                       class="chiffre mt-1 min-h-tactile w-32 rounded-net border-2 border-noir px-3 text-base">
            </div>

            <div>
                <label for="trim" class="intitule block">Trimestre</label>
                <select id="trim" x-model.number="trimestre"
                        class="mt-1 min-h-tactile rounded-net border-2 border-noir bg-blanc px-3 text-base">
                    <template x-for="t in [1, 2, 3, 4]" x-bind:key="t">
                        <option x-bind:value="t" x-text="t + 'ᵉ trimestre'"></option>
                    </template>
                </select>
            </div>

            <x-mvoe.bouton variante="second" x-on:click="charger()">Afficher</x-mvoe.bouton>
            <x-mvoe.bouton x-on:click="exporter()" x-bind:disabled="! donnees">
                Exporter en PDF
            </x-mvoe.bouton>
        </div>

        <template x-if="chargement">
            <p class="text-gris-texte">Chargement…</p>
        </template>

        <p x-show="erreur" x-text="erreur" class="rounded-net bg-jaune-sourd px-3 py-2 text-sm"></p>

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
                    <p class="mt-1" x-show="donnees.perimetre">
                        Périmètre : <span class="font-semibold" x-text="donnees.perimetre"></span>
                    </p>
                    <p class="mt-1 text-sm text-gris-texte">
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
                                    <div class="rounded-carte border border-ligne p-3 text-center">
                                        <dt class="intitule text-xs" x-text="c.t"></dt>
                                        <dd class="chiffre text-3xl" x-text="c.v"></dd>
                                    </div>
                                </template>
                            </dl>

                            <p class="mt-3 max-w-prose text-sm text-gris-texte">
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

                            <div class="mt-3 overflow-x-auto">
                                <table class="w-full border-collapse text-left">
                                    <thead>
                                        <tr class="border-b-2 border-noir">
                                            <th class="intitule py-2 pr-3">Cohorte</th>
                                            <th class="intitule py-2 pr-3">Arrondissement</th>
                                            <th class="intitule py-2 pr-3 text-right">Effectif</th>
                                            <th class="intitule py-2 pr-3 text-right">Plafond</th>
                                            <th class="intitule py-2 text-right">Séances</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <template x-for="c in donnees.cohortes" x-bind:key="c.libelle">
                                            <tr class="border-b border-ligne">
                                                <td class="py-2 pr-3" x-text="c.libelle"></td>
                                                <td class="py-2 pr-3" x-text="c.arrondissement"></td>
                                                <td class="chiffre py-2 pr-3 text-right" x-text="c.effectif"></td>
                                                <td class="chiffre py-2 pr-3 text-right" x-text="c.ratio_max"></td>
                                                <td class="chiffre py-2 text-right" x-text="c.seances_tenues"></td>
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

                            <div class="mt-3 overflow-x-auto">
                                <table class="w-full border-collapse text-left">
                                    <thead>
                                        <tr class="border-b-2 border-noir">
                                            <th class="intitule py-2 pr-3">Facilitateur</th>
                                            <th class="intitule py-2 pr-3 text-right">Séances</th>
                                            <th class="intitule py-2 pr-3 text-right">Séquences déclarées</th>
                                            <th class="intitule py-2 pr-3 text-right">Déclarées jamais ouvertes</th>
                                            <th class="intitule py-2 pr-3 text-right">Ouvertes déclarées non faites</th>
                                            <th class="intitule py-2 text-right">Délai moyen</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <template x-for="f in donnees.facilitateurs" x-bind:key="f.nom">
                                            <tr class="border-b border-ligne">
                                                <td class="py-2 pr-3">
                                                    <span x-text="f.nom"></span>
                                                    <span class="block text-sm text-gris-texte"
                                                          x-text="f.arrondissement"></span>
                                                </td>
                                                <td class="chiffre py-2 pr-3 text-right" x-text="f.seances"></td>
                                                <td class="chiffre py-2 pr-3 text-right"
                                                    x-text="f.sequences_declarees_realisees"></td>
                                                <td class="chiffre py-2 pr-3 text-right"
                                                    x-text="f.declarees_jamais_ouvertes"></td>
                                                <td class="chiffre py-2 pr-3 text-right"
                                                    x-text="f.ouvertes_declarees_non_faites"></td>
                                                <td class="chiffre py-2 text-right"
                                                    x-text="nombre(f.delai_moyen_remontee_jours) + ' j'"></td>
                                            </tr>
                                        </template>
                                    </tbody>
                                </table>
                            </div>

                            <p class="mt-3 max-w-prose text-sm text-gris-texte">
                                Un écart n'est pas une faute. Il indique un endroit du déroulé qui
                                résiste, et se lit avec le facilitateur, pas contre lui.
                            </p>
                        </section>

                        <footer class="border-t border-ligne pt-4 text-sm text-gris-texte">
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
