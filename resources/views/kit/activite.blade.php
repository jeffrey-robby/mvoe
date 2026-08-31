<x-layouts.kit titre="Enregistrer une activité">

    <div x-data="activiteTerrain" x-cloak>

        <template x-if="! enregistre">
            <div>
                <div class="mb-6">
                    <h2 class="text-2xl font-bold dark:text-white-light">Ce que j'ai fait</h2>
                    <p class="text-white-dark mt-1 max-w-prose">
                        Séance ou pas, tout compte. Une causerie sous l'arbre, un porte-à-porte,
                        une sensibilisation au marché : ne pas les enregistrer revient à conclure
                        qu'elles n'ont pas eu lieu.
                    </p>
                </div>

                <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

                    <div class="panel lg:col-span-2 space-y-5">

                        <div>
                            <label>Type d'activité</label>
                            <div class="flex flex-wrap gap-2">
                                <template x-for="t in types" x-bind:key="t.valeur">
                                    <button type="button" class="btn btn-sm" x-on:click="type = t.valeur"
                                            x-bind:aria-pressed="type === t.valeur"
                                            x-bind:class="type === t.valeur ? 'btn-primary' : 'btn-outline-primary'"
                                            x-text="t.libelle"></button>
                                </template>
                            </div>
                        </div>

                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-5">
                            <div>
                                <label for="date">Date</label>
                                <input id="date" x-model="date" type="date" class="form-input chiffre">
                            </div>
                            <div>
                                <label for="duree">Durée (minutes)</label>
                                <input id="duree" x-model.number="duree" type="number" min="5" max="480" step="5"
                                       inputmode="numeric" class="form-input chiffre">
                            </div>
                        </div>

                        <div>
                            <label for="lieu">Où</label>
                            <input id="lieu" x-model="lieu" type="text" maxlength="80"
                                   placeholder="sous le manguier du marché" class="form-input">
                            <span class="text-white-dark text-[11px] inline-block mt-1">
                                Un repère de lieu, jamais une adresse.
                            </span>
                        </div>

                        {{-- Une réunion de groupe fait avancer la continuité du
                             dossier : c'est le seul endroit d'où « dernière
                             réunion » bouge. --}}
                        <div x-show="estReunionGsp && groupes.length > 0">
                            <label for="gsp">Quel groupe</label>
                            <select id="gsp" x-model="gspUuid" class="form-select">
                                <option value="">Choisir…</option>
                                <template x-for="g in groupes" x-bind:key="g.uuid">
                                    <option x-bind:value="g.uuid" x-text="g.libelle"></option>
                                </template>
                            </select>
                        </div>

                        <div>
                            <label for="commentaire">
                                Un mot <span class="text-white-dark font-normal">— facultatif</span>
                            </label>
                            <textarea id="commentaire" x-model="commentaire" rows="2" maxlength="500"
                                      class="form-textarea"></textarea>
                            <span class="text-white-dark text-[11px] inline-block mt-1">
                                Sur le déroulé, jamais sur une personne. Aucun nom.
                            </span>
                        </div>

                        <div class="flex flex-wrap gap-3 !mt-8">
                            <button type="button" class="btn btn-primary" x-on:click="valider()"
                                    x-bind:disabled="! peutValider || occupe">
                                <span x-text="occupe ? 'Un instant…' : 'Enregistrer'">Enregistrer</span>
                            </button>
                            <a href="/kit" class="btn btn-outline-primary">Annuler</a>
                        </div>
                    </div>

                    {{-- La répartition par sexe et le nombre de participants en
                         situation de handicap sont demandés à chaque fois. C'est
                         ce qui rend le critère « handicap » mesurable plutôt que
                         déclaratif. --}}
                    <div class="panel h-fit">
                        <h5 class="font-semibold text-lg dark:text-white-light mb-5">Qui était là</h5>

                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <label for="touches" class="text-xs uppercase tracking-wider text-white-dark">Personnes touchées</label>
                                <input id="touches" x-model.number="touches" type="number" min="0" max="500"
                                       inputmode="numeric" class="form-input chiffre form-input-lg">
                            </div>
                            <div>
                                <label for="handicap" class="text-xs uppercase tracking-wider text-white-dark">Dont en situation de handicap</label>
                                <input id="handicap" x-model.number="handicap" type="number" min="0" max="500"
                                       inputmode="numeric" class="form-input chiffre form-input-lg">
                            </div>
                            <div>
                                <label for="hommes" class="text-xs uppercase tracking-wider text-white-dark">Hommes</label>
                                <input id="hommes" x-model.number="hommes" type="number" min="0" max="500"
                                       inputmode="numeric" class="form-input chiffre form-input-lg">
                            </div>
                            <div>
                                <label for="femmes" class="text-xs uppercase tracking-wider text-white-dark">Femmes</label>
                                <input id="femmes" x-model.number="femmes" type="number" min="0" max="500"
                                       inputmode="numeric" class="form-input chiffre form-input-lg">
                            </div>
                        </div>

                        <p class="text-white-dark mt-4" x-show="nonRenseigne > 0 && ! incoherent">
                            <span class="chiffre" x-text="nonRenseigne"></span>
                            personnes sans répartition. C'est accepté : on ne complète pas au hasard.
                        </p>

                        <div class="flex items-center rounded border border-warning bg-warning-light p-3.5 text-warning mt-4"
                             x-show="incoherent">
                            <span>
                                Les nombres ne s'additionnent pas. Hommes et femmes ne peuvent pas
                                dépasser le total, ni les participants en situation de handicap.
                            </span>
                        </div>
                    </div>
                </div>
            </div>
        </template>

        <template x-if="enregistre">
            <div class="panel max-w-xl">
                <h5 class="font-semibold text-lg dark:text-white-light">Activité enregistrée</h5>
                <p class="text-white-dark mt-1">
                    Elle partira dès que vous retrouverez du réseau. Le compteur en haut de
                    l'écran vous dit ce qui attend.
                </p>

                <div class="flex flex-wrap gap-3 mt-5">
                    <button type="button" class="btn btn-primary" x-on:click="recommencer()">
                        En enregistrer une autre
                    </button>
                    <a href="/kit" class="btn btn-outline-primary">Revenir à mon kit</a>
                </div>
            </div>
        </template>
    </div>
</x-layouts.kit>
