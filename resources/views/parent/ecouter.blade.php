{{--
    Écran « Écouter » : les modules, puis les unités, puis une unité.

    Lecteur à grands boutons, bascule vers la version texte + pictogrammes,
    sélecteur de langue permanent dans la barre du haut.

    Les modules encore vides restent visibles : le parent voit l'architecture du
    programme, sans qu'on lui fasse croire qu'un contenu existe.
--}}
<x-layouts.parent titre="Écouter" composant="ecouterParent">

    <div class="space-y-4">

        <div class="flex items-center gap-3">
            <button type="button" x-on:click="retour()"
                    class="flex size-tactile shrink-0 items-center justify-center rounded-net border-2 border-noir"
                    aria-label="Revenir">
                <svg class="size-6" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                     stroke-width="2.5" stroke-linecap="square" aria-hidden="true">
                    <path d="M15 5 8 12l7 7"/>
                </svg>
            </button>

            <h1 class="text-3xl">Écouter</h1>
        </div>

        <p x-show="chargement" class="text-gris-texte">Chargement…</p>
        <p x-show="erreur" x-text="erreur" class="rounded-net border-2 border-noir px-3 py-2"></p>

        {{-- ---------------------------------------------------------------- --}}
        {{-- Les modules.                                                      --}}
        <template x-if="vue === 'modules'">
            <ul class="space-y-2">
                <template x-for="m in modules" x-bind:key="m.id">
                    <li>
                        <button type="button" x-on:click="ouvrirModule(m)"
                                x-bind:disabled="! m.renseigne"
                                class="flex min-h-tactile w-full items-center gap-4 rounded-carte border-[3px] px-4 py-4 text-left"
                                x-bind:class="m.renseigne
                                    ? 'border-noir bg-blanc hover:bg-jaune'
                                    : 'border-ligne bg-blanc text-gris-texte'">
                            <span class="chiffre shrink-0 text-2xl" x-text="m.numero"></span>
                            <span class="flex-1 text-lg" x-text="m.titre"></span>

                            <span class="intitule shrink-0 text-xs"
                                  x-text="m.renseigne ? m.unites + ' à écouter' : 'bientôt'"></span>
                        </button>
                    </li>
                </template>
            </ul>
        </template>

        {{-- ---------------------------------------------------------------- --}}
        {{-- Les unités d'un module.                                           --}}
        <template x-if="vue === 'unites'">
            <div class="space-y-3">
                <p class="text-xl font-semibold [font-family:var(--font-titre)]"
                   x-text="module.titre"></p>

                <ul class="space-y-2">
                    <template x-for="u in unites" x-bind:key="u.id">
                        <li>
                            <button type="button" x-on:click="ouvrirUnite(u.id)"
                                    class="flex min-h-tactile w-full items-center gap-4 rounded-carte border-[3px] border-noir bg-blanc px-4 py-4 text-left hover:bg-jaune">
                                <svg class="size-7 shrink-0" viewBox="0 0 24 24" fill="currentColor"
                                     aria-hidden="true">
                                    <path d="M4 9v6h4l5 4V5L8 9H4z"/>
                                </svg>
                                <span class="flex-1 text-lg" x-text="u.titre"></span>
                            </button>
                        </li>
                    </template>
                </ul>
            </div>
        </template>

        {{-- ---------------------------------------------------------------- --}}
        {{-- Une unité.                                                        --}}
        <template x-if="vue === 'unite' && unite">
            <div class="space-y-4">

                {{-- Le titre de la réalisation servie, PAS `message_cle`.
                     `message_cle` n'existe qu'une fois, en français : c'est le
                     champ que l'assistant interroge, pas un texte à montrer.
                     L'afficher au-dessus d'un audio bulu ferait dire à l'écran
                     une chose et à la voix une autre. --}}
                <p class="text-2xl leading-snug"
                   x-text="unite.realisation?.titre ?? unite.message_cle"></p>

                {{-- Le repli est annoncé, jamais masqué, et il NOMME la langue
                     servie. Afficher du français en laissant croire que c'est
                     du bulu serait pire que de ne rien afficher. --}}
                <p x-show="versionManquante"
                   class="rounded-net bg-jaune-sourd px-3 py-2 text-base">
                    Cette version n'existe pas encore dans votre langue.
                    Voici la version en <span x-text="nomLangueServie"></span>.
                </p>

                {{-- Bascule audio ↔ texte + pictogrammes. Deux grandes cibles. --}}
                <div class="flex gap-2" role="group" aria-label="Modalité">
                    <button type="button" x-on:click="basculer('audio')"
                            class="min-h-tactile flex-1 rounded-net border-[3px] border-noir py-3 text-lg font-semibold [font-family:var(--font-titre)]"
                            x-bind:class="modalite === 'audio' ? 'bg-jaune' : 'bg-blanc'"
                            x-bind:aria-pressed="modalite === 'audio'">Écouter</button>

                    <button type="button" x-on:click="basculer('texte_picto')"
                            class="min-h-tactile flex-1 rounded-net border-[3px] border-noir py-3 text-lg font-semibold [font-family:var(--font-titre)]"
                            x-bind:class="modalite === 'texte_picto' ? 'bg-jaune' : 'bg-blanc'"
                            x-bind:aria-pressed="modalite === 'texte_picto'">Lire et voir</button>
                </div>

                <template x-if="modalite === 'audio'">
                    <div>
                        <template x-if="unite.realisation?.fichier_audio">
                            <audio class="w-full" controls preload="none"
                                   x-bind:src="unite.realisation.fichier_audio"></audio>
                        </template>

                        {{-- L'interface reste utilisable quand l'audio manque. --}}
                        <template x-if="! unite.realisation?.fichier_audio">
                            <p class="rounded-net bg-jaune-sourd px-3 py-3 text-base">
                                L'enregistrement n'est pas encore prêt. Appuyez sur « Lire et voir ».
                            </p>
                        </template>
                    </div>
                </template>

                <template x-if="modalite === 'texte_picto' && unite.realisation">
                    <div>
                        <p class="text-xl leading-relaxed" x-text="unite.realisation.contenu_texte"></p>

                        <ul class="mt-4 flex flex-wrap gap-2">
                            <template x-for="p in (unite.realisation.pictogrammes || [])"
                                      x-bind:key="p">
                                <li class="chiffre rounded-net bg-jaune-sourd px-3 py-2 text-base"
                                    x-text="p"></li>
                            </template>
                        </ul>
                    </div>
                </template>

                <p class="text-sm text-gris-texte" x-text="unite.reference"></p>
            </div>
        </template>
    </div>
</x-layouts.parent>
