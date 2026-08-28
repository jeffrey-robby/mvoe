{{--
    L'écran de séance. Le facilitateur y passe la séance entière.

    Il est structuré par la colonne verticale du déroulé : chaque séquence
    occupe une hauteur proportionnelle à sa durée officielle, et il y descend
    physiquement au fil de la séance.

    Rien ici ne parle au réseau. Le déroulé vient du paquet local, et chaque
    ouverture de séquence est écrite dans la file locale AVANT d'apparaître à
    l'écran.
--}}
<x-layouts.kit titre="Séance" retour="/kit">

    <div x-data="seance" x-cloak>

        <template x-if="! module">
            <x-mvoe.vide>
                Ce module n'est pas dans votre paquet.
                <x-slot:action>
                    <x-mvoe.bouton variante="second" href="/kit">Revenir à mon kit</x-mvoe.bouton>
                </x-slot:action>
            </x-mvoe.vide>
        </template>

        <template x-if="module">
            <div class="space-y-4">

                {{-- ------------------------------------------------------ --}}
                {{-- Avant le démarrage : le déroulé complet, rien d'ouvert. --}}
                <template x-if="! demarree">
                    <div class="space-y-4">
                        <div>
                            <x-mvoe.intitule>
                                Module <span x-text="module.numero"></span>
                            </x-mvoe.intitule>
                            <h1 class="mt-1 text-3xl" x-text="module.titre"></h1>
                            <p class="chiffre mt-1 text-gris-texte">
                                <span x-text="module.sequences.length"></span> séquences ·
                                <span x-text="module.duree_totale_minutes"></span> min
                            </p>
                        </div>

                        <x-mvoe.bouton class="w-full" x-on:click="demarrer()">
                            Démarrer la séance
                        </x-mvoe.bouton>
                    </div>
                </template>

                {{-- ------------------------------------------------------ --}}
                {{-- Pendant la séance : le bloc en cours, détaillé.        --}}
                <template x-if="demarree && sequenceCourante && ! sequenceCourante.est_brise_glace">
                    <div class="rounded-carte border-[3px] border-noir bg-jaune p-4">
                        <div class="flex items-start justify-between gap-4">
                            <div>
                                <x-mvoe.intitule>
                                    Séquence <span x-text="sequenceCourante.ordre"></span>
                                    sur <span x-text="sequences.length"></span>
                                </x-mvoe.intitule>
                                <p class="mt-1 text-2xl font-semibold [font-family:var(--font-titre)]"
                                   x-text="sequenceCourante.titre"></p>
                            </div>

                            <div class="shrink-0 text-right">
                                {{-- Chronomètre en Plex Mono, chiffres tabulaires :
                                     la largeur ne bouge pas pendant qu'il défile. --}}
                                <time class="chiffre block text-5xl leading-none font-medium"
                                      x-text="chrono">00:00</time>
                                <p class="chiffre mt-1 text-sm">
                                    prévu <span x-text="sequenceCourante.duree_minutes"></span> min
                                </p>
                            </div>
                        </div>

                        {{-- Le dépassement est une information, jamais un reproche :
                             pas d'alerte, pas de rouge, pas de compte à rebours qui
                             stresse quelqu'un en train d'animer. --}}
                        <p x-cloak x-show="depassement > 0" class="chiffre mt-2 text-sm">
                            + <span x-text="Math.round(depassement / 60)"></span> min sur le temps prévu
                        </p>

                        <template x-if="sequenceCourante.consigne">
                            <p class="mt-3 text-lg" x-text="sequenceCourante.consigne"></p>
                        </template>

                        {{-- Ouverture des contenus. --}}
                        <template x-if="sequenceCourante.unites.length">
                            <div class="mt-4 space-y-2">
                                <template x-for="u in sequenceCourante.unites" x-bind:key="u.id">
                                    <button type="button" x-on:click="ouvrirUnite(u)"
                                            class="flex min-h-tactile w-full items-center gap-3 rounded-net border-2 border-noir bg-blanc px-3 text-left hover:bg-noir hover:text-blanc">
                                        <svg class="size-5 shrink-0" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true">
                                            <path d="M4 9v6h4l5 4V5L8 9H4z"/>
                                        </svg>
                                        <span class="text-base" x-text="u.message_cle"></span>
                                    </button>
                                </template>
                            </div>
                        </template>

                        <div class="mt-4 flex flex-wrap gap-2">
                            <button type="button" x-cloak x-show="! estDerniere" x-on:click="suivante()"
                                    class="min-h-tactile flex-1 rounded-net bg-noir px-4 text-base font-semibold text-blanc [font-family:var(--font-titre)]">
                                Séquence suivante
                            </button>

                            <button type="button" x-cloak x-show="estDerniere" x-on:click="terminer()"
                                    class="min-h-tactile flex-1 rounded-net bg-noir px-4 text-base font-semibold text-blanc [font-family:var(--font-titre)]">
                                Terminer la séance
                            </button>
                        </div>
                    </div>
                </template>

                {{-- ------------------------------------------------------ --}}
                {{-- Le brise-glace.                                        --}}
                {{--                                                        --}}
                {{-- Bande jaune pleine sur toute la largeur, AUCUN contrôle --}}
                {{-- dedans, AUCUN chronomètre, une seule ligne de texte.    --}}
                {{-- C'est le moment où l'outil se retire et où la salle     --}}
                {{-- prend le relais. La trace d'ouverture continue pourtant --}}
                {{-- d'être enregistrée : l'outil se tait, il n'oublie pas.  --}}
                <template x-if="demarree && sequenceCourante && sequenceCourante.est_brise_glace">
                    <div>
                        <div class="bande-brise-glace -mx-4 px-6 py-12 text-center text-2xl"
                             x-text="sequenceCourante.consigne || sequenceCourante.titre"></div>

                        <div class="mt-4">
                            <x-mvoe.bouton variante="second" class="w-full" x-on:click="suivante()">
                                Reprendre le déroulé
                            </x-mvoe.bouton>
                        </div>
                    </div>
                </template>

                {{-- ------------------------------------------------------ --}}
                {{-- Le pointage reste accessible pendant toute la séance : --}}
                {{-- le facilitateur pointe quand il peut, pas quand un     --}}
                {{-- écran le lui impose.                                   --}}
                <template x-if="demarree">
                    <div class="flex flex-wrap gap-2">
                        <x-mvoe.bouton variante="second" class="flex-1" x-on:click="pointer()">
                            Pointer les présences
                        </x-mvoe.bouton>

                        <x-mvoe.bouton variante="discret" x-on:click="terminer()">
                            Terminer la séance
                        </x-mvoe.bouton>
                    </div>
                </template>

                {{-- ------------------------------------------------------ --}}
                {{-- Le panneau de contenu : l'unité digitale.              --}}
                <template x-if="uniteOuverte">
                    <div class="rounded-carte border border-ligne p-4">
                        <div class="flex items-start justify-between gap-3">
                            <p class="text-lg font-semibold" x-text="uniteOuverte.message_cle"></p>
                            <button type="button" x-on:click="uniteOuverte = null"
                                    class="intitule shrink-0 underline underline-offset-4">Fermer</button>
                        </div>

                        {{-- Sélecteur de langue, permanent. --}}
                        <div class="mt-3 flex gap-2" role="group" aria-label="Langue">
                            <template x-for="l in ['fr', 'en', 'bulu']" x-bind:key="l">
                                <button type="button" x-on:click="langue = l"
                                        class="min-h-tactile flex-1 rounded-net border-2 border-noir px-3 text-base font-semibold [font-family:var(--font-titre)]"
                                        x-bind:class="langue === l ? 'bg-jaune' : 'bg-blanc'"
                                        x-bind:aria-pressed="langue === l"
                                        x-text="{ fr: 'Français', en: 'English', bulu: 'Bulu' }[l]"></button>
                            </template>
                        </div>

                        {{-- Bascule audio ↔ texte + pictogrammes. --}}
                        <div class="mt-2 flex gap-2" role="group" aria-label="Modalité">
                            <button type="button" x-on:click="modalite = 'audio'"
                                    class="min-h-tactile flex-1 rounded-net border-2 border-noir px-3 text-base font-semibold [font-family:var(--font-titre)]"
                                    x-bind:class="modalite === 'audio' ? 'bg-jaune' : 'bg-blanc'"
                                    x-bind:aria-pressed="modalite === 'audio'">Écouter</button>

                            <button type="button" x-on:click="modalite = 'texte_picto'"
                                    class="min-h-tactile flex-1 rounded-net border-2 border-noir px-3 text-base font-semibold [font-family:var(--font-titre)]"
                                    x-bind:class="modalite === 'texte_picto' ? 'bg-jaune' : 'bg-blanc'"
                                    x-bind:aria-pressed="modalite === 'texte_picto'">Texte et images</button>
                        </div>

                        {{-- Repli sur le français annoncé, jamais masqué. --}}
                        <p x-cloak x-show="versionManquante"
                           class="mt-3 rounded-net bg-jaune-sourd px-3 py-2 text-sm">
                            Cette version n'existe pas encore. Voici la version française.
                        </p>

                        <template x-if="realisation && modalite === 'audio'">
                            <div class="mt-3">
                                <template x-if="realisation.fichier_audio">
                                    <audio class="w-full" controls preload="none"
                                           x-bind:src="realisation.fichier_audio"></audio>
                                </template>

                                {{-- L'interface reste utilisable quand l'audio manque. --}}
                                <template x-if="! realisation.fichier_audio">
                                    <p class="rounded-net bg-jaune-sourd px-3 py-2 text-sm">
                                        L'enregistrement n'est pas encore disponible.
                                        Passez à la version texte et images.
                                    </p>
                                </template>
                            </div>
                        </template>

                        <template x-if="realisation && modalite === 'texte_picto'">
                            <div class="mt-3">
                                <p class="text-lg" x-text="realisation.contenu_texte"></p>

                                <ul class="mt-3 flex flex-wrap gap-2">
                                    <template x-for="p in (realisation.pictogrammes || [])" x-bind:key="p">
                                        <li class="chiffre rounded-net bg-jaune-sourd px-2 py-1 text-sm"
                                            x-text="p"></li>
                                    </template>
                                </ul>
                            </div>
                        </template>
                    </div>
                </template>

                {{-- ------------------------------------------------------ --}}
                {{-- La colonne. Elle reste visible en permanence : c'est    --}}
                {{-- elle qui dit où on en est.                             --}}
                <div class="space-y-2">
                    <x-mvoe.intitule>Le déroulé</x-mvoe.intitule>

                    <template x-for="(s, i) in sequences" x-bind:key="s.id">
                        <button type="button"
                                class="bloc-sequence"
                                x-bind:style="'--minutes: ' + s.duree_minutes"
                                x-bind:data-etat="etats[s.id]"
                                x-bind:aria-current="etats[s.id] === 'en_cours' ? 'step' : false"
                                x-bind:disabled="! demarree"
                                x-on:click="ouvrir(i)">

                            <span class="flex items-start justify-between gap-3">
                                <span class="text-lg font-semibold [font-family:var(--font-titre)]">
                                    <span class="chiffre" x-text="s.ordre + '.'"></span>
                                    <span x-text="s.titre"></span>
                                </span>
                                <span class="chiffre shrink-0 text-sm" x-text="s.duree_minutes + ' min'"></span>
                            </span>

                            {{-- L'état est écrit, jamais porté par la seule couleur. --}}
                            <span class="intitule text-xs"
                                  x-bind:class="etats[s.id] === 'a_venir' ? 'text-gris-texte' : ''"
                                  x-text="{ passee: 'Terminée', en_cours: 'En cours', a_venir: 'À venir' }[etats[s.id]]"></span>
                        </button>
                    </template>
                </div>
            </div>
        </template>
    </div>
</x-layouts.kit>
