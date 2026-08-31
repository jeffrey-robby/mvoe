{{--
    Les modules encore vides restent visibles : le parent voit l'architecture du
    programme, sans qu'on lui fasse croire qu'un contenu existe.
--}}
<x-layouts.parent titre="Écouter" composant="ecouterParent">

    <div class="mb-6">
        <ul class="flex space-x-2 rtl:space-x-reverse mb-3">
            <li>
                <a href="/parent/accueil" class="text-primary hover:underline">Accueil</a>
            </li>
            <li class="before:content-['/'] ltr:before:mr-1 rtl:before:ml-1">
                <span>Écouter</span>
            </li>
        </ul>

        <div class="flex flex-wrap items-end justify-between gap-3">
            <div>
                <h2 class="text-2xl font-bold dark:text-white-light">Écouter</h2>
                <p class="text-white-dark mt-1">Les unités du programme, dans votre langue.</p>
            </div>

            <button type="button" class="btn btn-outline-primary" x-on:click="retour()"
                    x-show="vue !== 'modules'">
                Revenir
            </button>
        </div>
    </div>

    <div x-show="chargement" class="panel mb-6">
        <p class="text-white-dark">Chargement…</p>
    </div>

    <div x-show="erreur" class="panel border-l-4 border-warning mb-6">
        <p x-text="erreur"></p>
    </div>

    <template x-if="vue === 'modules'">
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
            <template x-for="m in modules" x-bind:key="m.id">
                <div class="panel h-full">
                    <div class="flex items-start gap-4">
                        <span class="chiffre w-11 h-11 shrink-0 rounded-lg flex items-center justify-center text-lg font-bold"
                              x-bind:class="m.renseigne
                                  ? 'bg-primary-light text-primary'
                                  : 'bg-white-light/60 text-white-dark'"
                              x-text="m.numero"></span>

                        <div class="flex-1 min-w-0">
                            <h5 class="font-semibold dark:text-white-light" x-text="m.titre"></h5>
                            <p class="text-white-dark text-xs mt-1"
                               x-text="m.renseigne ? m.unites + ' unités à écouter' : 'Pas encore chargé'"></p>
                        </div>

                        <span class="badge shadow-md shrink-0" x-show="! m.renseigne"
                              x-bind:class="'bg-slate-400'">Bientôt</span>
                    </div>

                    <button type="button" class="btn btn-primary w-full mt-5"
                            x-on:click="ouvrirModule(m)" x-bind:disabled="! m.renseigne">
                        Ouvrir le module
                    </button>
                </div>
            </template>
        </div>
    </template>

    <template x-if="vue === 'unites'">
        <div class="panel">
            <h5 class="font-semibold text-lg dark:text-white-light mb-5" x-text="module.titre"></h5>

            <div class="space-y-2">
                <template x-for="u in unites" x-bind:key="u.id">
                    <button type="button" x-on:click="ouvrirUnite(u.id)"
                            class="flex min-h-tactile w-full items-center gap-4 rounded-md border border-white-light dark:border-[#1b2e4b] px-4 text-left transition hover:border-primary hover:text-primary">
                        <svg class="w-6 h-6 shrink-0" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true">
                            <path d="M4 9v6h4l5 4V5L8 9H4z" />
                        </svg>
                        <span class="flex-1" x-text="u.titre"></span>
                    </button>
                </template>
            </div>
        </div>
    </template>

    <template x-if="vue === 'unite' && unite">
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

            <div class="panel lg:col-span-2">
                {{-- Le titre de la réalisation servie, PAS `message_cle`.
                     `message_cle` n'existe qu'une fois, en français : c'est le
                     champ que l'assistant interroge, pas un texte à montrer.
                     L'afficher au-dessus d'un audio bulu ferait dire à l'écran
                     une chose et à la voix une autre. --}}
                <h5 class="text-2xl font-bold leading-snug dark:text-white-light"
                    x-text="unite.realisation?.titre ?? unite.message_cle"></h5>
                <p class="text-white-dark text-xs mt-1" x-text="unite.reference"></p>

                {{-- Le repli est annoncé, jamais masqué, et il NOMME la langue
                     servie. Afficher du français en laissant croire que c'est
                     du bulu serait pire que de ne rien afficher. --}}
                <div x-show="versionManquante"
                     class="flex items-center rounded border border-warning bg-warning-light p-3.5 text-warning mt-5">
                    <span>
                        Cette version n'existe pas encore dans votre langue.
                        Voici la version en <span x-text="nomLangueServie"></span>.
                    </span>
                </div>

                <template x-if="modalite === 'audio'">
                    <div class="mt-6">
                        <template x-if="unite.realisation?.fichier_audio">
                            <audio class="w-full" controls preload="none"
                                   x-bind:src="unite.realisation.fichier_audio"></audio>
                        </template>

                        {{-- L'interface reste utilisable quand l'audio manque. --}}
                        <template x-if="! unite.realisation?.fichier_audio">
                            <div class="flex items-center rounded border border-warning bg-warning-light p-3.5 text-warning">
                                <span>L'enregistrement n'est pas encore prêt. Appuyez sur « Lire et voir ».</span>
                            </div>
                        </template>
                    </div>
                </template>

                <template x-if="modalite === 'texte_picto' && unite.realisation">
                    <div class="mt-6">
                        <p class="text-xl leading-relaxed" x-text="unite.realisation.contenu_texte"></p>

                        <ul class="mt-5 flex flex-wrap gap-2">
                            <template x-for="p in (unite.realisation.pictogrammes || [])" x-bind:key="p">
                                <li class="chiffre rounded-md bg-jaune-sourd px-3 py-2" x-text="p"></li>
                            </template>
                        </ul>
                    </div>
                </template>
            </div>

            {{-- Deux grandes cibles : aucun parcours ne dépend de la capacité
                 à lire, et la bascule doit rester atteignable au pouce. --}}
            <div class="panel h-fit">
                <h5 class="font-semibold text-lg dark:text-white-light mb-5">Comment le recevoir</h5>

                <div class="space-y-3" role="group" aria-label="Modalité">
                    <button type="button" class="btn btn-lg w-full" x-on:click="basculer('audio')"
                            x-bind:class="modalite === 'audio' ? 'btn-primary' : 'btn-outline-primary'"
                            x-bind:aria-pressed="modalite === 'audio'">
                        <svg class="w-5 h-5 ltr:mr-2 rtl:ml-2" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true">
                            <path d="M4 9v6h4l5 4V5L8 9H4z" />
                        </svg>
                        Écouter
                    </button>

                    <button type="button" class="btn btn-lg w-full" x-on:click="basculer('texte_picto')"
                            x-bind:class="modalite === 'texte_picto' ? 'btn-primary' : 'btn-outline-primary'"
                            x-bind:aria-pressed="modalite === 'texte_picto'">
                        <svg class="w-5 h-5 ltr:mr-2 rtl:ml-2" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                             stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M4 19.5A2.5 2.5 0 0 1 6.5 17H20M6.5 2H20v20H6.5A2.5 2.5 0 0 1 4 19.5v-15A2.5 2.5 0 0 1 6.5 2z" />
                        </svg>
                        Lire et voir
                    </button>
                </div>
            </div>
        </div>
    </template>
</x-layouts.parent>
