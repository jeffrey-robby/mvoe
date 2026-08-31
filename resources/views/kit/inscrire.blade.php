{{--
    AUCUN NOM. Un code, une langue, une situation. Le repère que saisit le
    facilitateur pour reconnaître la personne au pointage reste sur son
    appareil : il n'entre jamais dans la file d'envoi.
--}}
<x-layouts.kit titre="Inscrire un parent">

    <div x-data="inscrireParent" x-cloak>

        <template x-if="! cohorte">
            <div class="panel">
                <h5 class="font-semibold text-lg dark:text-white-light">Aucun paquet de cohorte</h5>
                <p class="text-white-dark mt-1">Téléchargez d'abord votre paquet.</p>
                <a href="/kit" class="btn btn-outline-primary mt-5 inline-flex">Revenir à mon kit</a>
            </div>
        </template>

        <template x-if="cohorte && ! resultat">
            <div>
                <div class="mb-6">
                    <h2 class="text-2xl font-bold dark:text-white-light">Inscrire un parent</h2>
                    <p class="text-white-dark mt-1 max-w-prose">
                        Dans <span class="font-semibold text-black dark:text-white-light" x-text="cohorte.libelle"></span>.
                        Son code s'affichera une seule fois : notez-le ou remettez-le tout de suite.
                    </p>
                </div>

                {{-- Le plafond se signale, il ne bloque pas. On n'a jamais
                     renvoyé quelqu'un parce qu'un chiffre était atteint. --}}
                <div class="flex items-center rounded border border-warning bg-warning-light p-3.5 text-warning mb-6"
                     x-show="depassePlafond">
                    <span>
                        La cohorte a atteint son plafond de
                        <span class="chiffre" x-text="cohorte.ratio_max"></span>.
                        Vous pouvez inscrire cette personne ; le dépassement sera signalé
                        à votre délégation.
                    </span>
                </div>

                <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

                    <div class="panel lg:col-span-2 space-y-5">

                        <div>
                            <label>Langue</label>
                            <div class="flex flex-wrap gap-2">
                                <template x-for="l in [
                                    { valeur: 'bulu', libelle: 'Bulu' },
                                    { valeur: 'fr', libelle: 'Français' },
                                    { valeur: 'en', libelle: 'English' },
                                ]" x-bind:key="l.valeur">
                                    <button type="button" class="btn btn-sm" x-on:click="langue = l.valeur"
                                            x-bind:aria-pressed="langue === l.valeur"
                                            x-bind:class="langue === l.valeur ? 'btn-primary' : 'btn-outline-primary'"
                                            x-text="l.libelle"></button>
                                </template>
                            </div>
                            <span class="text-white-dark text-[11px] inline-block mt-1">
                                La langue est celle du parent, pas celle de la région.
                            </span>
                        </div>

                        <div>
                            <label>Situation</label>
                            <div class="flex flex-wrap gap-2">
                                <template x-for="s in [
                                    { valeur: 'union', libelle: 'En union' },
                                    { valeur: 'seul', libelle: 'Seul·e' },
                                    { valeur: 'non_renseigne', libelle: 'Ne dit pas' },
                                ]" x-bind:key="s.valeur">
                                    <button type="button" class="btn btn-sm" x-on:click="statut = s.valeur"
                                            x-bind:aria-pressed="statut === s.valeur"
                                            x-bind:class="statut === s.valeur ? 'btn-primary' : 'btn-outline-primary'"
                                            x-text="s.libelle"></button>
                                </template>
                            </div>
                        </div>

                        <div>
                            <label>Revenu</label>
                            <div class="flex flex-wrap gap-2">
                                <template x-for="r in [
                                    { valeur: 'regulier', libelle: 'Régulier' },
                                    { valeur: 'irregulier', libelle: 'Irrégulier' },
                                    { valeur: 'aucun', libelle: 'Aucun' },
                                    { valeur: 'non_renseigne', libelle: 'Ne dit pas' },
                                ]" x-bind:key="r.valeur">
                                    <button type="button" class="btn btn-sm" x-on:click="revenu = r.valeur"
                                            x-bind:aria-pressed="revenu === r.valeur"
                                            x-bind:class="revenu === r.valeur ? 'btn-primary' : 'btn-outline-primary'"
                                            x-text="r.libelle"></button>
                                </template>
                            </div>
                        </div>

                        {{-- Ce n'est pas décoratif : c'est la raison d'être des
                             règles de l'espace parent, et la raison pour laquelle
                             cet espace reste secondaire. --}}
                        <div>
                            <label>Téléphone</label>
                            <div class="flex flex-wrap gap-2">
                                <template x-for="t in [
                                    { valeur: false, libelle: 'À elle seule' },
                                    { valeur: true, libelle: 'Partagé au foyer' },
                                ]" x-bind:key="String(t.valeur)">
                                    <button type="button" class="btn btn-sm" x-on:click="telephonePartage = t.valeur"
                                            x-bind:aria-pressed="telephonePartage === t.valeur"
                                            x-bind:class="telephonePartage === t.valeur ? 'btn-primary' : 'btn-outline-primary'"
                                            x-text="t.libelle"></button>
                                </template>
                            </div>
                        </div>

                        <div class="flex flex-wrap gap-3 !mt-8">
                            <button type="button" class="btn btn-primary" x-on:click="valider()"
                                    x-bind:disabled="occupe">
                                <span x-text="occupe ? 'Un instant…' : 'Inscrire'">Inscrire</span>
                            </button>
                            <a href="/kit" class="btn btn-outline-primary">Annuler</a>
                        </div>
                    </div>

                    <div class="panel h-fit space-y-5">
                        <div>
                            <p class="text-xs font-semibold uppercase tracking-wider text-white-dark">Code parent</p>
                            <p class="chiffre text-2xl font-bold mt-1" x-text="codeParent"></p>
                            <p class="text-white-dark text-[11px] mt-1">
                                Attribué automatiquement, dans la suite de votre cohorte.
                            </p>
                        </div>

                        <div>
                            <label for="repere">
                                Votre repère
                                <span class="text-white-dark font-normal">— reste sur ce téléphone</span>
                            </label>
                            <input id="repere" x-model="repere" type="text" maxlength="40"
                                   placeholder="Odile, marché" class="form-input">
                            <span class="text-white-dark text-[11px] inline-block mt-1">
                                Pour la reconnaître au pointage. Ce mot ne quitte jamais votre
                                appareil et n'est jamais envoyé au serveur.
                            </span>
                        </div>
                    </div>
                </div>
            </div>
        </template>

        {{-- Le code à remettre. Grand, en Plex Mono, lisible à bout de bras :
             il va être recopié à la main sur un bout de papier. --}}
        <template x-if="resultat">
            <div class="max-w-xl">
                <h2 class="text-2xl font-bold dark:text-white-light mb-6">À remettre maintenant</h2>

                <div class="panel !bg-noir !text-blanc">
                    <p class="intitule">Code parent</p>
                    <p class="chiffre mt-1 text-3xl tracking-[0.15em]" x-text="resultat.code_parent"></p>

                    <p class="intitule mt-6">Code à 4 chiffres</p>
                    <p class="chiffre mt-1 text-5xl tracking-[0.3em]" x-text="resultat.code_acces"></p>
                </div>

                <div class="panel border-l-4 border-danger mt-6">
                    <p>
                        Ce code ne sera plus affiché. Écrivez-le et donnez-le à la personne :
                        il lui ouvre l'espace parent depuis son téléphone. Sans téléphone, son
                        dossier existe quand même et elle est pointée comme les autres.
                    </p>
                </div>

                <div class="flex flex-wrap gap-3 mt-6">
                    <button type="button" class="btn btn-primary" x-on:click="recommencer()">
                        Inscrire quelqu'un d'autre
                    </button>
                    <a href="/kit" class="btn btn-outline-primary">Revenir à mon kit</a>
                </div>
            </div>
        </template>
    </div>
</x-layouts.kit>
