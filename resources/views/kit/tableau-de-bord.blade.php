@php
    $cartes = [
        ['cle' => 'cohortes', 'titre' => 'Cohortes',
         'ton' => 'bg-success-light text-success',
         'trace' => 'M3 7h18M3 12h18M3 17h18'],
        ['cle' => 'parents_inscrits', 'titre' => 'Parents inscrits',
         'ton' => 'bg-info-light text-info',
         'trace' => 'M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2M12 3a4 4 0 1 0 0 8 4 4 0 0 0 0-8z'],
        ['cle' => 'seances_tenues', 'titre' => 'Séances tenues',
         'ton' => 'bg-secondary-light text-secondary',
         'trace' => 'M8 2v4M16 2v4M3 10h18M5 4h14a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V6a2 2 0 0 1 2-2z'],
        ['cle' => 'ecarts_releves', 'titre' => 'Écarts relevés',
         'ton' => 'bg-warning-light text-warning',
         'trace' => 'M12 4l9 16H3zM12 10v4M12 17h.01'],
    ];

    $terrain = [
        ['cle' => 'activites', 'titre' => 'Activités',
         'ton' => 'bg-warning-light text-warning',
         'trace' => 'M12 2l3 7h7l-5.5 4.5L18 21l-6-4-6 4 1.5-7.5L2 9h7z'],
        ['cle' => 'parents_touches', 'titre' => 'Personnes touchées',
         'ton' => 'bg-info-light text-info',
         'trace' => 'M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2M9 3a4 4 0 1 0 0 8 4 4 0 0 0 0-8zM23 21v-2a4 4 0 0 0-3-3.87'],
        ['cle' => 'foyers_suivis', 'titre' => 'Foyers visités',
         'ton' => 'bg-success-light text-success',
         'trace' => 'M3 10l9-7 9 7v10a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z M9 22V12h6v10'],
        ['cle' => 'groupes_actifs', 'titre' => 'Groupes de soutien',
         'ton' => 'bg-secondary-light text-secondary',
         'trace' => 'M12 2a5 5 0 0 1 5 5v3a5 5 0 0 1-10 0V7a5 5 0 0 1 5-5zM4 21a8 8 0 0 1 16 0'],
    ];
@endphp

<x-layouts.kit titre="Mon activité">

    <div x-data="tableauDeBordFacilitateur" x-cloak>

        <div class="mb-6">
            <h1 class="text-3xl">Mon activité</h1>
            <p class="mt-1 text-gris-texte" x-text="facilitateur?.nom"></p>
        </div>

        <div x-show="chargement" class="panel">
            <p class="text-gris-texte">Chargement…</p>
        </div>

        <template x-if="horsLigne">
            <div class="panel border-l-4 border-jaune">
                <p>
                    Ces chiffres viennent du serveur. Rebranchez-vous pour les voir.
                    Tout le reste de votre kit fonctionne sans réseau.
                </p>
                <a href="/kit" class="btn btn-outline-primary mt-4 inline-flex">Revenir à mon kit</a>
            </div>
        </template>

        <template x-if="indicateurs">
            <div>
                <div class="grid grid-cols-2 xl:grid-cols-4 gap-4 mb-4">
                    @foreach ($cartes as $c)
                        <div class="panel h-full">
                            <div class="flex items-start justify-between">
                                <div class="w-11 h-11 rounded-lg flex items-center justify-center {{ $c['ton'] }}">
                                    <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                         stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round">
                                        <path d="{{ $c['trace'] }}" />
                                    </svg>
                                </div>
                                <h5 class="chiffre text-3xl font-bold text-right"
                                    x-text="indicateurs.{{ $c['cle'] }}"></h5>
                            </div>
                            <p class="text-base font-semibold mt-4">{{ $c['titre'] }}</p>
                        </div>
                    @endforeach
                </div>

                <div class="grid grid-cols-1 lg:grid-cols-3 gap-4 mb-4">
                    <div class="panel h-full">
                        <h5 class="chiffre text-3xl font-bold"
                            x-text="nombre(indicateurs.dose_moyenne_par_parent)"></h5>
                        <p class="text-base font-semibold mt-2">séances reçues par parent inscrit</p>
                        <p class="text-gris-texte mt-1">
                            Un parent rattrapé par son binôme a reçu la séance : il compte.
                        </p>
                    </div>

                    <div class="panel h-full">
                        <h5 class="chiffre text-3xl font-bold"
                            x-bind:class="indicateurs.ecarts_releves > 0 ? 'text-warning' : ''"
                            x-text="indicateurs.ecarts_releves"></h5>
                        <p class="text-base font-semibold mt-2">écarts entre déclaré et observé</p>
                        <p class="text-gris-texte mt-1">
                            Ce n'est pas une faute et ce n'est pas une note. Un écart montre un endroit
                            du déroulé qui résiste, et se lit avec votre superviseur.
                        </p>
                    </div>

                    <div class="panel h-full">
                        <h5 class="chiffre text-3xl font-bold"
                            x-text="nombre(indicateurs.delai_moyen_remontee_jours)"></h5>
                        <p class="text-base font-semibold mt-2">jours entre la séance et sa remontée</p>
                        <p class="text-gris-texte mt-1">
                            Le temps que vos séances mettent à parvenir à votre arrondissement.
                        </p>
                    </div>
                </div>

                <div class="flex items-center justify-between mb-3 mt-6">
                    <h2 class="text-xl">Le terrain</h2>
                    <span class="text-gris-texte text-sm">
                        Causeries, ateliers, porte-à-porte, visites.
                    </span>
                </div>

                <div class="grid grid-cols-2 xl:grid-cols-4 gap-4 mb-4">
                    @foreach ($terrain as $c)
                        <div class="panel h-full">
                            <div class="flex items-start justify-between">
                                <div class="w-11 h-11 rounded-lg flex items-center justify-center {{ $c['ton'] }}">
                                    <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                         stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round">
                                        <path d="{{ $c['trace'] }}" />
                                    </svg>
                                </div>
                                <h5 class="chiffre text-3xl font-bold text-right"
                                    x-text="indicateurs.{{ $c['cle'] }}"></h5>
                            </div>
                            <div class="mt-4">
                                <p class="text-base font-semibold">{{ $c['titre'] }}</p>

                                @if ($c['cle'] === 'parents_touches')
                                    <p class="text-sm text-gris-texte mt-0.5">
                                        <span class="chiffre" x-text="indicateurs.dont_femmes"></span> femmes ·
                                        <span class="chiffre" x-text="indicateurs.dont_hommes"></span> hommes
                                    </p>
                                    <div class="w-full rounded-full h-2 bg-ligne mt-3 flex overflow-hidden">
                                        <div class="bg-gradient-to-r from-[#7579ff] to-[#b224ef] h-full"
                                             x-bind:style="'width: ' + (indicateurs.parents_touches
                                                 ? Math.round(100 * indicateurs.dont_femmes / indicateurs.parents_touches)
                                                 : 0) + '%'"></div>
                                    </div>
                                @endif

                                @if ($c['cle'] === 'foyers_suivis')
                                    <p class="text-sm text-gris-texte mt-0.5">
                                        <span class="chiffre" x-text="indicateurs.foyers_avec_difficulte"></span>
                                        avec une difficulté fonctionnelle
                                    </p>
                                @endif

                                @if ($c['cle'] === 'groupes_actifs')
                                    <p class="text-sm text-gris-texte mt-0.5">
                                        actifs sur <span class="chiffre" x-text="indicateurs.groupes_soutien"></span>
                                    </p>
                                @endif
                            </div>
                        </div>
                    @endforeach
                </div>

                <div class="panel space-y-4">
                    <div class="flex items-start gap-3">
                        <span class="chiffre text-2xl font-bold shrink-0"
                              x-text="indicateurs.participants_handicap"></span>
                        <p class="text-gris-texte">
                            <span class="text-noir">participants en situation de handicap</span>,
                            sur <span class="chiffre" x-text="indicateurs.parents_touches"></span>
                            personnes touchées. Comptés activité par activité, jamais estimés.
                        </p>
                    </div>

                    <div class="flex items-start gap-3" x-show="indicateurs.signalements > 0">
                        <span class="chiffre text-2xl font-bold shrink-0"
                              x-bind:class="indicateurs.signalements_a_traiter > 0 ? 'text-warning' : ''"
                              x-text="indicateurs.signalements_a_traiter"></span>
                        <p class="text-gris-texte">
                            <span class="text-noir">de vos signalements attendent encore une réponse</span>,
                            sur <span class="chiffre" x-text="indicateurs.signalements"></span> transmis.
                            C'est votre superviseur qui les traite ; vous verrez la suite donnée.
                        </p>
                    </div>
                </div>

                <a href="/kit" class="btn btn-outline-primary w-full mt-6">Revenir à mon kit</a>
            </div>
        </template>
    </div>
</x-layouts.kit>
