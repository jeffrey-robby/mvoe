{{--
    Les modules de formation du facilitateur.

    Un facilitateur formé il y a deux ans ne se refait pas former : il rouvre
    ses modules. Cet écran existe pour cela, et il fonctionne HORS LIGNE — on
    révise dans un car, sur un banc, en attendant que la salle se remplisse. Un
    catalogue de formation qui exige une connexion ne sert qu'à ceux qui n'en
    ont pas besoin.

    Ce faisant, il rouvre l'application, donc il reste actif dans le registre.
    C'est le seul dispositif de réactivation qui ne coûte ni déplacement, ni
    per diem, ni convocation.

    Écran de terrain : cibles 48 px, corps 17 px, aucune couleur du template.
--}}
<x-layouts.kit titre="Ma formation">

    <div x-data="formationFacilitateur" x-cloak>

        {{-- ------------------------------------------------------------ --}}
        {{-- Le catalogue.                                                 --}}
        <template x-if="! module">
            <div class="space-y-5">

                <div>
                    <h1 class="text-3xl">Ma formation</h1>
                    <p class="mt-2 text-gris-texte">
                        Tout est lisible sans réseau. Reprenez où vous en étiez.
                    </p>
                </div>

                <p x-show="chargement" class="text-gris-texte">Chargement…</p>

                {{-- Un facilitateur qui vient d'être enregistré n'a pas encore de
                     cohorte, donc pas de paquet. Ses modules lui sont servis par
                     le réseau : il est encore assis en face de son superviseur. --}}
                <template x-if="! chargement && modules.length === 0">
                    <x-mvoe.vide>
                        <span x-show="horsLigne">
                            Vos modules demandent du réseau la première fois. Ensuite,
                            ils partent avec votre paquet de cohorte.
                        </span>
                        <span x-show="! horsLigne">
                            Aucun module n'est encore publié.
                        </span>
                        <x-slot:action>
                            <x-mvoe.bouton variante="second" href="/kit">Revenir à mon kit</x-mvoe.bouton>
                        </x-slot:action>
                    </x-mvoe.vide>
                </template>

                <div class="space-y-3">
                    <template x-for="m in modules" x-bind:key="m.code">
                        <button type="button" x-on:click="ouvrir(m.code)"
                                class="w-full rounded-carte border-2 border-noir p-4 text-left">
                            <span class="intitule block text-xs" x-text="m.type_libelle"></span>

                            <span class="mt-1 block text-xl font-semibold [font-family:var(--font-titre)]"
                                  x-text="m.titre"></span>

                            <span class="mt-1 block text-base text-gris-texte" x-text="m.objectif"></span>

                            <span class="chiffre mt-2 block text-sm">
                                <span x-text="m.sections.length"></span> sections ·
                                <span x-text="m.duree_minutes"></span> min
                                <span x-show="avancementDe(m) > 0">
                                    · <span x-text="avancementDe(m)"></span> % lu
                                </span>
                            </span>

                            {{-- Terminé se constate : il a lu les sections. --}}
                            <span x-show="avancementDe(m) === 100"
                                  class="intitule mt-2 inline-block rounded-net bg-noir px-2 py-1 text-xs text-blanc">
                                Terminé
                            </span>
                        </button>
                    </template>
                </div>

                <x-mvoe.bouton variante="second" class="w-full" href="/kit">
                    Revenir à mon kit
                </x-mvoe.bouton>
            </div>
        </template>

        {{-- ------------------------------------------------------------ --}}
        {{-- Un module, section par section.                               --}}
        <template x-if="module">
            <div class="space-y-5">

                <div>
                    <button type="button" x-on:click="fermer()"
                            class="min-h-tactile rounded-net border-2 border-noir px-4 text-base font-semibold [font-family:var(--font-titre)]">
                        Tous mes modules
                    </button>

                    <h1 class="mt-3 text-3xl" x-text="module.titre"></h1>
                    <p class="mt-1 text-gris-texte" x-text="module.objectif"></p>
                </div>

                {{-- Où il en est. Le compte des sections lues, pas une barre
                     décorative : c'est ce que son superviseur verra aussi. --}}
                <div class="rounded-carte bg-jaune-sourd px-4 py-3">
                    <p class="text-base">
                        Section <span class="chiffre" x-text="section"></span>
                        sur <span class="chiffre" x-text="module.sections.length"></span>
                        · <span class="chiffre" x-text="avancement"></span> % lu.
                    </p>
                </div>

                <template x-if="sectionCourante">
                    <div class="space-y-4">
                        <h2 class="text-2xl" x-text="sectionCourante.titre"></h2>

                        <p class="text-lg leading-relaxed" x-text="sectionCourante.contenu_texte"></p>

                        {{-- L'interface reste utilisable quand l'audio manque. --}}
                        <template x-if="sectionCourante.fichier_audio">
                            <audio class="w-full" controls preload="none"
                                   x-bind:src="sectionCourante.fichier_audio"></audio>
                        </template>

                        <p class="chiffre text-sm text-gris-texte">
                            <span x-text="sectionCourante.duree_minutes"></span> min
                        </p>
                    </div>
                </template>

                <div class="flex gap-2">
                    <x-mvoe.bouton variante="second" class="flex-1"
                                   x-on:click="precedente()" x-bind:disabled="section <= 1">
                        Précédente
                    </x-mvoe.bouton>

                    <x-mvoe.bouton class="flex-1" x-on:click="suivante()"
                                   x-bind:disabled="derniereSection">
                        Suivante
                    </x-mvoe.bouton>
                </div>

                {{-- Terminé se constate, il ne se déclare pas : il a lu les
                     sections, cela suffit. --}}
                <div x-show="avancement === 100" class="rounded-carte border-2 border-noir px-4 py-3">
                    <p class="text-base">
                        Module terminé. Votre superviseur le verra à la prochaine
                        synchronisation.
                    </p>
                </div>

                {{-- Le sommaire, pour sauter directement. --}}
                <div>
                    <x-mvoe.intitule>Le sommaire</x-mvoe.intitule>

                    <ul class="mt-2 divide-y divide-ligne rounded-carte border border-ligne">
                        <template x-for="s in module.sections" x-bind:key="s.ordre">
                            <li>
                                <button type="button" x-on:click="aller(s.ordre)"
                                        class="flex min-h-tactile w-full items-center gap-3 px-4 py-3 text-left text-base"
                                        x-bind:class="s.ordre === section ? 'bg-jaune-sourd font-semibold' : ''">
                                    <span class="chiffre" x-text="s.ordre"></span>
                                    <span class="flex-1" x-text="s.titre"></span>
                                    <span class="intitule text-xs" x-show="vues.includes(s.ordre)">Lu</span>
                                </button>
                            </li>
                        </template>
                    </ul>
                </div>
            </div>
        </template>
    </div>
</x-layouts.kit>
