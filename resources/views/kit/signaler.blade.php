{{--
    Le signalement REMONTE. Il entre dans la file du superviseur, qui juge et
    décide. Aucune autorité n'est prévenue automatiquement — une alerte
    automatique préviendrait avant que quiconque ait vérifié, et parfois elle
    préviendrait l'agresseur. Aucune identité n'est demandée, et la suite donnée
    est toujours visible : un signalement sans retour ne se refait pas.
--}}
<x-layouts.kit titre="Signaler">

    <div x-data="signalerTerrain" x-cloak>

        <div class="mb-6">
            <h2 class="text-2xl font-bold dark:text-white-light">Signaler une situation</h2>
            <p class="text-white-dark mt-1 max-w-prose">
                Votre superviseur la recevra et décidera de la suite. Personne d'autre n'est
                prévenu, et rien n'est envoyé automatiquement.
            </p>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">

            <div>
                <template x-if="! enregistre">
                    <div class="panel space-y-5">

                        <div class="flex items-center rounded border border-warning bg-warning-light p-3.5 text-warning">
                            <span>
                                <strong class="ltr:mr-1 rtl:ml-1">Ne mettez aucun nom.</strong>
                                Ni celui de l'enfant, ni celui du parent, ni celui du foyer.
                                C'est ce qui vous permet de signaler sans exposer qui que ce soit.
                            </span>
                        </div>

                        <div>
                            <label>De quoi s'agit-il</label>
                            <div class="flex flex-wrap gap-2">
                                <template x-for="t in types" x-bind:key="t.valeur">
                                    <button type="button" class="btn btn-sm" x-on:click="type = t.valeur"
                                            x-bind:aria-pressed="type === t.valeur"
                                            x-bind:class="type === t.valeur ? 'btn-primary' : 'btn-outline-primary'"
                                            x-text="t.libelle"></button>
                                </template>
                            </div>
                        </div>

                        <div>
                            <label>Gravité</label>
                            <div class="flex flex-wrap gap-2">
                                <template x-for="g in gravites" x-bind:key="g.valeur">
                                    <button type="button" class="btn btn-sm px-6" x-on:click="gravite = g.valeur"
                                            x-bind:aria-pressed="gravite === g.valeur"
                                            x-bind:class="gravite === g.valeur ? 'btn-primary' : 'btn-outline-primary'"
                                            x-text="g.libelle"></button>
                                </template>
                            </div>
                        </div>

                        <div class="flex flex-wrap gap-3 !mt-8">
                            <button type="button" class="btn btn-primary" x-on:click="valider()"
                                    x-bind:disabled="occupe">
                                <span x-text="occupe ? 'Un instant…' : 'Envoyer à mon superviseur'">Envoyer à mon superviseur</span>
                            </button>
                            <a href="/kit" class="btn btn-outline-primary">Annuler</a>
                        </div>
                    </div>
                </template>

                <template x-if="enregistre">
                    <div class="panel">
                        <h5 class="font-semibold text-lg dark:text-white-light">Signalement enregistré</h5>
                        <p class="text-white-dark mt-1">
                            Il partira vers votre superviseur dès que vous retrouverez du réseau.
                            Vous verrez ici ce qu'il en aura fait.
                        </p>

                        <div class="flex flex-wrap gap-3 mt-5">
                            <button type="button" class="btn btn-primary" x-on:click="recommencer()">
                                En signaler un autre
                            </button>
                            <a href="/kit" class="btn btn-outline-primary">Revenir à mon kit</a>
                        </div>
                    </div>
                </template>
            </div>

            {{-- La suite donnée. C'est ce qui décide s'il en fera un deuxième. --}}
            <div class="panel h-fit" x-show="! chargement">
                <h5 class="font-semibold text-lg dark:text-white-light mb-5">Mes signalements</h5>

                <p class="text-white-dark" x-show="horsLigne">
                    La réponse de votre superviseur demande du réseau. Ce que vous saisissez ici
                    part sans réseau ; c'est sa réponse qui a besoin d'une connexion.
                </p>

                <p class="text-white-dark" x-show="! horsLigne && miens.length === 0">
                    Vous n'avez encore rien signalé.
                </p>

                <div class="space-y-4" x-show="! horsLigne && miens.length > 0">
                    <template x-for="s in miens" x-bind:key="s.uuid">
                        <div class="rounded-md border border-white-light dark:border-[#1b2e4b] p-4">
                            <div class="flex flex-wrap items-start justify-between gap-2">
                                <div>
                                    <p class="font-semibold dark:text-white-light" x-text="s.type_libelle"></p>
                                    <p class="text-white-dark text-xs mt-0.5">
                                        Gravité <span x-text="s.gravite_libelle.toLowerCase()"></span>
                                        · <span class="chiffre" x-text="s.soumis_le.split('-').reverse().join('/')"></span>
                                    </p>
                                </div>
                                <span class="badge shadow-md"
                                      x-bind:class="s.suite_donnee ? 'bg-success' : 'bg-warning'"
                                      x-text="s.statut_libelle"></span>
                            </div>

                            <div class="mt-3" x-show="s.suite_donnee">
                                <p class="text-xs font-semibold uppercase tracking-wider text-white-dark">Suite donnée</p>
                                <p class="mt-1" x-text="s.suite_donnee"></p>
                            </div>

                            <p class="text-white-dark mt-3" x-show="! s.suite_donnee">
                                En attente depuis <span class="chiffre" x-text="s.jours_attente"></span> jours.
                                Votre superviseur ne l'a pas encore traité.
                            </p>
                        </div>
                    </template>
                </div>
            </div>
        </div>
    </div>
</x-layouts.kit>
