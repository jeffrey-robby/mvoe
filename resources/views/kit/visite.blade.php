{{--
    Enregistrer une visite à domicile.

    AUCUN nom, aucun prénom, aucune adresse précise, aucune coordonnée GPS.
    Cet écran ne propose aucun champ où en mettre un, et le serveur n'a aucune
    colonne pour les accueillir.

    C'est la seule façon d'enregistrer ce travail sans constituer un fichier de
    familles vulnérables — document qui, une fois copié, ne protège plus
    personne.

    Les observations sont des cases à cocher, jamais un récit : un champ libre
    finit toujours par contenir un nom, une rue, un détail qui réidentifie.
--}}
<x-layouts.kit titre="Visite à domicile">

    <div x-data="visiteDomicile" x-cloak>

        <template x-if="! enregistre">
            <div class="space-y-5">

                <div>
                    <h1 class="text-3xl">Visite à domicile</h1>
                    <p class="mt-2 text-gris-texte">
                        On enregistre un foyer, pas une famille : une localité, une
                        composition. Aucun nom n'est demandé.
                    </p>
                </div>

                <div x-show="foyersConnus.length > 0">
                    <label for="foyer" class="intitule block">Quel foyer</label>
                    <select id="foyer" x-model="foyerUuid"
                            class="mt-1 min-h-tactile w-full rounded-net border-2 border-noir px-3 text-base">
                        <option value="">Un foyer que je visite pour la première fois</option>
                        <template x-for="f in foyersConnus" x-bind:key="f.uuid">
                            <option x-bind:value="f.uuid"
                                    x-text="f.localite + ' — ' + (f.nb_adultes + f.nb_enfants) + ' personnes'"></option>
                        </template>
                    </select>
                </div>

                {{-- Le dossier du foyer, seulement s'il est nouveau. --}}
                <template x-if="nouveauFoyer">
                    <div class="space-y-5">
                        <div>
                            <label for="localite" class="intitule block">Localité</label>
                            <input id="localite" x-model="localite" type="text" maxlength="80"
                                   placeholder="quartier Nko'ovos"
                                   class="mt-1 min-h-tactile w-full rounded-net border-2 border-noir px-3 text-base">
                            <p class="mt-1 text-base text-gris-texte">
                                Le quartier ou le village. Jamais la rue, jamais le numéro.
                            </p>
                        </div>

                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <label for="adultes" class="intitule block">Adultes</label>
                                <input id="adultes" x-model.number="adultes" type="number" min="0" max="30"
                                       inputmode="numeric"
                                       class="chiffre mt-1 min-h-tactile w-full rounded-net border-2 border-noir px-3 text-2xl">
                            </div>
                            <div>
                                <label for="enfants" class="intitule block">Enfants</label>
                                <input id="enfants" x-model.number="enfants" type="number" min="0" max="30"
                                       inputmode="numeric"
                                       class="chiffre mt-1 min-h-tactile w-full rounded-net border-2 border-noir px-3 text-2xl">
                            </div>
                        </div>

                        {{-- On ne demande pas « y a-t-il un handicapé ? » : on
                             demande ce que quelqu'un a du mal à faire. La
                             première question produit des zéros, la seconde
                             produit des chiffres. --}}
                        <fieldset>
                            <legend class="intitule">
                                Quelqu'un du foyer a du mal à…
                            </legend>
                            <div class="mt-2 grid grid-cols-1 gap-2 sm:grid-cols-2">
                                <template x-for="d in difficultesPossibles" x-bind:key="d.valeur">
                                    <button type="button" x-on:click="basculer('difficultes', d.valeur)"
                                            x-bind:aria-pressed="difficultes.includes(d.valeur)"
                                            x-bind:class="difficultes.includes(d.valeur)
                                                ? 'bg-noir text-blanc' : 'bg-blanc text-noir'"
                                            class="min-h-tactile rounded-net border-2 border-noir px-4 text-left text-base font-semibold [font-family:var(--font-titre)]"
                                            x-text="d.libelle"></button>
                                </template>
                            </div>
                            <p class="mt-1 text-base text-gris-texte">
                                Ne cochez rien si personne n'a de difficulté.
                            </p>
                        </fieldset>

                        <fieldset>
                            <legend class="intitule">A déjà suivi le programme</legend>
                            <div class="mt-2 flex flex-wrap gap-2">
                                <template x-for="o in [
                                    { valeur: true, libelle: 'Oui' },
                                    { valeur: false, libelle: 'Non' },
                                ]" x-bind:key="String(o.valeur)">
                                    <button type="button" x-on:click="dejaSuivi = o.valeur"
                                            x-bind:aria-pressed="dejaSuivi === o.valeur"
                                            x-bind:class="dejaSuivi === o.valeur
                                                ? 'bg-noir text-blanc' : 'bg-blanc text-noir'"
                                            class="min-h-tactile rounded-net border-2 border-noir px-6 text-base font-semibold [font-family:var(--font-titre)]"
                                            x-text="o.libelle"></button>
                                </template>
                            </div>
                        </fieldset>
                    </div>
                </template>

                <div class="rounded-carte border border-ligne p-4 space-y-4">
                    <div>
                        <label for="datev" class="intitule block">Date de la visite</label>
                        <input id="datev" x-model="date" type="date"
                               class="chiffre mt-1 min-h-tactile w-full rounded-net border-2 border-noir px-3 text-base">
                    </div>

                    <fieldset>
                        <legend class="intitule">Ce que j'ai observé</legend>
                        <div class="mt-2 grid grid-cols-1 gap-2 sm:grid-cols-2">
                            <template x-for="o in observationsPossibles" x-bind:key="o.valeur">
                                <button type="button" x-on:click="basculer('observations', o.valeur)"
                                        x-bind:aria-pressed="observations.includes(o.valeur)"
                                        x-bind:class="observations.includes(o.valeur)
                                            ? 'bg-noir text-blanc' : 'bg-blanc text-noir'"
                                        class="min-h-tactile rounded-net border-2 border-noir px-4 text-left text-base font-semibold [font-family:var(--font-titre)]"
                                        x-text="o.libelle"></button>
                            </template>
                        </div>
                        <p class="mt-1 text-base text-gris-texte">
                            Des cases, pas un récit. Ce qui doit se dire avec des mots passe
                            par un signalement.
                        </p>
                    </fieldset>

                    <fieldset>
                        <legend class="intitule">Une autre visite est prévue</legend>
                        <div class="mt-2 flex flex-wrap gap-2">
                            <template x-for="o in [
                                { valeur: true, libelle: 'Oui' },
                                { valeur: false, libelle: 'Non' },
                            ]" x-bind:key="String(o.valeur)">
                                <button type="button" x-on:click="suiviPrevu = o.valeur"
                                        x-bind:aria-pressed="suiviPrevu === o.valeur"
                                        x-bind:class="suiviPrevu === o.valeur
                                            ? 'bg-noir text-blanc' : 'bg-blanc text-noir'"
                                        class="min-h-tactile rounded-net border-2 border-noir px-6 text-base font-semibold [font-family:var(--font-titre)]"
                                        x-text="o.libelle"></button>
                            </template>
                        </div>
                    </fieldset>
                </div>

                <div class="flex flex-wrap gap-2">
                    <x-mvoe.bouton x-on:click="valider()" x-bind:disabled="! peutValider || occupe">
                        <span x-text="occupe ? 'Un instant…' : 'Enregistrer la visite'">
                            Enregistrer la visite
                        </span>
                    </x-mvoe.bouton>
                    <x-mvoe.bouton variante="second" href="/kit">Annuler</x-mvoe.bouton>
                </div>
            </div>
        </template>

        <template x-if="enregistre">
            <div class="space-y-5">
                <x-mvoe.carte rappel>
                    <p class="text-xl [font-family:var(--font-titre)] font-semibold">
                        Visite enregistrée.
                    </p>
                    <p class="mt-2 text-base">
                        Le dossier ne porte aucun nom. Il partira dès que vous retrouverez
                        du réseau.
                    </p>
                </x-mvoe.carte>

                <x-mvoe.bouton variante="second" href="/kit">Revenir à mon kit</x-mvoe.bouton>
            </div>
        </template>
    </div>
</x-layouts.kit>
