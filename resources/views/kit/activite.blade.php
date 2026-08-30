{{--
    Enregistrer une activité de terrain.

    Le programme ne se résume pas aux séances de cohorte. Une causerie sous
    l'arbre, un porte-à-porte, une sensibilisation au marché comptent autant —
    ne pas les enregistrer revient à conclure qu'elles n'ont pas eu lieu, puis
    à écrire dans un rapport qu'un facilitateur n'était pas actif.

    La répartition par sexe et le nombre de participants en situation de
    handicap sont demandés à chaque fois. C'est ce qui rend le critère
    « handicap » mesurable plutôt que déclaratif.
--}}
<x-layouts.kit titre="Enregistrer une activité">

    <div x-data="activiteTerrain" x-cloak>

        <template x-if="! enregistre">
            <div class="space-y-5">

                <div>
                    <h1 class="text-3xl">Ce que j'ai fait</h1>
                    <p class="mt-2 text-gris-texte">
                        Séance ou pas, tout compte. Renseignez au plus près de ce que vous
                        avez vu.
                    </p>
                </div>

                <fieldset>
                    <legend class="intitule">Type d'activité</legend>
                    <div class="mt-2 grid grid-cols-1 gap-2 sm:grid-cols-2">
                        <template x-for="t in types" x-bind:key="t.valeur">
                            <button type="button" x-on:click="type = t.valeur"
                                    x-bind:aria-pressed="type === t.valeur"
                                    x-bind:class="type === t.valeur ? 'bg-noir text-blanc' : 'bg-blanc text-noir'"
                                    class="min-h-tactile rounded-net border-2 border-noir px-4 text-base font-semibold [font-family:var(--font-titre)]"
                                    x-text="t.libelle"></button>
                        </template>
                    </div>
                </fieldset>

                <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                    <div>
                        <label for="date" class="intitule block">Date</label>
                        <input id="date" x-model="date" type="date"
                               class="chiffre mt-1 min-h-tactile w-full rounded-net border-2 border-noir px-3 text-base">
                    </div>
                    <div>
                        <label for="duree" class="intitule block">Durée (minutes)</label>
                        <input id="duree" x-model.number="duree" type="number" min="5" max="480" step="5"
                               inputmode="numeric"
                               class="chiffre mt-1 min-h-tactile w-full rounded-net border-2 border-noir px-3 text-base">
                    </div>
                </div>

                <div>
                    <label for="lieu" class="intitule block">Où</label>
                    <input id="lieu" x-model="lieu" type="text" maxlength="80"
                           placeholder="sous le manguier du marché"
                           class="mt-1 min-h-tactile w-full rounded-net border-2 border-noir px-3 text-base">
                    <p class="mt-1 text-base text-gris-texte">
                        Un repère de lieu, jamais une adresse.
                    </p>
                </div>

                {{-- Une réunion de groupe fait avancer la continuité du dossier :
                     c'est le seul endroit d'où « dernière réunion » bouge. --}}
                <div x-show="estReunionGsp && groupes.length > 0">
                    <label for="gsp" class="intitule block">Quel groupe</label>
                    <select id="gsp" x-model="gspUuid"
                            class="mt-1 min-h-tactile w-full rounded-net border-2 border-noir px-3 text-base">
                        <option value="">Choisir…</option>
                        <template x-for="g in groupes" x-bind:key="g.uuid">
                            <option x-bind:value="g.uuid" x-text="g.libelle"></option>
                        </template>
                    </select>
                </div>

                <div class="rounded-carte border border-ligne p-4">
                    <p class="intitule">Qui était là</p>

                    <div class="mt-3 grid grid-cols-2 gap-4">
                        <div>
                            <label for="touches" class="intitule block text-xs">Personnes touchées</label>
                            <input id="touches" x-model.number="touches" type="number" min="0" max="500"
                                   inputmode="numeric"
                                   class="chiffre mt-1 min-h-tactile w-full rounded-net border-2 border-noir px-3 text-2xl">
                        </div>
                        <div>
                            <label for="handicap" class="intitule block text-xs">
                                Dont en situation de handicap
                            </label>
                            <input id="handicap" x-model.number="handicap" type="number" min="0" max="500"
                                   inputmode="numeric"
                                   class="chiffre mt-1 min-h-tactile w-full rounded-net border-2 border-noir px-3 text-2xl">
                        </div>
                        <div>
                            <label for="hommes" class="intitule block text-xs">Hommes</label>
                            <input id="hommes" x-model.number="hommes" type="number" min="0" max="500"
                                   inputmode="numeric"
                                   class="chiffre mt-1 min-h-tactile w-full rounded-net border-2 border-noir px-3 text-2xl">
                        </div>
                        <div>
                            <label for="femmes" class="intitule block text-xs">Femmes</label>
                            <input id="femmes" x-model.number="femmes" type="number" min="0" max="500"
                                   inputmode="numeric"
                                   class="chiffre mt-1 min-h-tactile w-full rounded-net border-2 border-noir px-3 text-2xl">
                        </div>
                    </div>

                    <p class="mt-3 text-base" x-show="nonRenseigne > 0 && ! incoherent">
                        <span class="chiffre" x-text="nonRenseigne"></span>
                        personnes sans répartition. C'est accepté : on ne complète pas au hasard.
                    </p>

                    <p class="mt-3 rounded-carte bg-jaune-sourd px-3 py-2 text-base" x-show="incoherent">
                        Les nombres ne s'additionnent pas. Hommes et femmes ne peuvent pas
                        dépasser le total, ni les participants en situation de handicap.
                    </p>
                </div>

                <div>
                    <label for="commentaire" class="intitule block">
                        Un mot <span class="text-gris-texte">— facultatif</span>
                    </label>
                    <textarea id="commentaire" x-model="commentaire" rows="2" maxlength="500"
                              class="mt-1 w-full rounded-net border-2 border-noir px-3 py-2 text-base"></textarea>
                    <p class="mt-1 text-base text-gris-texte">
                        Sur le déroulé, jamais sur une personne. Aucun nom.
                    </p>
                </div>

                <div class="flex flex-wrap gap-2">
                    <x-mvoe.bouton x-on:click="valider()" x-bind:disabled="! peutValider || occupe">
                        <span x-text="occupe ? 'Un instant…' : 'Enregistrer'">Enregistrer</span>
                    </x-mvoe.bouton>
                    <x-mvoe.bouton variante="second" href="/kit">Annuler</x-mvoe.bouton>
                </div>
            </div>
        </template>

        <template x-if="enregistre">
            <div class="space-y-5">
                <x-mvoe.carte rappel>
                    <p class="text-xl [font-family:var(--font-titre)] font-semibold">
                        Activité enregistrée.
                    </p>
                    <p class="mt-2 text-base">
                        Elle partira dès que vous retrouverez du réseau. Le compteur en haut
                        vous dit ce qui attend.
                    </p>
                </x-mvoe.carte>

                <div class="flex flex-wrap gap-2">
                    <x-mvoe.bouton x-on:click="recommencer()">En enregistrer une autre</x-mvoe.bouton>
                    <x-mvoe.bouton variante="second" href="/kit">Revenir à mon kit</x-mvoe.bouton>
                </div>
            </div>
        </template>
    </div>
</x-layouts.kit>
