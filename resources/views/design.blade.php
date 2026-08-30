{{--
    Page de démonstration du système de design.

    Elle n'est pas un écran du produit : elle réunit au même endroit les deux
    systèmes qui cohabitent, la frontière entre eux, et les règles qui ne se
    négocient dans ni l'un ni l'autre.
--}}
<x-layouts.delegation titre="Système de design" :navigation="false">

    <div class="space-y-10">

        <section>
            <h1 class="text-4xl">Système de design</h1>
            <p class="mt-3 max-w-prose text-base text-white-dark">
                Deux systèmes cohabitent, et c'est délibéré. Le template pour l'administration,
                qu'on utilise assis à la souris sur un grand écran. La palette Mvoé pour le
                terrain, tenu d'une main en plein soleil dans une salle sans électricité.
            </p>
        </section>

        {{-- ---------------------------------------------------------------- --}}
        <section>
            <h2 class="text-2xl">1. L'administration</h2>
            <p class="mt-1 text-base text-white-dark">
                MINPROFF, délégations. Fond gris très clair, panneaux blancs, densité de bureau.
            </p>

            <div class="mt-4 grid grid-cols-2 gap-3 sm:grid-cols-4">
                @foreach ([
                    ['Primary', '#4361EE', 'bg-primary', 'text-white'],
                    ['Success', '#00AB55', 'bg-success', 'text-white'],
                    ['Warning', '#E2A03F', 'bg-warning', 'text-black'],
                    ['Danger', '#E7515A', 'bg-danger', 'text-white'],
                    ['Info', '#2196F3', 'bg-info', 'text-white'],
                    ['Secondary', '#805DCA', 'bg-secondary', 'text-white'],
                    ['Dark', '#3B3F5C', 'bg-dark', 'text-white'],
                    ['Black', '#0E1726', 'bg-black', 'text-white'],
                ] as [$nom, $hex, $fond, $encre])
                    <div class="{{ $fond }} {{ $encre }} rounded-md p-3">
                        <p class="[font-family:var(--font-titre)] font-semibold">{{ $nom }}</p>
                        <p class="chiffre text-sm opacity-90">{{ $hex }}</p>
                    </div>
                @endforeach
            </div>

            <div class="panel mt-4 border-l-4 border-danger">
                <p class="[font-family:var(--font-titre)] font-semibold">
                    Ces couleurs sont des surfaces, jamais du texte.
                </p>
                <p class="mt-2 max-w-prose text-base">
                    Sur blanc, <span class="chiffre">warning</span> fait 2,2:1,
                    <span class="chiffre">success</span> 3,0:1,
                    <span class="chiffre">danger</span> 3,6:1 : toutes échouent le seuil du texte
                    courant. C'est le même piège que le jaune du système terrain, à l'identique.
                </p>
                <p class="mt-2 max-w-prose text-base">
                    Pour écrire, on utilise les variantes assombries :
                    <span class="text-success-texte font-semibold">success-texte</span>,
                    <span class="text-warning-texte font-semibold">warning-texte</span>,
                    <span class="text-danger-texte font-semibold">danger-texte</span> — toutes
                    au-dessus de 5,4:1. Le gris secondaire du template a lui aussi été assombri :
                    il ne faisait que 3,2:1.
                </p>
            </div>

            <div class="mt-4 flex flex-wrap items-center gap-3">
                <button type="button" class="btn btn-primary">Enregistrer un facilitateur</button>
                <button type="button" class="btn btn-neutre">Annuler</button>
                <button type="button" class="btn btn-primary" disabled>Indisponible</button>
                <span class="badge badge-succes">Actif</span>
                <span class="badge badge-neutre">Inactif</span>
                <span class="badge badge-alerte">À traiter</span>
            </div>

            <div class="panel mt-4">
                <table class="tableau">
                    <thead>
                        <tr><th>Facilitateur</th><th>Arrondissement</th>
                            <th class="text-right">Séances</th><th>Statut</th></tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td class="font-semibold">Ndzana Étienne</td>
                            <td>Ebolowa II</td>
                            <td class="chiffre text-right">3</td>
                            <td><span class="badge badge-succes">Actif</span></td>
                        </tr>
                        <tr>
                            <td class="font-semibold">Zé Barnabé</td>
                            <td>Biwong-Bulu</td>
                            <td class="chiffre text-right">0</td>
                            <td><span class="badge badge-neutre">Inactif</span></td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </section>

        {{-- ---------------------------------------------------------------- --}}
        <section>
            <h2 class="text-2xl">2. Le terrain</h2>
            <p class="mt-1 max-w-prose text-base text-white-dark">
                Kit de séance et espace parent. Jaune, blanc, noir. Corps à 17 px, cibles à 48 px,
                grands blocs. La grille du template ne s'applique pas ici, et un test échoue si une
                couleur du template apparaît dans ces vues.
            </p>

            <div class="mt-4 grid grid-cols-2 gap-3 sm:grid-cols-3">
                @foreach ([
                    ['Noir', '#121212', 'bg-noir', 'text-blanc', 'Texte, surfaces pleines'],
                    ['Jaune', '#F5C518', 'bg-jaune', 'text-noir', 'Action, état actif — surface seulement'],
                    ['Jaune sourd', '#FDF3D0', 'bg-jaune-sourd', 'text-noir', 'Fonds de rappel'],
                ] as [$nom, $hex, $fond, $encre, $usage])
                    <div class="{{ $fond }} {{ $encre }} rounded-carte p-3">
                        <p class="[font-family:var(--font-titre)] font-semibold">{{ $nom }}</p>
                        <p class="chiffre text-sm">{{ $hex }}</p>
                        <p class="mt-2 text-sm leading-tight">{{ $usage }}</p>
                    </div>
                @endforeach
            </div>

            <div class="panel mt-4">
                <p class="intitule text-white-dark">Les quatre états du pointage</p>
                <p class="mt-2 max-w-prose text-base">
                    Quatre états, trois couleurs : ce sont les <strong>formes</strong> qui
                    distinguent — contour pointillé, pastille pleine, contour plein, trait de
                    liaison — et chaque pastille porte aussi son libellé écrit. Jamais la couleur
                    seule comme porteuse d'information.
                </p>

                <div class="mt-3 flex flex-wrap gap-4">
                    @foreach ([
                        ['a_pointer', 'À pointer'],
                        ['present', 'Présent'],
                        ['absent', 'Absent'],
                        ['rattrape_binome', 'Binôme'],
                    ] as [$etat, $libelle])
                        <div class="text-center">
                            <span class="pastille" data-etat="{{ $etat }}"></span>
                            <p class="intitule mt-1 text-xs">{{ $libelle }}</p>
                        </div>
                    @endforeach
                </div>
            </div>
        </section>

        {{-- ---------------------------------------------------------------- --}}
        <section>
            <h2 class="text-2xl">3. La colonne de séance</h2>
            <p class="mt-1 max-w-prose text-base text-white-dark">
                L'élément signature, conservé tel quel. Chaque séquence occupe une hauteur
                proportionnelle à sa durée officielle : la colonne est une échelle, pas une liste.
                Le facilitateur y descend physiquement au fil de la séance.
            </p>

            <div class="mt-4 space-y-2">
                <x-mvoe.bloc-sequence :ordre="1" titre="Accueil et brise-glace" :duree-minutes="10" etat="passee"/>
                <x-mvoe.bande-brise-glace consigne="Tout le monde se lève. On chante et on danse ensemble."/>
                <x-mvoe.bloc-sequence :ordre="2" titre="Discipliner, est-ce punir ?" :duree-minutes="20" etat="passee"/>
                <x-mvoe.bloc-sequence :ordre="3" titre="Ce que le coup apprend à l'enfant" :duree-minutes="25" etat="en_cours"/>
                <x-mvoe.bloc-sequence :ordre="4" titre="Poser une règle qu'un enfant peut comprendre" :duree-minutes="20" etat="a_venir"/>
            </div>

            <p class="mt-3 max-w-prose text-base text-white-dark">
                Le brise-glace est une bande jaune pleine, sans contrôle et sans chronomètre : le
                moment où l'outil se retire et où la salle prend le relais. C'est le seul endroit
                où l'on dépense de l'audace.
            </p>
        </section>

        {{-- ---------------------------------------------------------------- --}}
        <section>
            <h2 class="text-2xl">4. Typographie</h2>
            <p class="mt-1 text-base text-white-dark">Identique dans les deux systèmes.</p>

            <div class="mt-4 space-y-3">
                <div class="panel">
                    <p class="intitule text-white-dark">Archivo — titres et intitulés</p>
                    <p class="mt-1 text-3xl">Discipline positive</p>
                </div>

                <div class="panel">
                    <p class="intitule text-white-dark">IBM Plex Sans — corps</p>
                    <p class="mt-1 max-w-prose text-lg">
                        Discipliner, c'est enseigner à l'enfant comment se conduire. Ce n'est pas le
                        faire souffrir. Éàçùêô — les diacritiques françaises tiennent bien.
                    </p>
                </div>

                <div class="panel">
                    <p class="intitule text-white-dark">
                        IBM Plex Mono — chiffres, durées, compteurs, identifiants
                    </p>
                    <div class="mt-2 flex flex-wrap items-baseline gap-6">
                        <x-mvoe.chrono :secondes="1485"/>
                        <span class="chiffre text-lg">29 arrondissements · 50 facilitateurs</span>
                        <span class="chiffre text-lg tracking-[0.2em]">NRG4-Y2NS</span>
                    </div>
                    <p class="mt-2 max-w-prose text-base text-white-dark">
                        Chiffres tabulaires : la largeur ne bouge pas quand les secondes défilent.
                        L'application est un instrument de mesure — et ces identifiants seront
                        recopiés à la main, sans caractères ambigus.
                    </p>
                </div>
            </div>
        </section>
    </div>
</x-layouts.delegation>
