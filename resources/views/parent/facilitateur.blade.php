{{--
    Aucun compte n'est nécessaire. C'est l'écran où quelqu'un qui a besoin d'un
    contact humain l'obtient sans rien demander, et c'est aussi la sortie
    proposée à un parent mineur — un refus sans issue serait une porte fermée.

    L'arrondissement choisi n'est jamais enregistré.
--}}
<x-layouts.parent titre="Trouver un facilitateur" composant="annuaireParent">

    <div class="mb-6">
        <h2 class="text-2xl font-bold dark:text-white-light">Trouver un facilitateur</h2>
        <p class="text-white-dark mt-1 max-w-prose">
            Choisissez votre arrondissement. Rien de ce que vous choisissez ici n'est
            enregistré, et personne n'est prévenu de votre passage.
        </p>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

        <div class="panel h-fit">
            <label for="arr">Arrondissement</label>
            <select id="arr" x-model="arrondissement" x-on:change="chercher()" class="form-select form-select-lg">
                <option value="">Choisir…</option>
                <template x-for="a in arrondissements" x-bind:key="a">
                    <option x-bind:value="a" x-text="a"></option>
                </template>
            </select>

            <div x-show="erreur" class="flex items-center rounded border border-warning bg-warning-light p-3.5 text-warning mt-5">
                <span x-text="erreur"></span>
            </div>
        </div>

        <div class="lg:col-span-2">
            <template x-if="! resultat">
                <div class="panel">
                    <p class="text-white-dark">
                        Choisissez un arrondissement pour voir qui anime le programme près de chez vous.
                    </p>
                </div>
            </template>

            <template x-if="resultat">
                <div>
                    {{-- Certains arrondissements n'ont aucun facilitateur actif.
                         On élargit alors au département en le disant, plutôt que
                         de renvoyer quelqu'un à rien. --}}
                    <div class="panel border-l-4 border-warning mb-6" x-show="resultat.repli_departement">
                        <p x-text="resultat.message"></p>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-6">
                        <template x-for="c in resultat.contacts" x-bind:key="c.telephone">
                            <div class="panel h-full">
                                <div class="flex items-center gap-3">
                                    <span class="w-11 h-11 shrink-0 rounded-full flex items-center justify-center bg-primary text-white font-semibold uppercase"
                                          x-text="c.nom.slice(0, 1)"></span>
                                    <div class="min-w-0">
                                        <h5 class="font-semibold dark:text-white-light truncate" x-text="c.nom"></h5>
                                        <p class="text-white-dark text-xs" x-text="c.arrondissement"></p>
                                    </div>
                                </div>

                                {{-- Un appel, pas un message : le système n'écrit
                                     jamais à personne. C'est le parent qui décide
                                     d'appeler, et rien n'est envoyé en son nom. --}}
                                <a x-bind:href="lienTelephone(c.telephone)" class="btn btn-primary btn-lg w-full mt-5">
                                    <svg class="w-5 h-5 ltr:mr-2 rtl:ml-2 shrink-0" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true">
                                        <path d="M6 3h3l2 5-2 1a12 12 0 0 0 5 5l1-2 5 2v3a2 2 0 0 1-2 2A16 16 0 0 1 4 5a2 2 0 0 1 2-2z" />
                                    </svg>
                                    <span class="chiffre" x-text="c.telephone"></span>
                                </a>
                            </div>
                        </template>
                    </div>
                </div>
            </template>
        </div>
    </div>
</x-layouts.parent>
