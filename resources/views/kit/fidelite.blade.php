{{--
    La fiche de fidélité — LE DÉCLARÉ.

    Elle ne s'ouvre qu'après la séance, jamais pendant.

    Cet écran n'affiche RIEN de ce que l'outil a observé : ni les séquences
    ouvertes, ni les durées réelles, ni le moindre pré-remplissage. Souffler sa
    réponse au facilitateur rendrait les deux sources dépendantes l'une de
    l'autre, et l'écart déclaré/observé — la mesure qui fait tout l'intérêt du
    système — ne mesurerait plus rien.

    Une question par séquence, plus un champ libre court.
--}}
<x-layouts.kit titre="Fiche de fidélité">

    <div x-data="fidelite" x-cloak>

        <template x-if="! seance">
            <x-mvoe.vide>
                Aucune séance à renseigner.
                <x-slot:action>
                    <x-mvoe.bouton variante="second" href="/kit">Revenir à mon kit</x-mvoe.bouton>
                </x-slot:action>
            </x-mvoe.vide>
        </template>

        <template x-if="seance && ! accessible">
            <x-mvoe.vide>
                La fiche s'ouvre à la fin de la séance.
                <x-slot:action>
                    <x-mvoe.bouton variante="second"
                                   x-bind:href="'/kit/seance?module=' + seance.module_id"
                                   href="#">Reprendre le déroulé</x-mvoe.bouton>
                </x-slot:action>
            </x-mvoe.vide>
        </template>

        <template x-if="seance && accessible">
            <div class="space-y-5">

                <div>
                    <h1 class="text-3xl">Comment ça s'est passé ?</h1>
                    <p class="mt-2 text-gris-texte">
                        Une question par séquence. Répondez de mémoire, sans rien vérifier :
                        c'est votre point de vue qui nous intéresse.
                    </p>
                </div>

                <template x-for="s in sequences" x-bind:key="s.id">
                    <div class="rounded-carte border border-ligne p-4">
                        <p class="text-lg font-semibold [font-family:var(--font-titre)]">
                            <span class="chiffre" x-text="s.ordre + '.'"></span>
                            <span x-text="s.titre"></span>
                        </p>

                        <p class="mt-3 text-base">Avez-vous réalisé cette séquence ?</p>

                        <div class="mt-2 flex gap-2" role="group">
                            <button type="button" x-on:click="repondre(s.id, true)"
                                    class="min-h-tactile flex-1 rounded-net border-2 border-noir px-3 text-base font-semibold [font-family:var(--font-titre)]"
                                    x-bind:class="reponses[s.id].realisee === true ? 'bg-jaune' : 'bg-blanc'"
                                    x-bind:aria-pressed="reponses[s.id].realisee === true">Oui</button>

                            <button type="button" x-on:click="repondre(s.id, false)"
                                    class="min-h-tactile flex-1 rounded-net border-2 border-noir px-3 text-base font-semibold [font-family:var(--font-titre)]"
                                    x-bind:class="reponses[s.id].realisee === false ? 'bg-jaune' : 'bg-blanc'"
                                    x-bind:aria-pressed="reponses[s.id].realisee === false">Non</button>
                        </div>

                        {{-- La note de qualité ne s'affiche que si la séquence a
                             eu lieu : on ne demande pas de noter ce qui n'a pas
                             été fait. --}}
                        <div x-cloak x-show="reponses[s.id].realisee === true" class="mt-3">
                            <p class="text-base">Comment l'avez-vous trouvée ?</p>

                            <div class="mt-2 flex gap-2" role="group">
                                <template x-for="n in [1, 2, 3]" x-bind:key="n">
                                    <button type="button" x-on:click="noter(s.id, n)"
                                            class="min-h-tactile flex-1 rounded-net border-2 border-noir px-3 text-base font-semibold [font-family:var(--font-titre)]"
                                            x-bind:class="reponses[s.id].note === n ? 'bg-jaune' : 'bg-blanc'"
                                            x-bind:aria-pressed="reponses[s.id].note === n"
                                            x-text="{ 1: 'Difficile', 2: 'Correcte', 3: 'Bien passée' }[n]"></button>
                                </template>
                            </div>
                        </div>

                        <div x-cloak x-show="reponses[s.id].realisee !== null" class="mt-3">
                            <label class="intitule block" x-bind:for="'c-' + s.id">
                                Une remarque ?
                            </label>
                            <input type="text" x-bind:id="'c-' + s.id"
                                   x-model="reponses[s.id].commentaire"
                                   maxlength="200"
                                   class="mt-1 min-h-tactile w-full rounded-net border-2 border-noir px-3 text-base">
                        </div>
                    </div>
                </template>

                {{-- Le champ libre de fin de séance. Court, et une seule
                     question : celle qui apprend quelque chose. --}}
                <div class="rounded-carte border border-ligne bg-jaune-sourd p-4">
                    <label for="bilan" class="text-lg font-semibold [font-family:var(--font-titre)]">
                        Qu'est-ce qui a le moins bien marché ?
                    </label>
                    <input id="bilan" type="text" x-model="bilan" maxlength="280"
                           class="mt-2 min-h-tactile w-full rounded-net border-2 border-noir bg-blanc px-3 text-base">
                </div>

                <div class="space-y-2">
                    <x-mvoe.bouton class="w-full" x-on:click="valider()">
                        Enregistrer la fiche
                    </x-mvoe.bouton>

                    <p class="text-center text-sm text-gris-texte">
                        <span x-text="repondues"></span> séquence(s) sur
                        <span x-text="sequences.length"></span> renseignée(s).
                        Ce qui reste vide ne sera pas envoyé.
                    </p>
                </div>
            </div>
        </template>
    </div>
</x-layouts.kit>
