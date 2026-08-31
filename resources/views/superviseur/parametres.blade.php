{{--
    Les paramètres des cohortes.

    Un seul réglage, et c'est le plus important à montrer : le plafond d'une
    cohorte. Aucun 20 n'existe dans le code — la migration ne donne même pas de
    valeur par défaut à `ratio_max`. Le passer de 20 à 10 est une modification
    de DONNÉE, faite ici, en direct, sans déploiement.

    Cette manipulation sera jouée devant un jury : la confirmation doit être
    lisible à deux mètres et sans ambiguïté.
--}}
<x-layouts.delegation titre="Paramètres">

    <div x-data="parametres" x-cloak class="space-y-6">

        <div class="flex flex-wrap items-baseline justify-between gap-3">
            <div>
                <h1 class="text-3xl">Paramètres des cohortes</h1>
                <p class="mt-1 text-white-dark">
                    Le plafond d'une cohorte se change ici, et prend effet immédiatement.
                </p>
            </div>
        </div>

        <template x-if="chargement">
            <p class="text-white-dark">Chargement…</p>
        </template>

        <p x-show="erreur" x-text="erreur" class="panel border-l-4 border-warning"></p>

        {{-- La confirmation de la dernière modification. Grande, en toutes
             lettres : devant un jury, une action sans retour visible est une
             action dont on doute. --}}
        <div x-cloak x-show="modification"
             class="panel border-l-4 border-primary">
            <p class="text-xl font-semibold [font-family:var(--font-titre)]">
                Plafond modifié
            </p>
            <p class="mt-1 text-lg">
                <span x-text="modification?.libelle"></span> :
                <span class="chiffre" x-text="modification?.avant"></span>
                →
                <span class="chiffre" x-text="modification?.apres"></span>
                parents
            </p>
            <p x-show="modification?.au_dela > 0" class="mt-2 text-base">
                <span class="chiffre" x-text="modification?.au_dela"></span>
                <span x-text="modification?.au_dela === 1
                    ? 'parent déjà inscrit dépasse désormais le plafond.'
                    : 'parents déjà inscrits dépassent désormais le plafond.'"></span>
                Personne n'a été retiré : le programme ne supprime pas quelqu'un
                parce qu'un chiffre a changé.
            </p>
        </div>

        <div class="space-y-4">
            <template x-for="c in cohortes" x-bind:key="c.id">
                <div class="panel">

                    <div class="flex flex-wrap items-baseline justify-between gap-3">
                        <div>
                            <p class="text-xl font-semibold [font-family:var(--font-titre)]"
                               x-text="c.libelle"></p>
                            <p class="text-sm text-white-dark">
                                <span x-text="c.arrondissement"></span>
                                <template x-if="c.facilitateur">
                                    <span> · <span x-text="c.facilitateur"></span></span>
                                </template>
                            </p>
                        </div>

                        <dl class="flex gap-6 text-center">
                            <div>
                                <dt class="intitule text-xs">Inscrits</dt>
                                <dd class="chiffre text-2xl" x-text="c.effectif"></dd>
                            </div>
                            <div>
                                <dt class="intitule text-xs">Plafond</dt>
                                <dd class="chiffre text-2xl" x-text="c.ratio_max"></dd>
                            </div>
                            <div>
                                <dt class="intitule text-xs">Places</dt>
                                <dd class="chiffre text-2xl" x-text="c.places_restantes"></dd>
                            </div>
                        </dl>
                    </div>

                    <div class="mt-4">
                        <p class="intitule">Ratio maximum</p>

                        <div class="mt-2 flex flex-wrap gap-2" role="group"
                             x-bind:aria-label="'Plafond de ' + c.libelle">
                            <template x-for="valeur in [10, 15, 20, 25]" x-bind:key="valeur">
                                <button type="button"
                                        x-on:click="changerRatio(c, valeur)"
                                        class="btn w-20 text-lg"
                                        x-bind:class="c.ratio_max === valeur ? 'btn-primary' : 'btn-neutre'"
                                        x-bind:aria-pressed="c.ratio_max === valeur"
                                        x-text="valeur"></button>
                            </template>
                        </div>
                    </div>

                    <p x-show="c.effectif_au_dela_du_plafond > 0"
                       class="mt-3 rounded-md bg-warning-light px-3 py-2 text-base">
                        <span class="chiffre" x-text="c.effectif_au_dela_du_plafond"></span>
                        <span x-text="c.effectif_au_dela_du_plafond === 1
                            ? 'parent inscrit au-delà du plafond. Il n’a pas été retiré.'
                            : 'parents inscrits au-delà du plafond. Aucun n’a été retiré.'"></span>
                    </p>
                </div>
            </template>
        </div>

        <template x-if="! chargement && cohortes.length === 0">
            <x-mvoe.vide>Aucune cohorte n'est enregistrée.</x-mvoe.vide>
        </template>

        <p class="max-w-prose text-sm text-white-dark">
            Le plafond est une donnée de la cohorte, jamais une constante du code : c'est ce qui
            permet à une délégation d'adapter la taille de ses groupes sans attendre une nouvelle
            version de l'application.
        </p>
    </div>
</x-layouts.delegation>
