@php
    $raccourcis = [
        ['/kit/inscrire', 'Inscrire un parent', 'bg-primary-light text-primary', 'M12 5v14M5 12h14'],
        ['/kit/activite', 'Enregistrer une activité', 'bg-warning-light text-warning',
         'M12 2l3 7h7l-5.5 4.5L18 21l-6-4-6 4 1.5-7.5L2 9h7z'],
        ['/kit/visite', 'Visite à domicile', 'bg-success-light text-success',
         'M3 10l9-7 9 7v10a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z M9 22V12h6v10'],
        ['/kit/signaler', 'Signaler une situation', 'bg-danger-light text-danger',
         'M12 4l9 16H3zM12 10v4M12 17h.01'],
        ['/kit/formation', 'Ma formation', 'bg-secondary-light text-secondary',
         'M22 10L12 5 2 10l10 5 10-5zM6 12v5c0 1.7 2.7 3 6 3s6-1.3 6-3v-5'],
        ['/kit/tableau-de-bord', 'Mon activité', 'bg-info-light text-info',
         'M4 19h16M7 16V9M12 16V5M17 16v-4'],
    ];
@endphp

<x-layouts.kit titre="Mon kit">

    <div x-data="accueil">

        <div class="mb-6">
            <h2 class="text-2xl font-bold dark:text-white-light" x-text="facilitateur?.nom">Facilitateur</h2>
            <p class="text-white-dark mt-1" x-text="facilitateur?.arrondissement"></p>
        </div>

        <div x-cloak x-show="message" class="panel border-l-4 border-warning mb-6">
            <p x-text="message"></p>
        </div>

        <template x-if="! paquetPresent && ! choix">
            <div class="panel">
                <h5 class="font-semibold text-lg dark:text-white-light">Votre kit est vide</h5>
                <p class="text-white-dark mt-1 max-w-prose">
                    Téléchargez votre paquet de cohorte pendant que vous avez du réseau.
                    Ensuite, tout fonctionne sans.
                </p>
                <button type="button" class="btn btn-primary mt-5"
                        x-on:click="telecharger()" x-bind:disabled="telechargement">
                    <span x-text="telechargement ? 'Téléchargement…' : 'Télécharger le paquet'">Télécharger le paquet</span>
                </button>
            </div>
        </template>

        <template x-if="choix">
            <div class="panel">
                <div class="mb-5">
                    <h5 class="font-semibold text-lg dark:text-white-light">Quelle cohorte ?</h5>
                    <p class="text-white-dark mt-1 max-w-prose">
                        Celle que vous animez aujourd'hui. Le kit n'en garde qu'une hors ligne.
                        Vous pourrez en changer plus tard, une fois vos séances envoyées.
                    </p>
                </div>

                <div class="space-y-3">
                    <template x-for="c in cohortes" x-bind:key="c.id">
                        <button type="button" x-on:click="telecharger(c.id)"
                                x-bind:disabled="telechargement"
                                class="w-full rounded-md border border-white-light dark:border-[#1b2e4b] p-4 text-left hover:border-primary transition">
                            <span class="block text-base font-semibold dark:text-white-light" x-text="c.libelle"></span>
                            <span class="chiffre block text-xs text-white-dark mt-1">
                                <span x-text="c.effectif"></span> parents · plafond
                                <span x-text="c.ratio_max"></span>
                            </span>
                        </button>
                    </template>
                </div>

                <button type="button" class="btn btn-outline-primary mt-5"
                        x-on:click="choix = false">Annuler</button>
            </div>
        </template>

        <template x-if="paquetPresent && ! choix">
            <div>
                <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-6">

                    <div class="panel lg:col-span-2">
                        <div class="flex flex-wrap items-start justify-between gap-3 mb-5">
                            <div>
                                <h5 class="font-semibold text-lg dark:text-white-light" x-text="cohorte?.libelle"></h5>
                                <p class="text-white-dark text-xs mt-0.5" x-text="cohorte?.arrondissement"></p>
                            </div>
                            <button type="button" class="btn btn-outline-primary btn-sm"
                                    x-on:click="ouvrirLeChoix()">Changer de cohorte</button>
                        </div>

                        <div class="grid grid-cols-3 gap-5">
                            <div>
                                <p class="text-xs font-semibold uppercase tracking-wider text-white-dark">Parents</p>
                                <p class="chiffre text-2xl font-bold mt-1" x-text="effectif"></p>
                            </div>
                            <div>
                                <p class="text-xs font-semibold uppercase tracking-wider text-white-dark">Plafond</p>
                                <p class="chiffre text-2xl font-bold mt-1" x-text="cohorte?.ratio_max"></p>
                            </div>
                            <div>
                                <p class="text-xs font-semibold uppercase tracking-wider text-white-dark">Depuis le</p>
                                <p class="chiffre text-2xl font-bold mt-1"
                                   x-text="cohorte ? cohorte.date_debut.slice(8, 10) + '/' + cohorte.date_debut.slice(5, 7) : ''"></p>
                            </div>
                        </div>
                    </div>

                    <template x-if="enCours">
                        <div class="panel border-l-4 border-warning h-fit">
                            <p class="text-xs font-semibold uppercase tracking-wider text-white-dark">Séance en cours</p>
                            <p class="text-base mt-2" x-text="enCours.terminee
                                ? 'Il reste la fiche de fidélité à remplir.'
                                : 'Une séance est ouverte. Elle reprendra là où vous l\'avez laissée.'"></p>

                            <a class="btn btn-primary w-full mt-5"
                               x-bind:href="enCours.terminee
                                   ? '/kit/fidelite'
                                   : '/kit/seance?module=' + enCours.module_id"
                               href="#">
                                <span x-text="enCours.terminee ? 'Remplir la fiche' : 'Reprendre la séance'">Reprendre la séance</span>
                            </a>
                        </div>
                    </template>

                    <template x-if="! enCours && prochainModule">
                        <div class="panel h-fit">
                            <p class="text-xs font-semibold uppercase tracking-wider text-white-dark">Prochaine séance</p>
                            <p class="text-base font-semibold mt-2 dark:text-white-light">
                                Module <span class="chiffre" x-text="prochainModule.numero"></span> —
                                <span x-text="prochainModule.titre"></span>
                            </p>
                            <p class="chiffre text-xs text-white-dark mt-1">
                                <span x-text="prochainModule.sequences.length"></span> séquences ·
                                <span x-text="prochainModule.duree_totale_minutes"></span> min
                            </p>

                            <a class="btn btn-primary w-full mt-5"
                               x-bind:href="'/kit/seance?module=' + prochainModule.id" href="#">
                                Ouvrir la séance
                            </a>
                        </div>
                    </template>

                    <template x-if="! enCours && ! prochainModule">
                        <div class="panel h-fit">
                            <p class="text-xs font-semibold uppercase tracking-wider text-white-dark">Prochaine séance</p>
                            <p class="text-white-dark mt-2">
                                Aucun module n'est encore renseigné dans ce paquet.
                            </p>
                        </div>
                    </template>
                </div>

                <div class="panel mb-6">
                    <h5 class="font-semibold text-lg dark:text-white-light mb-5">Ce que je peux faire</h5>

                    <div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-3 gap-4">
                        @foreach ($raccourcis as [$lien, $libelle, $ton, $trace])
                            <a href="{{ $lien }}"
                               class="flex items-center gap-3 rounded-md border border-white-light dark:border-[#1b2e4b] p-4 hover:border-primary transition">
                                <span class="w-11 h-11 shrink-0 rounded-lg flex items-center justify-center {{ $ton }}">
                                    <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                         stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round">
                                        <path d="{{ $trace }}" />
                                    </svg>
                                </span>
                                <span class="font-semibold dark:text-white-light">{{ $libelle }}</span>
                            </a>
                        @endforeach
                    </div>

                    <p class="text-white-dark text-[11px] mt-5">
                        Tout s'enregistre sans réseau : une causerie sous l'arbre compte autant
                        qu'une séance de cohorte.
                    </p>
                </div>

                <div class="panel">
                    <h5 class="font-semibold text-lg dark:text-white-light mb-5">Le programme</h5>

                    <div class="table-responsive">
                        <table>
                            <thead>
                                <tr>
                                    <th class="ltr:rounded-l-md rtl:rounded-r-md w-16">N°</th>
                                    <th>Module</th>
                                    <th class="ltr:rounded-r-md rtl:rounded-l-md text-right">État</th>
                                </tr>
                            </thead>
                            <tbody>
                                <template x-for="m in modules" x-bind:key="m.id">
                                    <tr class="text-white-dark hover:text-black dark:hover:text-white-light/90 group">
                                        <td class="chiffre" x-text="m.numero"></td>
                                        <td class="text-black dark:text-white" x-text="m.titre"></td>
                                        <td class="text-right">
                                            <span class="badge shadow-md dark:group-hover:bg-transparent"
                                                  x-bind:class="m.renseigne ? 'bg-success' : 'bg-slate-400'"
                                                  x-text="m.renseigne ? 'Prêt' : 'À venir'"></span>
                                        </td>
                                    </tr>
                                </template>
                            </tbody>
                        </table>
                    </div>

                    <p class="text-white-dark text-[11px] mt-4">
                        Les modules à venir restent visibles : montrer l'architecture du programme
                        sans faire croire qu'ils sont prêts.
                    </p>
                </div>
            </div>
        </template>
    </div>
</x-layouts.kit>
