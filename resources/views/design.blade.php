{{--
    Page de démonstration du système de design.

    Elle n'est pas un écran du produit : elle sert à vérifier les tokens, la
    typographie et les composants au même endroit, et à contrôler les règles
    non négociables (le jaune n'est jamais du texte, aucun état porté par la
    seule couleur, cibles tactiles à 48 px).
--}}
<x-layouts.kit titre="Système de design" :compteur-demo="1">

    <div class="space-y-10">

        <section>
            <h1 class="text-3xl">Système de design Mvoé</h1>
            <p class="mt-2 max-w-prose text-gris-texte">
                Trois couleurs, trois polices, une échelle généreuse. Cette page réunit tout ce
                qui sert à construire les écrans, et rien d'autre.
            </p>
        </section>

        {{-- ---------------------------------------------------------------- --}}
        <section>
            <x-mvoe.intitule>Palette</x-mvoe.intitule>

            <div class="mt-3 grid grid-cols-2 gap-3 sm:grid-cols-3">
                @foreach ([
                    ['Noir', '#121212', 'bg-noir', 'text-blanc', 'Texte, surfaces pleines'],
                    ['Jaune', '#F5C518', 'bg-jaune', 'text-noir', 'Action, état actif — surface seulement'],
                    ['Jaune sourd', '#FDF3D0', 'bg-jaune-sourd', 'text-noir', 'Fonds de rappel, zones calmes'],
                    ['Blanc', '#FFFFFF', 'bg-blanc border border-ligne', 'text-noir', 'Fond général'],
                    ['Ligne', '#E4E4E4', 'bg-ligne', 'text-noir', 'Bordures'],
                    ['Gris texte', '#6B6B6B', 'bg-gris-texte', 'text-blanc', 'Texte secondaire uniquement'],
                ] as [$nom, $hex, $fond, $encre, $usage])
                    <div class="{{ $fond }} {{ $encre }} rounded-carte p-3">
                        <p class="font-semibold [font-family:var(--font-titre)]">{{ $nom }}</p>
                        <p class="chiffre text-sm">{{ $hex }}</p>
                        <p class="mt-2 text-sm leading-tight">{{ $usage }}</p>
                    </div>
                @endforeach
            </div>

            <x-mvoe.carte rappel class="mt-3">
                <p class="font-semibold">Le jaune n'est jamais une couleur de texte.</p>
                <p class="mt-1 text-sm">
                    Jaune sur blanc échoue tous les seuils de contraste. Le jaune est une surface ;
                    le texte qui s'y pose est noir. Contraste noir sur jaune :
                    <span class="chiffre">11,5:1</span>. Noir sur blanc :
                    <span class="chiffre">18,7:1</span>. Gris secondaire sur blanc :
                    <span class="chiffre">5,3:1</span>.
                </p>
                <p class="mt-2 text-sm text-gris-texte">
                    Ces trois ratios sont recalculés à chaque exécution des tests
                    (<span class="chiffre">ContrastePaletteTest</span>) : s'ils cessent d'être exacts,
                    la suite échoue.
                </p>
            </x-mvoe.carte>
        </section>

        {{-- ---------------------------------------------------------------- --}}
        <section>
            <x-mvoe.intitule>Typographie</x-mvoe.intitule>

            <div class="mt-3 space-y-4">
                <div class="rounded-carte border border-ligne p-4">
                    <p class="mb-2 text-sm text-gris-texte">Archivo — titres et intitulés</p>
                    <p class="text-3xl">Discipline positive</p>
                    <p class="intitule mt-2">Intitulé de section</p>
                </div>

                <div class="rounded-carte border border-ligne p-4">
                    <p class="mb-2 text-sm text-gris-texte">IBM Plex Sans — corps, 17 px minimum</p>
                    <p class="max-w-prose">
                        Discipliner, c'est enseigner à l'enfant comment se conduire. Ce n'est pas le
                        faire souffrir. Éàçùêô — les diacritiques françaises tiennent bien.
                    </p>
                </div>

                <div class="rounded-carte border border-ligne p-4">
                    <p class="mb-2 text-sm text-gris-texte">
                        IBM Plex Mono — chiffres, chronomètres, compteurs, durées
                    </p>
                    <div class="flex flex-wrap items-baseline gap-6">
                        <x-mvoe.chrono :secondes="1485"/>
                        <x-mvoe.chrono :secondes="612" taille="moyen"/>
                        <span class="chiffre">20 parents · 90 min · 3 séances</span>
                    </div>
                    <p class="mt-2 max-w-prose text-sm text-gris-texte">
                        Chiffres tabulaires : la largeur ne bouge pas quand les secondes défilent.
                        L'application est un instrument de mesure.
                    </p>
                </div>
            </div>
        </section>

        {{-- ---------------------------------------------------------------- --}}
        <section>
            <x-mvoe.intitule>Boutons</x-mvoe.intitule>

            <div class="mt-3 flex flex-wrap items-center gap-3">
                <x-mvoe.bouton>Pointer les présences</x-mvoe.bouton>
                <x-mvoe.bouton variante="second">Ouvrir le déroulé</x-mvoe.bouton>
                <x-mvoe.bouton variante="discret">Quitter</x-mvoe.bouton>
                <x-mvoe.bouton disabled>Indisponible</x-mvoe.bouton>
                <x-mvoe.bouton-ecoute/>
            </div>

            <p class="mt-3 max-w-prose text-sm text-gris-texte">
                Hauteur minimale 48 px. Une seule action principale par écran. Les libellés disent
                le geste : « Pointer les présences », pas « Soumettre le formulaire ».
            </p>
        </section>

        {{-- ---------------------------------------------------------------- --}}
        <section>
            <x-mvoe.intitule>Les trois états du pointage</x-mvoe.intitule>

            <div class="mt-3 rounded-carte border border-ligne p-4"
                 x-data="{ dernier: null }"
                 x-on:pointage="dernier = $event.detail">

                <div class="grid grid-cols-4 gap-1 sm:grid-cols-6">
                    <x-mvoe.pastille-presence code="EB2-01" libelle-local="Odile, marché" statut="a_pointer"/>
                    <x-mvoe.pastille-presence code="EB2-02" libelle-local="la maman du petit" statut="present"/>
                    <x-mvoe.pastille-presence code="EB2-03" statut="absent"/>
                    <x-mvoe.pastille-presence code="EB2-07" libelle-local="Bernard, taxi" statut="rattrape_binome"/>
                </div>

                <p class="mt-3 text-sm text-gris-texte">
                    Un appui fait défiler les trois états. On ne revient jamais à « à pointer ».
                    <span x-cloak x-show="dernier" class="chiffre text-noir">
                        Dernier geste : <span x-text="dernier?.code"></span> →
                        <span x-text="dernier?.statut"></span>
                    </span>
                </p>
            </div>

            <x-mvoe.carte rappel class="mt-3">
                <p class="text-sm">
                    Quatre états, trois couleurs : <strong>à pointer</strong> = contour pointillé,
                    <strong>présent</strong> = pastille noire pleine, <strong>absent</strong> = contour
                    plein, <strong>rattrapé</strong> = pastille jaune marquée d'un trait de liaison.
                    Ce sont les formes qui distinguent, pas une palette élargie — et chaque état porte
                    aussi un libellé écrit. Personne ne démarre « présent » : un parent oublié reste
                    visiblement non pointé plutôt que d'être remonté comme présent.
                </p>
            </x-mvoe.carte>
        </section>

        {{-- ---------------------------------------------------------------- --}}
        <section>
            <x-mvoe.intitule>La colonne de séance</x-mvoe.intitule>
            <p class="mt-1 max-w-prose text-sm text-gris-texte">
                L'élément signature. Chaque séquence occupe une hauteur proportionnelle à sa durée
                officielle : la colonne est une échelle, pas une liste. Module 8, 90 minutes.
            </p>

            <div class="mt-3 space-y-2">
                <x-mvoe.bloc-sequence :ordre="1" titre="Accueil et brise-glace" :duree-minutes="10" etat="passee"/>
                <x-mvoe.bande-brise-glace consigne="Tout le monde se lève. On chante et on danse ensemble."/>
                <x-mvoe.bloc-sequence :ordre="2" titre="Discipliner, est-ce punir ?" :duree-minutes="20" etat="passee"/>
                <x-mvoe.bloc-sequence :ordre="3" titre="Ce que le coup apprend à l'enfant" :duree-minutes="25" etat="en_cours"/>
                <x-mvoe.bloc-sequence :ordre="4" titre="Poser une règle qu'un enfant peut comprendre" :duree-minutes="20" etat="a_venir"/>
                <x-mvoe.bloc-sequence :ordre="5" titre="Ce que je fais cette semaine à la maison" :duree-minutes="15" etat="a_venir"/>
            </div>
        </section>

        {{-- ---------------------------------------------------------------- --}}
        <section>
            <x-mvoe.intitule>Surfaces et écrans vides</x-mvoe.intitule>

            <div class="mt-3 grid gap-3 sm:grid-cols-2">
                <x-mvoe.carte titre="Ebolowa II — groupe du mardi" meta="20 parents">
                    <p class="text-sm text-gris-texte">Prochaine séance : module 9, mardi 1<sup>er</sup> septembre.</p>
                </x-mvoe.carte>

                <x-mvoe.carte titre="Fiche de fidélité" meta="après la séance" rappel>
                    <p class="text-sm">Elle ne s'ouvre jamais pendant la séance.</p>
                </x-mvoe.carte>
            </div>

            <div class="mt-3">
                <x-mvoe.vide>
                    Aucune séance à synchroniser.
                    <x-slot:action>
                        <x-mvoe.bouton variante="second">Ouvrir une séance</x-mvoe.bouton>
                    </x-slot:action>
                </x-mvoe.vide>
            </div>

            <p class="mt-3 max-w-prose text-sm text-gris-texte">
                Un écran vide dit quoi faire. Il ne s'excuse pas.
            </p>
        </section>

    </div>
</x-layouts.kit>
