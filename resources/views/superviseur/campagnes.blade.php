{{--
    Les campagnes.

    Le ministère déclenche, les délégations accusent réception. La cascade est
    enregistrée d'un coup au déclenchement : dans la vraie vie, elle n'est pas
    un processus asynchrone mais un fait administratif. Le ministère décide, et
    tous les échelons sont concernés au même instant.

    Ce qui avance dans le temps, c'est la PRISE DE CONNAISSANCE de chaque
    échelon — et c'est cela que cet écran montre. Ce n'est pas un pourcentage
    d'exécution du programme : confondre les deux ferait croire qu'une campagne
    « à 80 % » a touché 80 % des parents.
--}}
<x-layouts.delegation titre="Campagnes">

    <div x-data="campagnes" x-cloak class="space-y-6">

        <div class="flex flex-wrap items-end justify-between gap-3">
            <div>
                <h1 class="text-3xl">Campagnes</h1>
                <p class="mt-2 max-w-prose text-white-dark">
                    Une campagne pousse des modules, dans des langues, sur des territoires.
                    Elle ne remplace pas les séances : elle les accompagne.
                </p>
            </div>

            <button type="button" class="btn btn-primary" x-show="peutCreer && ! formulaireOuvert"
                    x-on:click="formulaireOuvert = true">
                Créer une campagne
            </button>
        </div>

        <p x-show="chargement && campagnes.length === 0" class="text-white-dark">Chargement…</p>
        <p x-show="erreur" x-text="erreur" class="panel border-l-4 border-warning"></p>

        {{-- ------------------------------------------------------------ --}}
        {{-- La création. Réservée au ministère.                           --}}
        <template x-if="formulaireOuvert">
            <div class="panel border-l-4 border-primary space-y-4">

                <h2 class="text-2xl">Nouvelle campagne</h2>

                <div>
                    <label for="titre" class="etiquette">Titre</label>
                    <input id="titre" x-model="titre" type="text" maxlength="160"
                           placeholder="Rentrée scolaire — discipline positive" class="champ">
                </div>

                <div>
                    <label for="objet" class="etiquette">
                        Pourquoi maintenant <span class="text-white-dark">— facultatif</span>
                    </label>
                    <textarea id="objet" x-model="objet" rows="2" maxlength="1000"
                              class="champ"></textarea>
                </div>

                {{-- Seuls les modules validés sont proposés : on ne lance pas
                     une campagne sur un brouillon. Le serveur le revérifie. --}}
                <fieldset>
                    <legend class="etiquette">Modules à pousser</legend>
                    <div class="flex flex-wrap gap-2">
                        <template x-for="m in modules" x-bind:key="m.id">
                            <button type="button" x-on:click="basculer('moduleIds', m.id)"
                                    x-bind:aria-pressed="moduleIds.includes(m.id)"
                                    x-bind:class="moduleIds.includes(m.id) ? 'btn-primary' : 'btn-neutre'"
                                    class="btn" x-text="m.titre"></button>
                        </template>
                    </div>
                </fieldset>

                <fieldset>
                    <legend class="etiquette">Langues</legend>
                    <div class="flex flex-wrap gap-2">
                        <template x-for="l in langues" x-bind:key="l.id">
                            <button type="button" x-on:click="basculer('langueIds', l.id)"
                                    x-bind:aria-pressed="langueIds.includes(l.id)"
                                    x-bind:class="langueIds.includes(l.id) ? 'btn-primary' : 'btn-neutre'"
                                    class="btn" x-text="l.nom"></button>
                        </template>
                    </div>
                </fieldset>

                {{-- Les dix régions. Les neuf non déployées restent visibles :
                     le système est national par construction, et une région
                     sans facilitateur produira une campagne sans destinataire
                     — ce que l'écran d'avancement dira ensuite. --}}
                <fieldset>
                    <legend class="etiquette">Régions</legend>
                    <div class="flex flex-wrap gap-2">
                        <template x-for="r in regions" x-bind:key="r.id">
                            <button type="button" x-on:click="basculer('regionIds', r.id)"
                                    x-bind:aria-pressed="regionIds.includes(r.id)"
                                    x-bind:class="regionIds.includes(r.id) ? 'btn-primary' : 'btn-neutre'"
                                    class="btn">
                                <span x-text="r.libelle"></span>
                                <span class="text-xs" x-show="! r.peuplee">· pas déployée</span>
                            </button>
                        </template>
                    </div>
                </fieldset>

                <div class="grid grid-cols-1 gap-3 sm:grid-cols-2">
                    <div>
                        <label for="debut" class="etiquette">Du</label>
                        <input id="debut" x-model="dateDebut" type="date" class="champ chiffre">
                    </div>
                    <div>
                        <label for="fin" class="etiquette">Au</label>
                        <input id="fin" x-model="dateFin" type="date" class="champ chiffre">
                    </div>
                </div>

                <div class="flex flex-wrap gap-2">
                    <button type="button" class="btn btn-primary" x-on:click="creer()"
                            x-bind:disabled="! peutValider || occupe">
                        <span x-text="occupe ? 'Un instant…' : 'Déclencher la campagne'">
                            Déclencher la campagne
                        </span>
                    </button>
                    <button type="button" class="btn btn-neutre"
                            x-on:click="fermerLeFormulaire()">Annuler</button>
                </div>
            </div>
        </template>

        {{-- ------------------------------------------------------------ --}}
        {{-- Les campagnes et l'avancement de leur cascade.                --}}
        <template x-if="! chargement && campagnes.length === 0">
            <div class="panel">
                <p class="text-base" x-show="peutCreer">
                    Aucune campagne. Créez-en une pour pousser un module sur une région.
                </p>
                <p class="text-base" x-show="! peutCreer">
                    Aucune campagne ne concerne votre territoire pour l'instant.
                </p>
            </div>
        </template>

        <div class="space-y-4">
            <template x-for="c in campagnes" x-bind:key="c.id">
                <div class="panel space-y-3">

                    <div class="flex flex-wrap items-baseline justify-between gap-2">
                        <h2 class="text-2xl" x-text="c.titre"></h2>
                        <span class="badge"
                              x-bind:class="c.statut === 'declenchee' ? 'badge-succes' : 'badge-neutre'"
                              x-text="c.statut_libelle"></span>
                    </div>

                    <p class="max-w-prose text-base" x-show="c.objet" x-text="c.objet"></p>

                    <p class="text-base text-white-dark">
                        <span class="chiffre" x-text="c.date_debut.split('-').reverse().join('/')"></span>
                        au
                        <span class="chiffre" x-text="c.date_fin.split('-').reverse().join('/')"></span>
                        · <span x-text="c.regions.join(', ')"></span>
                        · <span x-text="c.langues.join(', ')"></span>
                    </p>

                    <ul class="list-inside list-disc text-base">
                        <template x-for="m in c.modules" x-bind:key="m">
                            <li x-text="m"></li>
                        </template>
                    </ul>

                    {{-- L'avancement de la cascade, niveau par niveau. --}}
                    <div>
                        <p class="intitule text-white-dark">Qui a pris connaissance</p>

                        <div class="mt-2 grid grid-cols-2 gap-3 lg:grid-cols-4">
                            <template x-for="n in c.avancement" x-bind:key="n.niveau">
                                <div class="rounded-md bg-black/[0.04] px-3 py-2">
                                    <p class="intitule text-xs" x-text="{
                                        region: 'Régions',
                                        departement: 'Départements',
                                        arrondissement: 'Arrondissements',
                                        facilitateur: 'Facilitateurs',
                                    }[n.niveau]"></p>
                                    <p class="chiffre text-xl">
                                        <span x-text="n.recues"></span><span class="text-white-dark"
                                              x-text="'/' + n.affectees"></span>
                                    </p>
                                </div>
                            </template>
                        </div>

                        <p class="mt-2 max-w-prose text-sm text-white-dark">
                            <span class="chiffre" x-text="recues(c)"></span> échelons sur
                            <span class="chiffre" x-text="affectees(c)"></span> ont ouvert la
                            campagne. Ce n'est pas un taux d'exécution du programme : c'est le
                            nombre de gens qui savent qu'elle existe.
                        </p>
                    </div>

                    {{-- Accuser réception. Geste manuel, et il le reste :
                         cocher à l'ouverture d'un écran ferait passer une
                         consultation pour une décision. --}}
                    <button type="button" class="btn btn-primary" x-show="! peutCreer"
                            x-on:click="accuser(c)" x-bind:disabled="occupe">
                        J'ai pris connaissance
                    </button>
                </div>
            </template>
        </div>
    </div>
</x-layouts.delegation>
