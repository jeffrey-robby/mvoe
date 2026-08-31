<x-layouts.delegation titre="Enregistrer un facilitateur">

    <div x-data="enregistrerFacilitateur" x-cloak>

        <ul class="flex space-x-2 rtl:space-x-reverse sans-impression">
            <li>
                <a href="/superviseur" class="text-primary hover:underline">Registre</a>
            </li>
            <li class="before:content-['/'] ltr:before:mr-1 rtl:before:ml-1">
                <span>Enregistrer un facilitateur</span>
            </li>
        </ul>

        <div class="pt-5">

            <template x-if="niveau && ! peutEnregistrer">
                <div class="max-w-2xl">
                    <h2 class="text-2xl font-bold dark:text-white-light mb-5">Enregistrer un facilitateur</h2>

                    <div class="panel border-l-4 border-warning">
                        <p>
                            Un facilitateur est enregistré par la délégation d'arrondissement où il anime,
                            jamais depuis un niveau supérieur : c'est elle qui l'a vu travailler et qui lui
                            remettra ses identifiants en main propre.
                        </p>
                        <p class="text-white-dark mt-2">
                            Votre compte couvre <span class="font-semibold" x-text="portee"></span>.
                            Vous en lisez le registre et le rapport.
                        </p>
                    </div>

                    <a href="/superviseur" class="btn btn-outline-primary mt-5">Revenir au registre</a>
                </div>
            </template>

            <template x-if="peutEnregistrer && ! resultat">
                <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

                    <div class="panel lg:col-span-2">
                        <div class="mb-5">
                            <h5 class="font-semibold text-lg dark:text-white-light">Le dossier</h5>
                            <p class="text-white-dark mt-1">
                                Il sera rattaché à votre arrondissement. Ni l'arrondissement ni le mot de
                                passe ne se saisissent ici : le premier est le vôtre, le second est
                                généré par le serveur.
                            </p>
                        </div>

                        <div x-show="erreur" class="mb-5 flex items-center rounded border border-warning bg-warning-light p-3.5 text-warning dark:bg-warning-dark-light">
                            <span x-text="erreur"></span>
                        </div>

                        <form x-on:submit.prevent="valider()" class="space-y-5">
                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-5">
                                <div>
                                    <label for="nom">Nom et prénom</label>
                                    <input id="nom" x-model="nom" type="text" required
                                           placeholder="Ateba Marie-Claire" class="form-input">
                                    <span x-show="erreurs.nom" x-text="erreurs.nom?.[0]"
                                          class="text-danger text-[11px] inline-block mt-1"></span>
                                </div>

                                <div>
                                    <label for="tel">Téléphone</label>
                                    <input id="tel" x-model="telephone" type="tel" inputmode="tel" required
                                           placeholder="699 00 00 00" class="form-input chiffre">
                                    <span x-show="erreurs.telephone" x-text="erreurs.telephone?.[0]"
                                          class="text-danger text-[11px] inline-block mt-1"></span>
                                </div>
                            </div>

                            <div>
                                <label for="email">
                                    Adresse e-mail
                                    <span class="text-white-dark font-normal">— facultative</span>
                                </label>
                                <input id="email" x-model="email" type="email" class="form-input">
                                <span class="text-white-dark text-[11px] inline-block mt-1">
                                    Laissez vide : elle sera dérivée de son nom.
                                </span>
                            </div>

                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-5">
                                <div>
                                    <label for="type">Type juridique</label>
                                    <select id="type" x-model="typeJuridique" required class="form-select">
                                        <option value="">Choisir…</option>
                                        <template x-for="t in types" x-bind:key="t.valeur">
                                            <option x-bind:value="t.valeur" x-text="t.libelle"></option>
                                        </template>
                                    </select>
                                </div>

                                <div>
                                    <label for="formation">Date de formation initiale</label>
                                    <input id="formation" x-model="dateFormation" type="date" required
                                           class="form-input chiffre">
                                </div>
                            </div>

                            <div>
                                <label for="orga">
                                    Organisation de rattachement
                                    <span class="text-white-dark font-normal">— facultative</span>
                                </label>
                                <input id="orga" x-model="organisation" type="text"
                                       placeholder="Association des femmes d'Ebolowa" class="form-input">
                            </div>

                            <button type="submit" class="btn btn-primary !mt-6"
                                    x-bind:disabled="! peutValider || occupe">
                                <span x-text="occupe ? 'Un instant…' : 'Enregistrer le facilitateur'">Enregistrer le facilitateur</span>
                            </button>
                        </form>
                    </div>

                    <div class="panel h-fit">
                        <h5 class="font-semibold text-lg dark:text-white-light mb-5">Ce qui va se passer</h5>

                        <ul class="space-y-5">
                            <li class="flex gap-3">
                                <span class="chiffre w-7 h-7 shrink-0 rounded-full grid place-content-center bg-primary-light text-primary dark:bg-primary dark:text-primary-light text-xs font-bold">1</span>
                                <p class="text-white-dark">
                                    Le dossier est créé dans <span class="font-semibold text-black dark:text-white-light" x-text="portee"></span>,
                                    et nulle part ailleurs.
                                </p>
                            </li>
                            <li class="flex gap-3">
                                <span class="chiffre w-7 h-7 shrink-0 rounded-full grid place-content-center bg-primary-light text-primary dark:bg-primary dark:text-primary-light text-xs font-bold">2</span>
                                <p class="text-white-dark">
                                    Le serveur génère un code d'appareil et un mot de passe.
                                </p>
                            </li>
                            <li class="flex gap-3">
                                <span class="chiffre w-7 h-7 shrink-0 rounded-full grid place-content-center bg-primary-light text-primary dark:bg-primary dark:text-primary-light text-xs font-bold">3</span>
                                <p class="text-white-dark">
                                    Ils s'affichent <span class="font-semibold text-black dark:text-white-light">une seule fois</span>.
                                    Prévoyez de quoi les noter, ou imprimez la fiche.
                                </p>
                            </li>
                            <li class="flex gap-3">
                                <span class="chiffre w-7 h-7 shrink-0 rounded-full grid place-content-center bg-primary-light text-primary dark:bg-primary dark:text-primary-light text-xs font-bold">4</span>
                                <p class="text-white-dark">
                                    Vous les remettez en main propre. Le système n'envoie aucun message.
                                </p>
                            </li>
                        </ul>

                        <p class="text-white-dark text-[11px] mt-6 pt-5 border-t border-white-light dark:border-[#1b2e4b]">
                            Le type juridique n'est pas décoratif : c'est lui qui permettra de savoir quel
                            type de facilitateur reste actif le plus longtemps.
                        </p>
                    </div>
                </div>
            </template>

            <template x-if="resultat">
                <div class="max-w-3xl">
                    <div class="mb-5">
                        <h2 class="text-2xl font-bold dark:text-white-light">Identifiants à remettre</h2>
                        <p class="text-lg mt-1">
                            <span x-text="resultat.facilitateur.nom"></span>
                            <span class="text-white-dark"> · </span>
                            <span x-text="resultat.facilitateur.arrondissement"></span>
                        </p>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-6">
                        <div class="panel">
                            <h5 class="font-semibold text-lg dark:text-white-light mb-5">
                                Sur le terrain — pour ouvrir son kit
                            </h5>
                            <dl class="space-y-4">
                                <div>
                                    <dt class="text-xs font-semibold uppercase tracking-wider text-white-dark">Téléphone</dt>
                                    <dd class="chiffre text-2xl mt-1" x-text="resultat.identifiants.telephone"></dd>
                                </div>
                                <div>
                                    <dt class="text-xs font-semibold uppercase tracking-wider text-white-dark">Code d'appareil</dt>
                                    <dd class="chiffre text-3xl tracking-[0.3em] mt-1"
                                        x-text="resultat.identifiants.code_appareil"></dd>
                                </div>
                            </dl>
                        </div>

                        <div class="panel">
                            <h5 class="font-semibold text-lg dark:text-white-light mb-5">
                                Depuis un poste — accès classique
                            </h5>
                            <dl class="space-y-4">
                                <div>
                                    <dt class="text-xs font-semibold uppercase tracking-wider text-white-dark">Adresse e-mail</dt>
                                    <dd class="chiffre text-lg mt-1 break-all" x-text="resultat.identifiants.email"></dd>
                                </div>
                                <div>
                                    <dt class="text-xs font-semibold uppercase tracking-wider text-white-dark">Mot de passe</dt>
                                    <dd class="chiffre text-2xl tracking-[0.15em] mt-1"
                                        x-text="resultat.identifiants.mot_de_passe"></dd>
                                </div>
                            </dl>
                        </div>
                    </div>

                    <div class="panel border-l-4 border-danger mt-6">
                        <p x-text="resultat.avertissement"></p>
                    </div>

                    <div class="sans-impression flex flex-wrap gap-3 mt-6">
                        <button type="button" class="btn btn-primary" x-on:click="imprimer()">
                            Imprimer la fiche
                        </button>
                        <button type="button" class="btn btn-outline-primary" x-on:click="recommencer()">
                            Enregistrer un autre facilitateur
                        </button>
                        <a href="/superviseur" class="btn btn-outline-primary">Revenir au registre</a>
                    </div>
                </div>
            </template>
        </div>
    </div>
</x-layouts.delegation>
