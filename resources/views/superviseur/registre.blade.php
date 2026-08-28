{{--
    Le registre des facilitateurs.

    Il répond à une question à laquelle personne ne sait répondre aujourd'hui :
    combien de facilitateurs formés sont encore actifs ? Le statut n'est pas
    stocké, il se recalcule à chaque consultation — un statut en base se
    périmerait en silence, et c'est exactement le problème d'aujourd'hui.
--}}
<x-layouts.delegation titre="Registre">

    <div x-data="registre" x-cloak class="space-y-6">

        <div class="flex flex-wrap items-baseline justify-between gap-3">
            <div>
                <h1 class="text-3xl">Registre des facilitateurs</h1>
                <p class="mt-1 text-gris-texte" x-text="nom"></p>
                {{-- Le périmètre est écrit : personne ne doit croire lire tout
                     le département alors qu'il ne lit qu'un arrondissement. --}}
                <p class="mt-1 text-sm" x-show="perimetre">
                    Périmètre : <span class="font-semibold" x-text="perimetre"></span>
                </p>
            </div>

            <x-mvoe.bouton variante="discret" class="sans-impression" x-on:click="deconnecter()">
                Fermer la session
            </x-mvoe.bouton>
        </div>

        <template x-if="chargement">
            <p class="text-gris-texte">Chargement…</p>
        </template>

        <p x-show="erreur" x-text="erreur" class="rounded-net bg-jaune-sourd px-3 py-2 text-sm"></p>

        <template x-if="synthese">
            <div class="space-y-6">

                {{-- La synthèse, en trois chiffres. C'est elle qui fait la
                     démonstration : sept actifs sur quatorze formés. --}}
                <div class="grid grid-cols-2 gap-3 sm:grid-cols-4">
                    <template x-for="carte in [
                        { titre: 'Formés', valeur: synthese.formes },
                        { titre: 'Actifs', valeur: synthese.actifs },
                        { titre: 'Inactifs', valeur: synthese.inactifs },
                        { titre: 'Jamais actifs', valeur: synthese.jamais_actifs },
                    ]" x-bind:key="carte.titre">
                        <div class="rounded-carte border border-ligne p-3 text-center">
                            <p class="intitule text-xs" x-text="carte.titre"></p>
                            <p class="chiffre text-3xl" x-text="carte.valeur"></p>
                        </div>
                    </template>
                </div>

                <p class="text-sm text-gris-texte">
                    Est considéré comme inactif un facilitateur sans séance remontée depuis plus de
                    <span class="chiffre" x-text="synthese.seuil_inactivite_jours"></span> jours.
                    Ce seuil se règle dans la configuration, pas dans le code.
                </p>

                {{-- Filtre par arrondissement. --}}
                <div class="sans-impression">
                    <label for="arr" class="intitule block">Arrondissement</label>
                    <select id="arr" x-model="arrondissement"
                            class="mt-1 min-h-tactile w-full max-w-sm rounded-net border-2 border-noir bg-blanc px-3 text-base">
                        <option value="">Tous les arrondissements</option>
                        <template x-for="a in arrondissements" x-bind:key="a">
                            <option x-bind:value="a" x-text="a"></option>
                        </template>
                    </select>
                </div>

                <div class="overflow-x-auto">
                    <table class="w-full border-collapse text-left">
                        <thead>
                            <tr class="border-b-2 border-noir">
                                <th class="intitule py-2 pr-3">Facilitateur</th>
                                <th class="intitule py-2 pr-3">Arrondissement</th>
                                <th class="intitule py-2 pr-3">Formé le</th>
                                <th class="intitule py-2 pr-3">Dernière activité</th>
                                <th class="intitule py-2 pr-3 text-right">Séances</th>
                                <th class="intitule py-2">Statut</th>
                            </tr>
                        </thead>
                        <tbody>
                            <template x-for="f in liste" x-bind:key="f.id">
                                <tr class="border-b border-ligne align-top">
                                    <td class="py-2 pr-3">
                                        <span x-text="f.nom"></span>
                                        <span class="chiffre block text-sm text-gris-texte"
                                              x-text="f.telephone"></span>
                                    </td>
                                    <td class="py-2 pr-3" x-text="f.arrondissement"></td>
                                    <td class="chiffre py-2 pr-3 text-sm"
                                        x-text="f.date_formation.split('-').reverse().join('/')"></td>
                                    <td class="chiffre py-2 pr-3 text-sm">
                                        <span x-text="derniereActivite(f)"></span>
                                        <template x-if="f.jours_depuis_activite !== null">
                                            <span class="block text-gris-texte"
                                                  x-text="'il y a ' + f.jours_depuis_activite + ' j'"></span>
                                        </template>
                                    </td>
                                    <td class="chiffre py-2 pr-3 text-right" x-text="f.seances_animees"></td>

                                    {{-- Le statut est écrit, jamais porté par une
                                         couleur seule : « actif » et « inactif »
                                         se lisent, y compris à l'impression. --}}
                                    <td class="py-2">
                                        <span class="intitule text-xs"
                                              x-bind:class="f.actif ? '' : 'text-gris-texte'"
                                              x-text="f.actif ? 'Actif' : 'Inactif'"></span>
                                    </td>
                                </tr>
                            </template>
                        </tbody>
                    </table>
                </div>

                <template x-if="liste.length === 0">
                    <x-mvoe.vide>Aucun facilitateur dans cet arrondissement.</x-mvoe.vide>
                </template>
            </div>
        </template>
    </div>
</x-layouts.delegation>
