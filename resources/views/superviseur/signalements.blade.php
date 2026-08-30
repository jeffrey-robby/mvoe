{{--
    La file des signalements.

    Le système n'a prévenu personne. Ces situations attendent ici, dans la file
    d'un humain qui juge et qui décide — une alerte automatique de maltraitance
    ferait courir un risque à l'enfant qu'elle prétend protéger : elle prévient
    avant que quiconque ait vérifié, et parfois elle prévient l'agresseur.

    Aucune ligne ne porte l'identité d'un enfant, d'un parent ou d'un foyer :
    un type, une gravité, un arrondissement, et le facilitateur avec qui en
    parler.
--}}
<x-layouts.delegation titre="Signalements">

    <div x-data="signalements" x-cloak class="space-y-6">

        <div>
            <h1 class="text-3xl">Signalements</h1>
            <p class="mt-2 max-w-prose text-white-dark">
                Aucune autorité n'est prévenue automatiquement. Ces situations vous sont
                remontées pour que vous en décidiez.
            </p>
        </div>

        <p x-show="chargement && ! synthese" class="text-white-dark">Chargement…</p>
        <p x-show="erreur" x-text="erreur" class="panel border-l-4 border-warning"></p>

        <template x-if="synthese">
            <div class="space-y-6">

                <dl class="grid grid-cols-2 gap-3 lg:grid-cols-4">
                    <template x-for="c in [
                        { t: 'À traiter', v: synthese.a_traiter, alerte: synthese.a_traiter > 0 },
                        { t: 'Dont graves', v: synthese.graves_non_traites, alerte: synthese.graves_non_traites > 0 },
                        { t: 'Reçus en tout', v: synthese.total, alerte: false },
                        { t: 'Délai moyen', v: synthese.delai_moyen_traitement_jours ?? '—', alerte: false },
                    ]" x-bind:key="c.t">
                        <div class="panel">
                            <dt class="intitule text-white-dark" x-text="c.t"></dt>
                            <dd class="chiffre mt-1 text-3xl"
                                x-bind:class="c.alerte ? 'text-danger-texte' : ''"
                                x-text="c.v"></dd>
                        </div>
                    </template>
                </dl>

                <div class="flex flex-wrap items-center gap-2">
                    <template x-for="f in [
                        { valeur: 'ouverts', libelle: 'Ce qui attend' },
                        { valeur: 'tous', libelle: 'Tout l\'historique' },
                    ]" x-bind:key="f.valeur">
                        <button type="button" x-on:click="filtre = f.valeur"
                                x-bind:aria-pressed="filtre === f.valeur"
                                x-bind:class="filtre === f.valeur ? 'btn-primary' : 'btn-neutre'"
                                class="btn" x-text="f.libelle"></button>
                    </template>
                </div>

                <template x-if="affiches.length === 0">
                    <div class="panel">
                        <p class="text-base">
                            Rien n'attend dans votre file. Les signalements traités restent
                            consultables dans l'historique.
                        </p>
                    </div>
                </template>

                <template x-if="affiches.length > 0">
                    <div class="panel overflow-x-auto">
                        <table class="tableau">
                            <thead>
                                <tr>
                                    <th>Situation</th>
                                    <th>Gravité</th>
                                    <th>Arrondissement</th>
                                    <th>Signalé par</th>
                                    <th class="text-right">Attente</th>
                                    <th>Statut</th>
                                </tr>
                            </thead>
                            <tbody>
                                <template x-for="s in affiches" x-bind:key="s.uuid">
                                    <tr class="cursor-pointer" x-on:click="ouvrir(s)">
                                        <td>
                                            <span class="font-semibold text-primary underline underline-offset-2"
                                                  x-text="s.type_libelle"></span>
                                            <span class="chiffre block text-sm text-white-dark"
                                                  x-text="s.soumis_le.split('-').reverse().join('/')"></span>
                                        </td>
                                        <td>
                                            <span class="badge"
                                                  x-bind:class="s.gravite === 'elevee' ? 'badge-alerte' : 'badge-neutre'"
                                                  x-text="s.gravite_libelle"></span>
                                        </td>
                                        <td x-text="s.arrondissement"></td>
                                        <td x-text="s.facilitateur"></td>
                                        <td class="chiffre text-right"
                                            x-bind:class="s.ouvert && s.jours_attente > 14 ? 'text-danger-texte font-semibold' : ''"
                                            x-text="s.jours_attente + ' j'"></td>
                                        <td>
                                            <span class="badge"
                                                  x-bind:class="s.ouvert ? 'badge-alerte' : 'badge-succes'"
                                                  x-text="s.statut_libelle"></span>
                                        </td>
                                    </tr>
                                </template>
                            </tbody>
                        </table>
                    </div>
                </template>

                {{-- Le traitement. Rien ne part vers personne : on inscrit une
                     décision et la suite que le facilitateur lira. --}}
                <template x-if="ouvert">
                    <div class="panel border-l-4 border-primary space-y-4">
                        <div>
                            <h2 class="text-2xl" x-text="ouvert.type_libelle"></h2>
                            <p class="mt-1 text-white-dark">
                                Gravité <span x-text="ouvert.gravite_libelle.toLowerCase()"></span> ·
                                <span x-text="ouvert.arrondissement"></span> ·
                                signalé par <span x-text="ouvert.facilitateur"></span>,
                                il y a <span class="chiffre" x-text="ouvert.jours_attente"></span> jours.
                            </p>
                        </div>

                        <div>
                            <p class="etiquette">Décision</p>
                            <div class="flex flex-wrap gap-2">
                                <template x-for="s in [
                                    { valeur: 'examine', libelle: 'Examiné' },
                                    { valeur: 'oriente', libelle: 'Orienté' },
                                    { valeur: 'clos', libelle: 'Clos' },
                                ]" x-bind:key="s.valeur">
                                    <button type="button" x-on:click="statut = s.valeur"
                                            x-bind:aria-pressed="statut === s.valeur"
                                            x-bind:class="statut === s.valeur ? 'btn-primary' : 'btn-neutre'"
                                            class="btn" x-text="s.libelle"></button>
                                </template>
                            </div>
                        </div>

                        <div>
                            <label for="suite" class="etiquette">
                                Suite donnée
                                <span class="text-white-dark" x-show="! suiteRequise">— facultative à ce stade</span>
                            </label>
                            <textarea id="suite" x-model="suite" rows="3" class="champ"
                                      placeholder="Ce que le facilitateur lira."></textarea>
                            <p class="mt-1 text-sm text-white-dark">
                                Le facilitateur verra ce texte. Un signalement sans retour est un
                                signalement qu'il ne refera pas.
                            </p>
                        </div>

                        <div class="flex flex-wrap gap-2">
                            <button type="button" class="btn btn-primary" x-on:click="traiter()"
                                    x-bind:disabled="! peutValider || occupe">
                                <span x-text="occupe ? 'Un instant…' : 'Enregistrer la décision'">
                                    Enregistrer la décision
                                </span>
                            </button>
                            <button type="button" class="btn btn-neutre" x-on:click="fermer()">Annuler</button>
                        </div>
                    </div>
                </template>
            </div>
        </template>
    </div>
</x-layouts.delegation>
