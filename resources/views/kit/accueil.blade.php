{{--
    Accueil du facilitateur : ses cohortes, la prochaine séance, l'état du
    paquet. Tout est lu depuis le paquet local — cet écran ne demande jamais
    le réseau une fois le téléchargement fait.
--}}
<x-layouts.kit titre="Mon kit">

    <div x-data="accueil" class="space-y-6">

        <div>
            <h1 class="text-3xl" x-text="facilitateur?.nom">Facilitateur</h1>
            <p class="mt-1 text-gris-texte" x-text="facilitateur?.arrondissement"></p>
        </div>

        {{-- Tant que le paquet n'est pas là, rien ne fonctionne hors ligne.
             L'écran le dit clairement et propose l'unique action utile. --}}
        <template x-if="! paquetPresent && ! choix">
            <div>
                <x-mvoe.vide>
                    Téléchargez votre paquet de cohorte pour travailler hors ligne.
                    <x-slot:action>
                        <x-mvoe.bouton x-on:click="telecharger()" x-bind:disabled="telechargement">
                            <span x-text="telechargement ? 'Téléchargement…' : 'Télécharger le paquet'">
                                Télécharger le paquet
                            </span>
                        </x-mvoe.bouton>
                    </x-slot:action>
                </x-mvoe.vide>
            </div>
        </template>

        {{-- Plusieurs cohortes : c'est le facilitateur qui désigne celle du
             jour. Le kit n'en garde qu'une hors ligne — un téléphone de terrain
             n'a pas la place pour tout le programme — mais choisir la première
             venue à sa place lui ferait ouvrir la mauvaise salle. --}}
        <template x-if="choix">
            <div class="space-y-3">
                <div>
                    <h2 class="text-2xl">Quelle cohorte ?</h2>
                    <p class="mt-1 text-gris-texte">
                        Celle que vous animez aujourd'hui. Vous pourrez en changer plus tard,
                        une fois vos séances envoyées.
                    </p>
                </div>

                <template x-for="c in cohortes" x-bind:key="c.id">
                    <button type="button" x-on:click="telecharger(c.id)"
                            x-bind:disabled="telechargement"
                            class="min-h-tactile w-full rounded-carte border-2 border-noir p-4 text-left">
                        <span class="block text-lg font-semibold [font-family:var(--font-titre)]"
                              x-text="c.libelle"></span>
                        <span class="chiffre mt-1 block text-sm">
                            <span x-text="c.effectif"></span> parents · plafond <span x-text="c.ratio_max"></span>
                        </span>
                    </button>
                </template>

                <x-mvoe.bouton variante="second" x-on:click="choix = false">Annuler</x-mvoe.bouton>
            </div>
        </template>

        <template x-if="paquetPresent && ! choix">
            <div class="space-y-6">

                <x-mvoe.carte>
                    <p class="text-xl font-semibold [font-family:var(--font-titre)]"
                       x-text="cohorte?.libelle"></p>
                    <p class="mt-1 text-sm text-gris-texte" x-text="cohorte?.arrondissement"></p>

                    <dl class="mt-4 grid grid-cols-3 gap-3 text-center">
                        <div>
                            <dt class="intitule text-xs">Parents</dt>
                            <dd class="chiffre text-2xl" x-text="effectif"></dd>
                        </div>
                        <div>
                            {{-- Le plafond vient de la donnée de la cohorte, jamais du code.
                                 Le superviseur peut le passer de 20 à 10 sans déploiement. --}}
                            <dt class="intitule text-xs">Plafond</dt>
                            <dd class="chiffre text-2xl" x-text="cohorte?.ratio_max"></dd>
                        </div>
                        <div>
                            <dt class="intitule text-xs">Depuis le</dt>
                            <dd class="chiffre text-2xl"
                                x-text="cohorte ? cohorte.date_debut.slice(8, 10) + '/' + cohorte.date_debut.slice(5, 7) : ''"></dd>
                        </div>
                    </dl>
                </x-mvoe.carte>

                {{-- Une séance ouverte reprend là où elle en était : ni le
                     déroulé, ni le pointage ne sont perdus. --}}
                <template x-if="enCours">
                    <div>
                        <x-mvoe.intitule>Séance en cours</x-mvoe.intitule>

                        <div class="mt-2 space-y-2">
                            <x-mvoe.carte rappel>
                                <p class="text-lg" x-text="enCours.terminee
                                    ? 'Il reste la fiche de fidélité à remplir.'
                                    : 'Une séance est ouverte.'"></p>
                            </x-mvoe.carte>

                            <x-mvoe.bouton class="w-full"
                                           x-bind:href="enCours.terminee
                                               ? '/kit/fidelite'
                                               : '/kit/seance?module=' + enCours.module_id"
                                           href="#">
                                <span x-text="enCours.terminee
                                    ? 'Remplir la fiche'
                                    : 'Reprendre la séance'">Reprendre la séance</span>
                            </x-mvoe.bouton>
                        </div>
                    </div>
                </template>

                <div x-show="! enCours">
                    <x-mvoe.intitule>Prochaine séance</x-mvoe.intitule>

                    <template x-if="prochainModule">
                        <div class="mt-2 space-y-3">
                            <x-mvoe.carte rappel>
                                <p class="text-xl [font-family:var(--font-titre)] font-semibold">
                                    Module <span class="chiffre" x-text="prochainModule.numero"></span> —
                                    <span x-text="prochainModule.titre"></span>
                                </p>
                                <p class="chiffre mt-1 text-sm">
                                    <span x-text="prochainModule.sequences.length"></span> séquences ·
                                    <span x-text="prochainModule.duree_totale_minutes"></span> min
                                </p>
                            </x-mvoe.carte>

                            <x-mvoe.bouton class="w-full"
                                           x-bind:href="'/kit/seance?module=' + prochainModule.id"
                                           href="#">
                                Ouvrir la séance
                            </x-mvoe.bouton>
                        </div>
                    </template>

                    <template x-if="! prochainModule">
                        <div class="mt-2">
                            <x-mvoe.vide>Aucun module n'est encore renseigné dans ce paquet.</x-mvoe.vide>
                        </div>
                    </template>
                </div>

                {{-- L'inscription d'un parent se fait sur le terrain, hors
                     ligne, en séance ou en visite. Le code est remis en main
                     propre : c'est la seule voie d'entrée d'un parent. --}}
                <div>
                    <x-mvoe.intitule>Ma cohorte</x-mvoe.intitule>

                    <div class="mt-2 space-y-2">
                        <x-mvoe.bouton variante="second" class="w-full" href="/kit/inscrire">
                            Inscrire un parent
                        </x-mvoe.bouton>

                        <x-mvoe.bouton variante="second" class="w-full"
                                       x-on:click="ouvrirLeChoix()">
                            Changer de cohorte
                        </x-mvoe.bouton>

                        <x-mvoe.bouton variante="second" class="w-full" href="/kit/tableau-de-bord">
                            Mon activité
                        </x-mvoe.bouton>

                        {{-- Rouvrir un module, c'est rester actif au registre. --}}
                        <x-mvoe.bouton variante="second" class="w-full" href="/kit/formation">
                            Ma formation
                        </x-mvoe.bouton>
                    </div>
                </div>

                {{-- Le travail de terrain. Tout s'enregistre sans réseau : une
                     causerie sous l'arbre compte autant qu'une séance. --}}
                <div>
                    <x-mvoe.intitule>Sur le terrain</x-mvoe.intitule>

                    <div class="mt-2 space-y-2">
                        <x-mvoe.bouton variante="second" class="w-full" href="/kit/activite">
                            Enregistrer une activité
                        </x-mvoe.bouton>

                        <x-mvoe.bouton variante="second" class="w-full" href="/kit/visite">
                            Visite à domicile
                        </x-mvoe.bouton>

                        <x-mvoe.bouton variante="second" class="w-full" href="/kit/signaler">
                            Signaler une situation
                        </x-mvoe.bouton>
                    </div>
                </div>

                <div>
                    <x-mvoe.intitule>Le programme</x-mvoe.intitule>

                    <ul class="mt-2 divide-y divide-ligne rounded-carte border border-ligne">
                        <template x-for="m in modules" x-bind:key="m.id">
                            <li class="flex items-center justify-between gap-3 px-4 py-3">
                                <span>
                                    <span class="chiffre text-gris-texte" x-text="m.numero"></span>
                                    <span x-text="m.titre"></span>
                                </span>

                                {{-- Les modules vides restent visibles : montrer
                                     l'architecture sans faire croire qu'ils sont prêts. --}}
                                <span class="intitule shrink-0 text-xs"
                                      x-bind:class="m.renseigne ? '' : 'text-gris-texte'"
                                      x-text="m.renseigne ? 'Prêt' : 'À venir'"></span>
                            </li>
                        </template>
                    </ul>
                </div>
            </div>
        </template>

        <p x-cloak x-show="message" x-text="message"
           class="rounded-net bg-jaune-sourd px-3 py-2 text-sm"></p>

        <x-mvoe.bouton variante="discret" x-on:click="deconnecter()">Fermer mon kit</x-mvoe.bouton>
    </div>
</x-layouts.kit>
