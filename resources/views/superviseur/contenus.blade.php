<x-layouts.delegation titre="Enregistrer un contenu">

    <div x-data="redactionContenus" x-cloak>

        <ul class="flex space-x-2 rtl:space-x-reverse">
            <li>
                <a href="/superviseur/bibliotheque" class="text-primary hover:underline">Bibliothèque</a>
            </li>
            <li class="before:content-['/'] ltr:before:mr-1 rtl:before:ml-1">
                <span>Enregistrer un contenu</span>
            </li>
        </ul>

        <div class="pt-5">

            <div class="mb-6">
                <h2 class="text-2xl font-bold dark:text-white-light">Enregistrer un contenu</h2>
                <p class="text-white-dark mt-1 max-w-prose">
                    Ce que vous écrivez ici entre en brouillon. Rien ne part sur le terrain
                    avant d'être validé dans la bibliothèque.
                </p>
            </div>

            <div x-show="chargement" class="panel">
                <p class="text-white-dark">Chargement…</p>
            </div>

            <template x-if="! chargement && referentiel">
                <div>

                    {{-- Les deux catalogues ne se mélangent pas : un module de
                         formation ne touche pas un parent, et compter les deux
                         ensemble le laisserait croire. --}}
                    <div class="mb-6 inline-flex rounded-md border border-white-light dark:border-[#1b2e4b] p-1">
                        <button type="button" class="btn btn-sm border-0 shadow-none"
                                x-bind:class="volet === 'parent' ? 'btn-primary' : 'bg-transparent text-white-dark'"
                                x-on:click="volet = 'parent'">
                            Contenu pour les parents
                        </button>
                        <button type="button" class="btn btn-sm border-0 shadow-none"
                                x-bind:class="volet === 'facilitateur' ? 'btn-primary' : 'bg-transparent text-white-dark'"
                                x-on:click="volet = 'facilitateur'">
                            Contenu pour les facilitateurs
                        </button>
                    </div>

                    <div x-show="erreur" class="panel border-l-4 border-danger mb-6">
                        <p x-text="erreur"></p>
                    </div>

                    <div x-show="message" class="panel border-l-4 border-success mb-6">
                        <p x-text="message"></p>
                    </div>

                    {{-- ---------------------------------------------------- --}}
                    {{-- Volet parent : une unité, puis ses réalisations.      --}}
                    {{-- ---------------------------------------------------- --}}
                    <div x-show="volet === 'parent'" class="grid grid-cols-1 lg:grid-cols-2 gap-6">

                        <div class="panel h-fit">
                            <div class="mb-5">
                                <h5 class="font-semibold text-lg dark:text-white-light">
                                    1. Écrire l'unité
                                </h5>
                                <p class="text-white-dark mt-1">
                                    Le message clé est restitué mot pour mot par l'assistant.
                                    Écrivez-le tel qu'un parent doit l'entendre.
                                </p>
                            </div>

                            <div class="space-y-5">
                                <div>
                                    <label for="module">Module du curriculum</label>
                                    <select id="module" class="form-select"
                                            x-model="unite.module_id" x-on:change="moduleChange()"
                                            x-bind:disabled="uniteCreee !== null">
                                        <option value="">Choisir un module</option>
                                        <template x-for="m in referentiel.modules" x-bind:key="m.id">
                                            <option x-bind:value="m.id"
                                                    x-bind:disabled="m.sequences.length === 0"
                                                    x-text="'Module ' + m.numero + ' — ' + m.titre
                                                        + (m.sequences.length === 0 ? ' (aucune séquence)' : '')"></option>
                                        </template>
                                    </select>
                                    <span class="text-danger text-xs mt-1 inline-block"
                                          x-show="erreurDe('module_id')" x-text="erreurDe('module_id')"></span>
                                </div>

                                <div>
                                    <label for="sequence">Séquence</label>
                                    <select id="sequence" class="form-select" x-model="unite.sequence_id"
                                            x-bind:disabled="unite.module_id === '' || uniteCreee !== null">
                                        <option value="">Choisir une séquence</option>
                                        <template x-for="s in sequencesDuModule" x-bind:key="s.id">
                                            <option x-bind:value="s.id"
                                                    x-text="s.ordre + '. ' + s.titre
                                                        + (s.est_brise_glace ? ' (brise-glace)' : '')"></option>
                                        </template>
                                    </select>
                                    <span class="text-white-dark text-[11px] inline-block mt-1"
                                          x-show="unite.module_id !== '' && sequencesDuModule.length === 0">
                                        Ce module n'a pas encore de séquence. Une unité ne peut pas s'y rattacher.
                                    </span>
                                    <span class="text-danger text-xs mt-1 inline-block"
                                          x-show="erreurDe('sequence_id')" x-text="erreurDe('sequence_id')"></span>
                                </div>

                                <div>
                                    <label for="message_cle">Message clé</label>
                                    <textarea id="message_cle" rows="5" class="form-textarea"
                                              x-model="unite.message_cle"
                                              x-bind:disabled="uniteCreee !== null"
                                              placeholder="Un enfant qui pleure ne cherche pas à vous fatiguer. Il dit ce qu'il ne sait pas encore nommer."></textarea>
                                    <span class="text-danger text-xs mt-1 inline-block"
                                          x-show="erreurDe('message_cle')" x-text="erreurDe('message_cle')"></span>
                                </div>

                                <div class="flex flex-wrap gap-3 !mt-6">
                                    <button type="button" class="btn btn-primary"
                                            x-show="uniteCreee === null"
                                            x-on:click="creerUneUnite()"
                                            x-bind:disabled="! peutCreerUneUnite || occupe">
                                        Enregistrer l'unité
                                    </button>
                                    <button type="button" class="btn btn-outline-primary"
                                            x-show="uniteCreee !== null" x-on:click="nouvelleUnite()">
                                        Écrire une autre unité
                                    </button>
                                </div>
                            </div>
                        </div>

                        <div class="panel h-fit">
                            <div class="mb-5">
                                <h5 class="font-semibold text-lg dark:text-white-light">
                                    2. Charger une réalisation
                                </h5>
                                <p class="text-white-dark mt-1">
                                    Une réalisation par langue et par modalité. C'est elle qui rend
                                    une langue réellement disponible — pas son enregistrement dans
                                    la bibliothèque.
                                </p>
                            </div>

                            <template x-if="uniteCreee === null">
                                <p class="py-8 text-center text-white-dark">
                                    Enregistrez d'abord une unité.
                                </p>
                            </template>

                            <template x-if="uniteCreee !== null">
                                <div>
                                    <div class="rounded-md bg-white-light/40 dark:bg-[#1b2e4b]/40 p-3 mb-5">
                                        <p class="chiffre text-xs text-white-dark" x-text="uniteCreee.reference"></p>
                                        <p class="mt-1 dark:text-white-light" x-text="uniteCreee.message_cle"></p>
                                    </div>

                                    <div class="space-y-5">
                                        <div class="grid grid-cols-2 gap-4">
                                            <div>
                                                <label for="langue">Langue</label>
                                                <select id="langue" class="form-select" x-model="realisation.langue_id">
                                                    <option value="">Choisir</option>
                                                    <template x-for="l in referentiel.langues" x-bind:key="l.id">
                                                        <option x-bind:value="l.id" x-text="l.nom"></option>
                                                    </template>
                                                </select>
                                            </div>
                                            <div>
                                                <label for="modalite">Modalité</label>
                                                <select id="modalite" class="form-select" x-model="realisation.modalite">
                                                    <template x-for="m in referentiel.modalites" x-bind:key="m.valeur">
                                                        <option x-bind:value="m.valeur" x-text="m.libelle"></option>
                                                    </template>
                                                </select>
                                            </div>
                                        </div>

                                        <div>
                                            <label for="titre">Titre dans cette langue</label>
                                            <input id="titre" type="text" maxlength="160" class="form-input"
                                                   x-model="realisation.titre"
                                                   placeholder="Facultatif — sert à lister l'unité">
                                        </div>

                                        <div>
                                            <label for="contenu">Texte</label>
                                            <textarea id="contenu" rows="4" class="form-textarea"
                                                      x-model="realisation.contenu_texte"></textarea>
                                        </div>

                                        <div>
                                            <label for="audio">Fichier audio</label>
                                            <input id="audio" type="text" maxlength="255" class="form-input chiffre"
                                                   x-model="realisation.fichier_audio"
                                                   placeholder="audio/unites/m08-u7-bulu.wav">
                                            <span class="text-white-dark text-[11px] inline-block mt-1">
                                                Le chemin de l'enregistrement, pas le fichier lui-même.
                                                Laissez vide tant qu'il n'est pas produit : l'interface
                                                bascule alors sur le texte.
                                            </span>
                                        </div>

                                        <div x-show="realisation.modalite === 'texte_picto'">
                                            <label for="pictos">Pictogrammes</label>
                                            <input id="pictos" type="text" class="form-input"
                                                   x-model="realisation.pictogrammes"
                                                   placeholder="enfant-pleure, adulte-ecoute">
                                            <span class="text-white-dark text-[11px] inline-block mt-1">
                                                Séparés par des virgules.
                                            </span>
                                        </div>

                                        <button type="button" class="btn btn-primary w-full !mt-6"
                                                x-on:click="chargerUneRealisation()"
                                                x-bind:disabled="! peutChargerUneRealisation || occupe">
                                            Charger la réalisation
                                        </button>
                                    </div>

                                    <div class="mt-6" x-show="uniteCreee.realisations.length > 0">
                                        <h6 class="font-semibold dark:text-white-light mb-3">
                                            Ce que porte cette unité
                                        </h6>
                                        <div class="table-responsive">
                                            <table>
                                                <thead>
                                                    <tr>
                                                        <th class="ltr:rounded-l-md rtl:rounded-r-md">Langue</th>
                                                        <th>Modalité</th>
                                                        <th>Audio</th>
                                                        <th class="ltr:rounded-r-md rtl:rounded-l-md">État</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    <template x-for="r in uniteCreee.realisations" x-bind:key="r.id">
                                                        <tr class="text-white-dark">
                                                            <td class="text-black dark:text-white" x-text="r.langue"></td>
                                                            <td x-text="r.modalite === 'audio' ? 'Audio' : 'Texte + pictogrammes'"></td>
                                                            <td x-text="r.audio_disponible ? 'Chargé' : '—'"></td>
                                                            <td>
                                                                <span class="badge shadow-md"
                                                                      x-bind:class="r.diffusable ? 'bg-success' : 'bg-warning'"
                                                                      x-text="r.statut_libelle"></span>
                                                            </td>
                                                        </tr>
                                                    </template>
                                                </tbody>
                                            </table>
                                        </div>
                                    </div>
                                </div>
                            </template>
                        </div>
                    </div>

                    {{-- ---------------------------------------------------- --}}
                    {{-- Volet facilitateur : un module, puis ses sections.    --}}
                    {{-- ---------------------------------------------------- --}}
                    <div x-show="volet === 'facilitateur'" class="grid grid-cols-1 lg:grid-cols-2 gap-6">

                        <div class="panel h-fit">
                            <div class="mb-5">
                                <h5 class="font-semibold text-lg dark:text-white-light">
                                    1. Ouvrir le module
                                </h5>
                                <p class="text-white-dark mt-1">
                                    L'objectif dit au facilitateur si ce module répond à sa question.
                                    Une phrase, pas un programme.
                                </p>
                            </div>

                            <div class="space-y-5">
                                <div class="grid grid-cols-2 gap-4">
                                    <div>
                                        <label for="code_module">Code</label>
                                        <input id="code_module" type="text" maxlength="40"
                                               class="form-input chiffre"
                                               x-model="moduleFormation.code" placeholder="RN-04"
                                               x-bind:disabled="moduleCree !== null">
                                        <span class="text-danger text-xs mt-1 inline-block"
                                              x-show="erreurDe('code')" x-text="erreurDe('code')"></span>
                                    </div>
                                    <div>
                                        <label for="ordre">Rang</label>
                                        <input id="ordre" type="number" min="1" max="999"
                                               class="form-input chiffre" x-model="moduleFormation.ordre"
                                               x-bind:disabled="moduleCree !== null">
                                    </div>
                                </div>

                                <div>
                                    <label for="titre_module">Titre</label>
                                    <input id="titre_module" type="text" maxlength="160" class="form-input"
                                           x-model="moduleFormation.titre"
                                           x-bind:disabled="moduleCree !== null"
                                           placeholder="Recevoir une révélation sans la trahir">
                                    <span class="text-danger text-xs mt-1 inline-block"
                                          x-show="erreurDe('titre')" x-text="erreurDe('titre')"></span>
                                </div>

                                <div>
                                    <label for="type_module">Nature</label>
                                    <select id="type_module" class="form-select" x-model="moduleFormation.type"
                                            x-bind:disabled="moduleCree !== null">
                                        <template x-for="t in referentiel.types_formation" x-bind:key="t.valeur">
                                            <option x-bind:value="t.valeur" x-text="t.libelle"></option>
                                        </template>
                                    </select>
                                    <span class="text-white-dark text-[11px] inline-block mt-1"
                                          x-text="referentiel.types_formation
                                              .find((t) => t.valeur === moduleFormation.type)?.description"></span>
                                </div>

                                <div>
                                    <label for="objectif">Objectif</label>
                                    <textarea id="objectif" rows="3" class="form-textarea"
                                              x-model="moduleFormation.objectif"
                                              x-bind:disabled="moduleCree !== null"
                                              placeholder="Après ce module, vous saurez quoi dire dans l'heure qui suit une révélation."></textarea>
                                    <span class="text-danger text-xs mt-1 inline-block"
                                          x-show="erreurDe('objectif')" x-text="erreurDe('objectif')"></span>
                                </div>

                                <div>
                                    <label for="duree">Durée totale, en minutes</label>
                                    <input id="duree" type="number" min="1" max="600"
                                           class="form-input chiffre" x-model="moduleFormation.duree_minutes"
                                           x-bind:disabled="moduleCree !== null">
                                </div>

                                <div class="flex flex-wrap gap-3 !mt-6">
                                    <button type="button" class="btn btn-primary"
                                            x-show="moduleCree === null" x-on:click="creerUnModule()"
                                            x-bind:disabled="! peutCreerUnModule || occupe">
                                        Enregistrer le module
                                    </button>
                                    <button type="button" class="btn btn-outline-primary"
                                            x-show="moduleCree !== null" x-on:click="nouveauModule()">
                                        Ouvrir un autre module
                                    </button>
                                </div>
                            </div>
                        </div>

                        <div class="panel h-fit">
                            <div class="mb-5">
                                <h5 class="font-semibold text-lg dark:text-white-light">
                                    2. Ajouter les sections
                                </h5>
                                <p class="text-white-dark mt-1">
                                    Le facilitateur reprend un module là où il l'avait laissé,
                                    section par section.
                                </p>
                            </div>

                            <template x-if="moduleCree === null">
                                <p class="py-8 text-center text-white-dark">
                                    Enregistrez d'abord un module.
                                </p>
                            </template>

                            <template x-if="moduleCree !== null">
                                <div>
                                    <div class="rounded-md bg-white-light/40 dark:bg-[#1b2e4b]/40 p-3 mb-5 flex items-start justify-between gap-3">
                                        <div>
                                            <p class="font-semibold dark:text-white-light" x-text="moduleCree.titre"></p>
                                            <p class="chiffre text-xs text-white-dark">
                                                <span x-text="moduleCree.code"></span> ·
                                                <span x-text="moduleCree.sections"></span> sections ·
                                                <span x-text="moduleCree.duree_minutes"></span> min
                                            </p>
                                        </div>
                                        <span class="badge bg-warning shadow-md whitespace-nowrap"
                                              x-text="moduleCree.statut_libelle"></span>
                                    </div>

                                    <div class="space-y-5">
                                        <div>
                                            <label for="titre_section">Titre de la section</label>
                                            <input id="titre_section" type="text" maxlength="160"
                                                   class="form-input" x-model="section.titre">
                                        </div>

                                        <div>
                                            <label for="texte_section">Texte</label>
                                            <textarea id="texte_section" rows="5" class="form-textarea"
                                                      x-model="section.contenu_texte"></textarea>
                                        </div>

                                        <div class="grid grid-cols-2 gap-4">
                                            <div>
                                                <label for="audio_section">Fichier audio</label>
                                                <input id="audio_section" type="text" maxlength="255"
                                                       class="form-input chiffre" x-model="section.fichier_audio"
                                                       placeholder="audio/formation/rn04-s1.wav">
                                            </div>
                                            <div>
                                                <label for="duree_section">Durée, en minutes</label>
                                                <input id="duree_section" type="number" min="1" max="240"
                                                       class="form-input chiffre" x-model="section.duree_minutes">
                                            </div>
                                        </div>

                                        <button type="button" class="btn btn-primary w-full !mt-6"
                                                x-on:click="ajouterUneSection()"
                                                x-bind:disabled="! peutAjouterUneSection || occupe">
                                            Ajouter la section
                                        </button>
                                    </div>

                                    <div class="mt-6" x-show="sections.length > 0">
                                        <h6 class="font-semibold dark:text-white-light mb-3">Sections ajoutées</h6>
                                        <div class="space-y-2">
                                            <template x-for="s in sections" x-bind:key="s.id">
                                                <div class="flex items-center justify-between rounded-md border border-white-light dark:border-[#1b2e4b] px-3 py-2">
                                                    <p class="dark:text-white-light">
                                                        <span class="chiffre text-white-dark" x-text="s.ordre + '.'"></span>
                                                        <span x-text="s.titre"></span>
                                                    </p>
                                                    <p class="chiffre text-xs text-white-dark">
                                                        <span x-text="s.duree_minutes"></span> min ·
                                                        <span x-text="s.audio_disponible ? 'audio' : 'texte seul'"></span>
                                                    </p>
                                                </div>
                                            </template>
                                        </div>
                                    </div>

                                    <p class="text-white-dark text-xs mt-6">
                                        Ce module reste en brouillon. Validez-le dans la bibliothèque
                                        quand il est relu.
                                    </p>
                                </div>
                            </template>
                        </div>
                    </div>
                </div>
            </template>
        </div>
    </div>
</x-layouts.delegation>
