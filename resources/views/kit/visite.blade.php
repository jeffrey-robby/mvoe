{{--
    AUCUN nom, aucun prénom, aucune adresse précise, aucune coordonnée GPS.
    Cet écran ne propose aucun champ où en mettre un, et le serveur n'a aucune
    colonne pour les accueillir. Les observations sont des cases, jamais un
    récit : un champ libre finit toujours par contenir un nom ou une rue.
--}}
<x-layouts.kit titre="Visite à domicile">

    <div x-data="visiteDomicile" x-cloak>

        <template x-if="! enregistre">
            <div>
                <div class="mb-6">
                    <h2 class="text-2xl font-bold dark:text-white-light">Visite à domicile</h2>
                    <p class="text-white-dark mt-1 max-w-prose">
                        On enregistre un foyer, pas une famille : une localité, une composition,
                        des difficultés fonctionnelles. Aucun nom n'est demandé.
                    </p>
                </div>

                <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">

                    <div class="panel space-y-5">
                        <h5 class="font-semibold text-lg dark:text-white-light">Le foyer</h5>

                        <div x-show="foyersConnus.length > 0">
                            <label for="foyer">Quel foyer</label>
                            <select id="foyer" x-model="foyerUuid" class="form-select">
                                <option value="">Un foyer que je visite pour la première fois</option>
                                <template x-for="f in foyersConnus" x-bind:key="f.uuid">
                                    <option x-bind:value="f.uuid"
                                            x-text="f.localite + ' — ' + (f.nb_adultes + f.nb_enfants) + ' personnes'"></option>
                                </template>
                            </select>
                        </div>

                        <template x-if="nouveauFoyer">
                            <div class="space-y-5">
                                <div>
                                    <label for="localite">Localité</label>
                                    <input id="localite" x-model="localite" type="text" maxlength="80"
                                           placeholder="quartier Nko'ovos" class="form-input">
                                    <span class="text-white-dark text-[11px] inline-block mt-1">
                                        Le quartier ou le village. Jamais la rue, jamais le numéro.
                                    </span>
                                </div>

                                <div class="grid grid-cols-2 gap-5">
                                    <div>
                                        <label for="adultes">Adultes</label>
                                        <input id="adultes" x-model.number="adultes" type="number" min="0" max="30"
                                               inputmode="numeric" class="form-input form-input-lg chiffre">
                                    </div>
                                    <div>
                                        <label for="enfants">Enfants</label>
                                        <input id="enfants" x-model.number="enfants" type="number" min="0" max="30"
                                               inputmode="numeric" class="form-input form-input-lg chiffre">
                                    </div>
                                </div>

                                {{-- On ne demande pas « y a-t-il un handicapé ? » :
                                     on demande ce que quelqu'un a du mal à faire.
                                     La première question produit des zéros, la
                                     seconde produit des chiffres. --}}
                                <div>
                                    <label>Quelqu'un du foyer a du mal à…</label>
                                    <div class="flex flex-wrap gap-2">
                                        <template x-for="d in difficultesPossibles" x-bind:key="d.valeur">
                                            <button type="button" class="btn btn-sm"
                                                    x-on:click="basculer('difficultes', d.valeur)"
                                                    x-bind:aria-pressed="difficultes.includes(d.valeur)"
                                                    x-bind:class="difficultes.includes(d.valeur) ? 'btn-primary' : 'btn-outline-primary'"
                                                    x-text="d.libelle"></button>
                                        </template>
                                    </div>
                                    <span class="text-white-dark text-[11px] inline-block mt-1">
                                        Ne cochez rien si personne n'a de difficulté.
                                    </span>
                                </div>

                                <div>
                                    <label>A déjà suivi le programme</label>
                                    <div class="flex flex-wrap gap-2">
                                        <template x-for="o in [
                                            { valeur: true, libelle: 'Oui' },
                                            { valeur: false, libelle: 'Non' },
                                        ]" x-bind:key="String(o.valeur)">
                                            <button type="button" class="btn btn-sm px-6"
                                                    x-on:click="dejaSuivi = o.valeur"
                                                    x-bind:aria-pressed="dejaSuivi === o.valeur"
                                                    x-bind:class="dejaSuivi === o.valeur ? 'btn-primary' : 'btn-outline-primary'"
                                                    x-text="o.libelle"></button>
                                        </template>
                                    </div>
                                </div>
                            </div>
                        </template>
                    </div>

                    <div class="panel space-y-5 h-fit">
                        <h5 class="font-semibold text-lg dark:text-white-light">La visite</h5>

                        <div>
                            <label for="datev">Date de la visite</label>
                            <input id="datev" x-model="date" type="date" class="form-input chiffre">
                        </div>

                        <div>
                            <label>Ce que j'ai observé</label>
                            <div class="flex flex-wrap gap-2">
                                <template x-for="o in observationsPossibles" x-bind:key="o.valeur">
                                    <button type="button" class="btn btn-sm"
                                            x-on:click="basculer('observations', o.valeur)"
                                            x-bind:aria-pressed="observations.includes(o.valeur)"
                                            x-bind:class="observations.includes(o.valeur) ? 'btn-primary' : 'btn-outline-primary'"
                                            x-text="o.libelle"></button>
                                </template>
                            </div>
                            <span class="text-white-dark text-[11px] inline-block mt-1">
                                Des cases, pas un récit. Ce qui doit se dire avec des mots passe
                                par un signalement.
                            </span>
                        </div>

                        <div>
                            <label>Une autre visite est prévue</label>
                            <div class="flex flex-wrap gap-2">
                                <template x-for="o in [
                                    { valeur: true, libelle: 'Oui' },
                                    { valeur: false, libelle: 'Non' },
                                ]" x-bind:key="String(o.valeur)">
                                    <button type="button" class="btn btn-sm px-6"
                                            x-on:click="suiviPrevu = o.valeur"
                                            x-bind:aria-pressed="suiviPrevu === o.valeur"
                                            x-bind:class="suiviPrevu === o.valeur ? 'btn-primary' : 'btn-outline-primary'"
                                            x-text="o.libelle"></button>
                                </template>
                            </div>
                        </div>

                        <div class="flex flex-wrap gap-3 !mt-8">
                            <button type="button" class="btn btn-primary" x-on:click="valider()"
                                    x-bind:disabled="! peutValider || occupe">
                                <span x-text="occupe ? 'Un instant…' : 'Enregistrer la visite'">Enregistrer la visite</span>
                            </button>
                            <a href="/kit" class="btn btn-outline-primary">Annuler</a>
                        </div>
                    </div>
                </div>
            </div>
        </template>

        <template x-if="enregistre">
            <div class="panel max-w-xl">
                <h5 class="font-semibold text-lg dark:text-white-light">Visite enregistrée</h5>
                <p class="text-white-dark mt-1">
                    Le dossier ne porte aucun nom. Il partira dès que vous retrouverez du réseau.
                </p>
                <a href="/kit" class="btn btn-outline-primary mt-5 inline-flex">Revenir à mon kit</a>
            </div>
        </template>
    </div>
</x-layouts.kit>
