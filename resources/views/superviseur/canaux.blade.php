{{--
    Les canaux de diffusion.

    Les pilotes sont FACTICES, et cet écran le dit en toutes lettres : un
    prototype qui laisserait croire que des SMS partent vraiment mentirait à son
    jury. Ce qui est réel, c'est l'abstraction — brancher un opérateur consiste
    à remplacer une ligne du registre des pilotes.

    Pour la radio, AUCUNE AUDIENCE n'est affichée. Une station qui annonce
    « deux millions d'auditeurs » n'a compté personne. On montre à la place le
    surcroît d'appels et de sessions dans les 48 heures suivant une diffusion
    attestée — la seule mesure d'effet radio qui compte des gestes réels.
--}}
<x-layouts.delegation titre="Canaux">

    <div x-data="canaux" x-cloak class="space-y-6">

        <div>
            <h1 class="text-3xl">Canaux de diffusion</h1>
            <p class="mt-2 max-w-prose text-white-dark">
                Ce qui atteint un parent sans passer par une séance. Une abstraction,
                quatre pilotes interchangeables.
            </p>
        </div>

        {{-- Dit une fois, en haut, plutôt que caché en note de bas de page. --}}
        <div class="panel border-l-4 border-warning" x-show="factices">
            <p class="text-base">
                <strong>Ces pilotes sont factices.</strong> Aucun message ne part
                réellement. Les volumes sont fictifs, mais la mécanique est celle de
                production : brancher un opérateur consiste à remplacer une ligne du
                registre des pilotes, sans toucher au reste du programme.
            </p>
        </div>

        <p x-show="chargement" class="text-white-dark">Chargement…</p>
        <p x-show="erreur" x-text="erreur" class="panel border-l-4 border-warning"></p>

        <template x-if="! chargement && canaux.length > 0">
            <div class="space-y-6">

                <p class="text-base text-white-dark" x-show="periode">
                    Du <span class="chiffre" x-text="periode.du.split('-').reverse().join('/')"></span>
                    au <span class="chiffre" x-text="periode.au.split('-').reverse().join('/')"></span>.
                </p>

                {{-- Les trois canaux qui se mesurent directement. --}}
                <dl class="grid grid-cols-1 gap-3 lg:grid-cols-3">
                    <template x-for="c in autres" x-bind:key="c.canal">
                        <div class="panel">
                            <dt class="intitule text-white-dark" x-text="c.libelle"></dt>

                            <dd class="chiffre mt-1 text-3xl" x-text="c.volume"></dd>
                            <p class="text-sm text-white-dark" x-text="c.unite"></p>

                            <p class="mt-3 text-base">
                                <span class="chiffre" x-text="c.aboutis"></span>
                                <span x-text="c.aboutissement"></span>
                                <span class="text-white-dark" x-show="c.taux !== null">
                                    (<span class="chiffre" x-text="nombre(c.taux)"></span> %)
                                </span>
                            </p>

                            {{-- L'USSD porte son indicateur propre : l'abandon.
                                 Une session ouverte ne dit rien ; une session
                                 abandonnée dit qu'un menu est mauvais. --}}
                            <p class="mt-2 text-base text-white-dark" x-show="c.abandons !== undefined">
                                <span class="chiffre" x-text="c.abandons"></span>
                                abandons en cours de menu.
                            </p>
                        </div>
                    </template>
                </dl>

                {{-- ------------------------------------------------------ --}}
                {{-- La radio. Le seul canal qui ne se mesure pas lui-même.  --}}
                <template x-if="radio">
                    <section class="panel space-y-4">
                        <div>
                            <h2 class="text-2xl" x-text="radio.libelle"></h2>
                            <p class="mt-1 max-w-prose text-base text-white-dark">
                                <strong>Aucune audience n'est affichée ici, et c'est
                                délibéré.</strong> Une station qui annonce « deux millions
                                d'auditeurs » multiplie une couverture théorique par une
                                population : elle n'a compté personne.
                            </p>
                        </div>

                        <dl class="grid grid-cols-2 gap-3 lg:grid-cols-4">
                            <div>
                                <dt class="intitule text-white-dark">Diffusions</dt>
                                <dd class="chiffre text-2xl" x-text="radio.volume"></dd>
                            </div>
                            <div>
                                <dt class="intitule text-white-dark">Dont attestées</dt>
                                <dd class="chiffre text-2xl" x-text="radio.aboutis"></dd>
                            </div>
                        </dl>

                        <p class="text-base text-white-dark">
                            Sans attestation, une diffusion déclarée n'est qu'une intention.
                        </p>

                        <template x-if="radio.surcroit && radio.surcroit.mesurable">
                            <div class="rounded-md bg-black/[0.04] p-4">
                                <p class="intitule text-white-dark">
                                    Ce qu'on mesure à la place : le surcroît à
                                    <span class="chiffre" x-text="radio.surcroit.fenetre_heures"></span> h
                                </p>

                                <p class="mt-2 flex flex-wrap items-baseline gap-x-2">
                                    <span class="chiffre text-3xl text-success">
                                        +<span x-text="nombre(radio.surcroit.surcroit_pourcent)"></span> %
                                    </span>
                                    <span class="text-base">
                                        d'appels vocaux et de sessions USSD dans les
                                        <span class="chiffre" x-text="radio.surcroit.fenetre_heures"></span>
                                        heures suivant une diffusion attestée.
                                    </span>
                                </p>

                                <p class="mt-2 text-base">
                                    <span class="chiffre" x-text="nombre(radio.surcroit.par_heure_apres)"></span>
                                    par heure après l'émission, contre
                                    <span class="chiffre" x-text="nombre(radio.surcroit.par_heure_ordinaire)"></span>
                                    le reste du temps, sur
                                    <span class="chiffre" x-text="radio.surcroit.diffusions_attestees"></span>
                                    diffusions attestées.
                                </p>

                                {{-- La limite est écrite à côté du chiffre, pas
                                     dans une annexe que personne ne lit. --}}
                                <p class="mt-3 max-w-prose text-sm text-white-dark"
                                   x-text="radio.surcroit.limite"></p>
                            </div>
                        </template>

                        <template x-if="radio.surcroit && ! radio.surcroit.mesurable">
                            <div class="rounded-md bg-black/[0.04] p-4">
                                <p class="text-base" x-text="radio.surcroit.raison"></p>
                                <p class="mt-1 text-sm text-white-dark">
                                    « Pas mesurable » et « aucun effet » ne veulent pas dire la
                                    même chose : on ne remplace pas l'un par l'autre.
                                </p>
                            </div>
                        </template>
                    </section>
                </template>

                {{-- ------------------------------------------------------ --}}
                {{-- Les dernières diffusions.                              --}}
                <section x-show="dernieres.length > 0">
                    <h2 class="text-xl">Dernières diffusions</h2>

                    <div class="panel mt-3 overflow-x-auto">
                        <table class="tableau">
                            <thead>
                                <tr>
                                    <th>Canal</th>
                                    <th>Cible</th>
                                    <th>Langue</th>
                                    <th class="text-right">Volume</th>
                                    <th class="text-right">Aboutis</th>
                                    <th>Quand</th>
                                </tr>
                            </thead>
                            <tbody>
                                <template x-for="d in dernieres" x-bind:key="d.date + d.cible">
                                    <tr>
                                        <td class="font-semibold" x-text="d.canal_libelle"></td>
                                        <td>
                                            <span x-text="d.cible"></span>
                                            <span class="block text-sm text-white-dark"
                                                  x-show="d.atteste_par"
                                                  x-text="'Attesté par ' + d.atteste_par"></span>
                                        </td>
                                        <td x-text="d.langue"></td>
                                        <td class="chiffre text-right" x-text="d.volume"></td>
                                        <td class="chiffre text-right" x-text="d.aboutis"></td>
                                        <td class="chiffre text-sm" x-text="d.date.slice(0, 16)"></td>
                                    </tr>
                                </template>
                            </tbody>
                        </table>
                    </div>
                </section>
            </div>
        </template>
    </div>
</x-layouts.delegation>
