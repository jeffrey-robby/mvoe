{{--
    Inscrire un parent, sur le terrain.

    Le brief demande deux voies d'entrée pour un parent et interdit par ailleurs
    tout écran d'inscription publique. Les deux tiennent ensemble parce que
    l'inscription n'est jamais faite par un visiteur anonyme : le facilitateur
    crée le dossier et remet le code en main propre. Le parent l'ACTIVE ensuite
    en se connectant — s'il a un téléphone. Sinon le dossier existe quand même,
    et il est pointé en séance comme les autres.

    AUCUN NOM. Un code, une langue, une situation. Le repère que saisit le
    facilitateur pour reconnaître la personne au pointage reste sur son
    appareil : il n'entre jamais dans la file d'envoi.

    Écran de terrain : cibles 48 px, corps 17 px, aucune couleur du template.
--}}
<x-layouts.kit titre="Inscrire un parent">

    <div x-data="inscrireParent" x-cloak>

        <template x-if="! cohorte">
            <x-mvoe.vide>
                Téléchargez d'abord votre paquet de cohorte.
                <x-slot:action>
                    <x-mvoe.bouton variante="second" href="/kit">Revenir à mon kit</x-mvoe.bouton>
                </x-slot:action>
            </x-mvoe.vide>
        </template>

        {{-- ------------------------------------------------------------ --}}
        {{-- Le formulaire.                                                --}}
        <template x-if="cohorte && ! resultat">
            <div class="space-y-5">

                <div>
                    <h1 class="text-3xl">Inscrire un parent</h1>
                    <p class="mt-2 text-gris-texte">
                        Dans <span x-text="cohorte.libelle"></span>.
                        Son code s'affichera une fois : notez-le ou remettez-le tout de suite.
                    </p>
                </div>

                {{-- Le plafond se signale, il ne bloque pas. On n'a jamais
                     renvoyé quelqu'un parce qu'un chiffre était atteint. --}}
                <p x-show="depassePlafond"
                   class="rounded-carte bg-jaune-sourd px-4 py-3 text-base">
                    La cohorte a atteint son plafond de
                    <span class="chiffre" x-text="cohorte.ratio_max"></span>.
                    Vous pouvez inscrire cette personne ; le dépassement sera signalé
                    à votre délégation.
                </p>

                <div class="rounded-carte border border-ligne p-4">
                    <p class="intitule">Code parent</p>
                    <p class="chiffre mt-1 text-2xl" x-text="codeParent"></p>
                    <p class="mt-2 text-base text-gris-texte">
                        Attribué automatiquement, dans la suite de votre cohorte.
                    </p>
                </div>

                <div>
                    <label for="repere" class="intitule block">
                        Votre repère <span class="text-gris-texte">— reste sur ce téléphone</span>
                    </label>
                    <input id="repere" x-model="repere" type="text" maxlength="40"
                           placeholder="Odile, marché"
                           class="mt-1 min-h-tactile w-full rounded-net border-2 border-noir px-3 text-base">
                    <p class="mt-1 text-base text-gris-texte">
                        Pour la reconnaître au pointage. Ce mot ne quitte jamais votre appareil
                        et n'est jamais envoyé au serveur.
                    </p>
                </div>

                <fieldset>
                    <legend class="intitule">Langue</legend>
                    <div class="mt-2 flex flex-wrap gap-2">
                        <template x-for="l in [
                            { valeur: 'bulu', libelle: 'Bulu' },
                            { valeur: 'fr', libelle: 'Français' },
                            { valeur: 'en', libelle: 'English' },
                        ]" x-bind:key="l.valeur">
                            <button type="button" x-on:click="langue = l.valeur"
                                    x-bind:aria-pressed="langue === l.valeur"
                                    x-bind:class="langue === l.valeur
                                        ? 'bg-noir text-blanc'
                                        : 'bg-blanc text-noir'"
                                    class="min-h-tactile rounded-net border-2 border-noir px-4 text-base font-semibold [font-family:var(--font-titre)]"
                                    x-text="l.libelle"></button>
                        </template>
                    </div>
                    <p class="mt-1 text-base text-gris-texte">
                        La langue est celle du parent, pas celle de la région.
                    </p>
                </fieldset>

                <fieldset>
                    <legend class="intitule">Situation</legend>
                    <div class="mt-2 flex flex-wrap gap-2">
                        <template x-for="s in [
                            { valeur: 'union', libelle: 'En union' },
                            { valeur: 'seul', libelle: 'Seul·e' },
                            { valeur: 'non_renseigne', libelle: 'Ne dit pas' },
                        ]" x-bind:key="s.valeur">
                            <button type="button" x-on:click="statut = s.valeur"
                                    x-bind:aria-pressed="statut === s.valeur"
                                    x-bind:class="statut === s.valeur
                                        ? 'bg-noir text-blanc'
                                        : 'bg-blanc text-noir'"
                                    class="min-h-tactile rounded-net border-2 border-noir px-4 text-base font-semibold [font-family:var(--font-titre)]"
                                    x-text="s.libelle"></button>
                        </template>
                    </div>
                </fieldset>

                <fieldset>
                    <legend class="intitule">Revenu</legend>
                    <div class="mt-2 flex flex-wrap gap-2">
                        <template x-for="r in [
                            { valeur: 'regulier', libelle: 'Régulier' },
                            { valeur: 'irregulier', libelle: 'Irrégulier' },
                            { valeur: 'aucun', libelle: 'Aucun' },
                            { valeur: 'non_renseigne', libelle: 'Ne dit pas' },
                        ]" x-bind:key="r.valeur">
                            <button type="button" x-on:click="revenu = r.valeur"
                                    x-bind:aria-pressed="revenu === r.valeur"
                                    x-bind:class="revenu === r.valeur
                                        ? 'bg-noir text-blanc'
                                        : 'bg-blanc text-noir'"
                                    class="min-h-tactile rounded-net border-2 border-noir px-4 text-base font-semibold [font-family:var(--font-titre)]"
                                    x-text="r.libelle"></button>
                        </template>
                    </div>
                </fieldset>

                {{-- Ce n'est pas décoratif : c'est la raison d'être des règles
                     de l'espace parent, et la raison pour laquelle cet espace
                     reste secondaire. --}}
                <fieldset>
                    <legend class="intitule">Téléphone</legend>
                    <div class="mt-2 flex flex-wrap gap-2">
                        <template x-for="t in [
                            { valeur: false, libelle: 'À elle seule' },
                            { valeur: true, libelle: 'Partagé au foyer' },
                        ]" x-bind:key="String(t.valeur)">
                            <button type="button" x-on:click="telephonePartage = t.valeur"
                                    x-bind:aria-pressed="telephonePartage === t.valeur"
                                    x-bind:class="telephonePartage === t.valeur
                                        ? 'bg-noir text-blanc'
                                        : 'bg-blanc text-noir'"
                                    class="min-h-tactile rounded-net border-2 border-noir px-4 text-base font-semibold [font-family:var(--font-titre)]"
                                    x-text="t.libelle"></button>
                        </template>
                    </div>
                </fieldset>

                <div class="flex flex-wrap gap-2 pt-2">
                    <x-mvoe.bouton x-on:click="valider()" x-bind:disabled="occupe">
                        <span x-text="occupe ? 'Un instant…' : 'Inscrire'">Inscrire</span>
                    </x-mvoe.bouton>
                    <x-mvoe.bouton variante="second" href="/kit">Annuler</x-mvoe.bouton>
                </div>
            </div>
        </template>

        {{-- ------------------------------------------------------------ --}}
        {{-- Le code à remettre. Grand, en Plex Mono, lisible à bout de    --}}
        {{-- bras : il va être recopié à la main sur un bout de papier.    --}}
        <template x-if="resultat">
            <div class="space-y-5">

                <h1 class="text-3xl">À remettre maintenant</h1>

                <div class="rounded-carte bg-noir p-5 text-blanc">
                    <p class="intitule">Code parent</p>
                    <p class="chiffre mt-1 text-3xl tracking-[0.15em]"
                       x-text="resultat.code_parent"></p>

                    <p class="intitule mt-5">Code à 4 chiffres</p>
                    <p class="chiffre mt-1 text-5xl tracking-[0.3em]"
                       x-text="resultat.code_acces"></p>
                </div>

                <p class="rounded-carte border-2 border-noir px-4 py-3 text-base">
                    Ce code ne sera plus affiché. Écrivez-le et donnez-le à la personne :
                    il lui ouvre l'espace parent depuis son téléphone. Sans téléphone,
                    son dossier existe quand même et elle est pointée comme les autres.
                </p>

                <div class="flex flex-wrap gap-2">
                    <x-mvoe.bouton x-on:click="recommencer()">Inscrire quelqu'un d'autre</x-mvoe.bouton>
                    <x-mvoe.bouton variante="second" href="/kit">Revenir à mon kit</x-mvoe.bouton>
                </div>
            </div>
        </template>
    </div>
</x-layouts.kit>
