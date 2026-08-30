{{--
    Le registre des facilitateurs.

    Il répond à une question à laquelle personne ne sait répondre aujourd'hui :
    combien de facilitateurs formés sont encore actifs ? Le statut n'est pas
    stocké, il se recalcule à chaque consultation — un statut en base se
    périmerait en silence, et c'est exactement le problème d'aujourd'hui.

    Un seul registre pour les quatre niveaux : le MINPROFF y voit les 29
    arrondissements, une délégation régionale les siens, un superviseur le sien.
--}}
<x-layouts.delegation titre="Registre">

    <div x-data="registre" x-cloak class="space-y-5">

        <div class="flex flex-wrap items-start justify-between gap-4">
            <div>
                <h1 class="text-3xl">Registre des facilitateurs</h1>
                <p class="mt-1 text-base text-white-dark" x-text="nom"></p>

                {{-- La portée est écrite : personne ne doit croire lire tout le
                     pays alors qu'il ne lit qu'un arrondissement. --}}
                <p class="mt-1 text-base" x-show="portee">
                    Portée : <span class="font-semibold" x-text="portee?.libelle"></span>
                    <span class="text-white-dark" x-show="portee?.arrondissements">
                        · <span class="chiffre" x-text="portee?.arrondissements"></span>
                        <span x-text="portee?.arrondissements > 1 ? 'arrondissements' : 'arrondissement'"></span>
                    </span>
                    <span class="text-white-dark" x-show="! portee?.arrondissements">· national</span>
                </p>
            </div>

            <div class="sans-impression flex gap-2">
                <a href="/superviseur/enregistrer" class="btn btn-primary">
                    Enregistrer un facilitateur
                </a>
                <button type="button" class="btn btn-neutre" x-on:click="deconnecter()">
                    Fermer la session
                </button>
            </div>
        </div>

        <p x-show="chargement" class="text-white-dark">Chargement…</p>
        <p x-show="erreur" x-text="erreur" class="panel border-l-4 border-danger"></p>

        <template x-if="synthese">
            <div class="space-y-5">

                {{-- La synthèse. C'est elle qui fait la démonstration : la
                     moitié des facilitateurs formés ne sont plus actifs. --}}
                <div class="grid grid-cols-2 gap-4 sm:grid-cols-4">
                    <template x-for="carte in [
                        { titre: 'Formés', valeur: synthese.formes, ton: '' },
                        { titre: 'Actifs', valeur: synthese.actifs, ton: 'text-success-texte' },
                        { titre: 'Inactifs', valeur: synthese.inactifs, ton: 'text-warning-texte' },
                        { titre: 'Jamais actifs', valeur: synthese.jamais_actifs, ton: 'text-danger-texte' },
                    ]" x-bind:key="carte.titre">
                        <div class="panel">
                            <p class="intitule text-white-dark" x-text="carte.titre"></p>
                            {{-- La couleur accompagne le chiffre, elle ne le
                                 remplace pas : l'intitulé est juste au-dessus,
                                 en toutes lettres. --}}
                            <p class="chiffre mt-1 text-4xl font-semibold"
                               x-bind:class="carte.ton" x-text="carte.valeur"></p>
                        </div>
                    </template>
                </div>

                <div class="panel space-y-4">
                    <div class="flex flex-wrap items-end justify-between gap-4">
                        <p class="max-w-prose text-base text-white-dark">
                            Est considéré comme inactif un facilitateur sans séance remontée depuis
                            plus de <span class="chiffre" x-text="synthese.seuil_inactivite_jours"></span>
                            jours. Ce seuil se règle dans la configuration, pas dans le code.
                        </p>

                        <div class="sans-impression">
                            <label for="arr" class="etiquette">Arrondissement</label>
                            <select id="arr" x-model="arrondissement" class="champ w-64">
                                <option value="">Tous ceux de ma portée</option>
                                <template x-for="a in arrondissements" x-bind:key="a">
                                    <option x-bind:value="a" x-text="a"></option>
                                </template>
                            </select>
                        </div>
                    </div>

                    <div class="overflow-x-auto">
                        <table class="tableau">
                            <thead>
                                <tr>
                                    <th>Facilitateur</th>
                                    <th>Arrondissement</th>
                                    <th>Formé le</th>
                                    <th>Dernière activité</th>
                                    <th class="text-right">Séances</th>
                                    <th class="text-right">Formation</th>
                                    <th>Statut</th>
                                </tr>
                            </thead>
                            <tbody>
                                <template x-for="f in liste" x-bind:key="f.id">
                                    <tr>
                                        <td>
                                            <span class="font-semibold" x-text="f.nom"></span>
                                            <span class="chiffre block text-sm text-white-dark"
                                                  x-text="f.telephone"></span>
                                            <span class="block text-sm text-white-dark"
                                                  x-text="f.type_juridique"></span>
                                        </td>
                                        <td>
                                            <span x-text="f.arrondissement"></span>
                                            <span class="block text-sm text-white-dark"
                                                  x-text="f.departement"></span>
                                        </td>
                                        <td class="chiffre text-sm"
                                            x-text="f.date_formation_initiale.split('-').reverse().join('/')"></td>
                                        <td class="chiffre text-sm">
                                            <span x-text="derniereActivite(f)"></span>
                                            <template x-if="f.jours_depuis_activite !== null">
                                                <span class="block text-white-dark"
                                                      x-text="'il y a ' + f.jours_depuis_activite + ' j'"></span>
                                            </template>
                                        </td>
                                        <td class="chiffre text-right" x-text="f.seances_animees"></td>

                                        {{-- Où il en est de sa formation. Ce n'est
                                             pas de la surveillance : c'est la seule
                                             façon de repérer qui décroche AVANT qu'il
                                             ne disparaîsse du registre. --}}
                                        <td class="chiffre text-right">
                                            <span x-text="f.modules_termines"></span><span
                                                  class="text-white-dark"
                                                  x-text="'/' + synthese.modules_diffusables"></span>
                                            <span class="block text-sm text-white-dark"
                                                  x-show="f.modules_ouverts > f.modules_termines">
                                                <span x-text="f.modules_ouverts - f.modules_termines"></span>
                                                en cours
                                            </span>
                                        </td>

                                        {{-- Le statut est ÉCRIT, jamais porté par
                                             une couleur seule : « Actif » et
                                             « Inactif » se lisent, y compris à
                                             l'impression en noir et blanc. --}}
                                        <td>
                                            <span class="badge"
                                                  x-bind:class="f.actif ? 'badge-succes' : 'badge-neutre'"
                                                  x-text="f.actif ? 'Actif' : 'Inactif'"></span>
                                        </td>
                                    </tr>
                                </template>
                            </tbody>
                        </table>
                    </div>

                    <template x-if="liste.length === 0">
                        <p class="py-8 text-center text-base text-white-dark">
                            Aucun facilitateur dans cet arrondissement.
                        </p>
                    </template>
                </div>
            </div>
        </template>
    </div>
</x-layouts.delegation>
