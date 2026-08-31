{{--
    LE DÉCLARÉ. Cet écran n'affiche RIEN de ce que l'outil a observé : ni les
    séquences ouvertes, ni les durées réelles, ni le moindre pré-remplissage.
    Souffler sa réponse au facilitateur rendrait les deux sources dépendantes,
    et l'écart déclaré/observé ne mesurerait plus rien.
--}}
<x-layouts.kit titre="Fiche de fidélité">

    <div x-data="fidelite" x-cloak>

        <template x-if="! seance">
            <div class="panel">
                <h5 class="font-semibold text-lg dark:text-white-light">Aucune séance à renseigner</h5>
                <a href="/kit" class="btn btn-outline-primary mt-5 inline-flex">Revenir à mon kit</a>
            </div>
        </template>

        <template x-if="seance && ! accessible">
            <div class="panel">
                <h5 class="font-semibold text-lg dark:text-white-light">La fiche s'ouvre à la fin de la séance</h5>
                <p class="text-white-dark mt-1">On ne raconte pas une séance pendant qu'on l'anime.</p>
                <a class="btn btn-outline-primary mt-5 inline-flex"
                   x-bind:href="'/kit/seance?module=' + seance.module_id" href="#">Reprendre le déroulé</a>
            </div>
        </template>

        <template x-if="seance && accessible">
            <div>
                <div class="mb-6">
                    <h2 class="text-2xl font-bold dark:text-white-light">Comment ça s'est passé ?</h2>
                    <p class="text-white-dark mt-1 max-w-prose">
                        Une question par séquence. Répondez de mémoire, sans rien vérifier :
                        c'est votre point de vue qui nous intéresse.
                    </p>
                </div>

                <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                    <template x-for="s in sequences" x-bind:key="s.id">
                        <div class="panel h-full">
                            <h5 class="font-semibold text-lg dark:text-white-light">
                                <span class="chiffre" x-text="s.ordre + '.'"></span>
                                <span x-text="s.titre"></span>
                            </h5>

                            <p class="mt-4">Avez-vous réalisé cette séquence ?</p>

                            <div class="flex gap-2 mt-2" role="group">
                                <button type="button" class="btn flex-1" x-on:click="repondre(s.id, true)"
                                        x-bind:class="reponses[s.id].realisee === true ? 'btn-primary' : 'btn-outline-primary'"
                                        x-bind:aria-pressed="reponses[s.id].realisee === true">Oui</button>

                                <button type="button" class="btn flex-1" x-on:click="repondre(s.id, false)"
                                        x-bind:class="reponses[s.id].realisee === false ? 'btn-primary' : 'btn-outline-primary'"
                                        x-bind:aria-pressed="reponses[s.id].realisee === false">Non</button>
                            </div>

                            {{-- La note ne s'affiche que si la séquence a eu lieu :
                                 on ne demande pas de noter ce qui n'a pas été fait. --}}
                            <div x-cloak x-show="reponses[s.id].realisee === true" class="mt-5">
                                <p>Comment l'avez-vous trouvée ?</p>

                                <div class="flex gap-2 mt-2" role="group">
                                    <template x-for="n in [1, 2, 3]" x-bind:key="n">
                                        <button type="button" class="btn btn-sm flex-1" x-on:click="noter(s.id, n)"
                                                x-bind:class="reponses[s.id].note === n ? 'btn-primary' : 'btn-outline-primary'"
                                                x-bind:aria-pressed="reponses[s.id].note === n"
                                                x-text="{ 1: 'Difficile', 2: 'Correcte', 3: 'Bien passée' }[n]"></button>
                                    </template>
                                </div>
                            </div>

                            <div x-cloak x-show="reponses[s.id].realisee !== null" class="mt-5">
                                <label x-bind:for="'c-' + s.id">Une remarque ?</label>
                                <input type="text" class="form-input" x-bind:id="'c-' + s.id"
                                       x-model="reponses[s.id].commentaire" maxlength="200">
                            </div>
                        </div>
                    </template>
                </div>

                {{-- Le champ libre de fin de séance. Court, et une seule
                     question : celle qui apprend quelque chose. --}}
                <div class="panel border-l-4 border-warning mt-6">
                    <label for="bilan" class="text-lg font-semibold dark:text-white-light">
                        Qu'est-ce qui a le moins bien marché ?
                    </label>
                    <input id="bilan" type="text" class="form-input mt-2" x-model="bilan" maxlength="280">
                </div>

                <div class="panel mt-6">
                    <button type="button" class="btn btn-primary w-full" x-on:click="valider()">
                        Enregistrer la fiche
                    </button>

                    <p class="text-white-dark text-center mt-3">
                        <span class="chiffre" x-text="repondues"></span> séquence(s) sur
                        <span class="chiffre" x-text="sequences.length"></span> renseignée(s).
                        Ce qui reste vide ne sera pas envoyé.
                    </p>
                </div>
            </div>
        </template>
    </div>
</x-layouts.kit>
