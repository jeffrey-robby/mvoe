{{--
    La bibliothèque de contenus et la file de validation.

    C'est l'autre moitié de la règle « un contenu non validé ne peut pas être
    diffusé » : il faut bien un endroit où quelqu'un valide, sans quoi la règle
    bloquerait tout et serait contournée dans les six mois.

    Réservé au ministère. Une délégation qui validerait ses propres contenus
    produirait dix curriculums différents, et le programme cesserait d'être
    national.
--}}
<x-layouts.delegation titre="Bibliothèque">

    <div x-data="bibliotheque" x-cloak class="space-y-8">

        <div>
            <h1 class="text-3xl">Bibliothèque</h1>
            <p class="mt-2 max-w-prose text-white-dark">
                Les contenus du programme et les langues dans lesquelles ils existent.
                Rien ne part sur le terrain avant d'être validé ici.
            </p>
        </div>

        <p x-show="chargement && ! contenusParents" class="text-white-dark">Chargement…</p>
        <p x-show="erreur" x-text="erreur" class="panel border-l-4 border-warning"></p>

        <template x-if="contenusParents">
            <div class="space-y-8">

                {{-- ---------------------------------------------------- --}}
                {{-- La file de validation. C'est ce qui appelle une       --}}
                {{-- décision : elle vient donc en premier.                --}}
                <section>
                    <h2 class="text-xl">Ce qui attend votre validation</h2>

                    <template x-if="file.length === 0">
                        <div class="panel mt-3">
                            <p class="text-base">
                                Rien n'attend. Tous les contenus du programme sont validés.
                            </p>
                        </div>
                    </template>

                    <div class="mt-3 space-y-3">
                        <template x-for="m in file" x-bind:key="m.code">
                            <div class="panel border-l-4 border-warning">
                                <div class="flex flex-wrap items-baseline justify-between gap-2">
                                    <p class="text-lg font-semibold [font-family:var(--font-titre)]"
                                       x-text="m.titre"></p>
                                    <span class="badge badge-alerte" x-text="m.statut_libelle"></span>
                                </div>

                                <p class="mt-1 text-base text-white-dark">
                                    <span x-text="m.type_libelle"></span> ·
                                    <span class="chiffre" x-text="m.sections"></span> sections ·
                                    <span class="chiffre" x-text="m.code"></span>
                                </p>

                                <p class="mt-2 max-w-prose text-base" x-text="m.objectif"></p>

                                <div class="mt-3 flex flex-wrap gap-2">
                                    <button type="button" class="btn btn-primary"
                                            x-on:click="valider(m.code, 'valide')"
                                            x-bind:disabled="occupe">
                                        Valider et diffuser
                                    </button>
                                    <button type="button" class="btn btn-neutre"
                                            x-on:click="valider(m.code, 'brouillon')"
                                            x-bind:disabled="occupe">
                                        Renvoyer en brouillon
                                    </button>
                                </div>
                            </div>
                        </template>
                    </div>
                </section>

                {{-- ---------------------------------------------------- --}}
                {{-- Les langues.                                          --}}
                <section>
                    <h2 class="text-xl">Les langues du programme</h2>
                    <p class="mt-1 max-w-prose text-base text-white-dark">
                        Ajouter une langue ne suffit pas à la rendre disponible : il faut
                        charger les réalisations correspondantes. Désactiver une langue la
                        retire de l'interface <strong>sans rien supprimer</strong>.
                    </p>

                    <div class="panel mt-3 overflow-x-auto">
                        <table class="tableau">
                            <thead>
                                <tr>
                                    <th>Langue</th>
                                    <th>Code</th>
                                    <th class="text-right">Réalisations</th>
                                    <th>État</th>
                                    <th></th>
                                </tr>
                            </thead>
                            <tbody>
                                <template x-for="l in langues" x-bind:key="l.id">
                                    <tr>
                                        <td>
                                            <span class="font-semibold" x-text="l.nom"></span>
                                            <span class="block text-sm text-white-dark"
                                                  x-show="l.nom !== l.libelle" x-text="l.libelle"></span>
                                        </td>
                                        <td class="chiffre" x-text="l.code"></td>
                                        <td class="chiffre text-right"
                                            x-bind:class="l.realisations === 0 ? 'text-warning-texte' : ''"
                                            x-text="l.realisations"></td>
                                        <td>
                                            <span class="badge"
                                                  x-bind:class="l.actif ? 'badge-succes' : 'badge-neutre'"
                                                  x-text="l.actif ? 'Active' : 'Retirée'"></span>
                                        </td>
                                        <td class="text-right">
                                            <button type="button" class="btn btn-neutre"
                                                    x-on:click="basculerLangue(l)"
                                                    x-bind:disabled="occupe"
                                                    x-text="l.actif ? 'Retirer' : 'Remettre'"></button>
                                        </td>
                                    </tr>
                                </template>
                            </tbody>
                        </table>
                    </div>

                    <div class="panel mt-3">
                        <p class="etiquette">Enregistrer une langue</p>

                        <div class="grid grid-cols-1 gap-3 sm:grid-cols-3">
                            <div>
                                <label for="code" class="etiquette">Code</label>
                                <input id="code" x-model="nouvelleLangue.code" type="text"
                                       maxlength="20" placeholder="ful" class="champ chiffre">
                            </div>
                            <div>
                                <label for="libelle" class="etiquette">Nom en français</label>
                                <input id="libelle" x-model="nouvelleLangue.libelle" type="text"
                                       maxlength="80" placeholder="Fulfulde" class="champ">
                            </div>
                            <div>
                                <label for="endonyme" class="etiquette">
                                    Nom dans la langue
                                </label>
                                <input id="endonyme" x-model="nouvelleLangue.endonyme" type="text"
                                       maxlength="80" placeholder="Fulfulde" class="champ">
                            </div>
                        </div>

                        <p class="mt-1 text-sm text-white-dark">
                            C'est le nom dans la langue qui s'affiche au parent : personne ne
                            cherche « Fulfulde » écrit en français quand il ne lit pas le français.
                        </p>

                        <button type="button" class="btn btn-primary mt-3"
                                x-on:click="ajouterUneLangue()"
                                x-bind:disabled="! peutAjouterUneLangue || occupe">
                            Enregistrer la langue
                        </button>
                    </div>
                </section>

                {{-- ---------------------------------------------------- --}}
                {{-- Les deux catalogues, et la couverture par langue.      --}}
                <section>
                    <h2 class="text-xl">Les deux catalogues</h2>
                    <p class="mt-1 max-w-prose text-base text-white-dark">
                        Ils ne servent pas le même public : mélanger leurs chiffres ferait
                        croire qu'un module de formation touche des parents.
                    </p>

                    <dl class="mt-3 grid grid-cols-2 gap-3 lg:grid-cols-4">
                        <template x-for="c in [
                            { t: 'Unités parents', v: contenusParents.unites },
                            { t: 'Réalisations', v: contenusParents.realisations },
                            { t: 'Modules facilitateur', v: contenusFacilitateurs.modules },
                            { t: 'Dont diffusables', v: contenusFacilitateurs.diffusables },
                        ]" x-bind:key="c.t">
                            <div class="panel">
                                <dt class="intitule text-white-dark" x-text="c.t"></dt>
                                <dd class="chiffre mt-1 text-3xl" x-text="c.v"></dd>
                            </div>
                        </template>
                    </dl>

                    {{-- Le seul chiffre qui dise où porter l'effort. --}}
                    <div class="panel mt-3 overflow-x-auto">
                        <p class="etiquette">Couverture des unités, langue par langue</p>

                        <table class="tableau">
                            <thead>
                                <tr>
                                    <th>Langue</th>
                                    <th class="text-right">Unités couvertes</th>
                                    <th class="text-right">Manquantes</th>
                                </tr>
                            </thead>
                            <tbody>
                                <template x-for="c in contenusParents.couverture" x-bind:key="c.langue">
                                    <tr>
                                        <td class="font-semibold" x-text="c.nom"></td>
                                        <td class="chiffre text-right">
                                            <span x-text="c.unites_couvertes"></span><span
                                                  class="text-white-dark"
                                                  x-text="'/' + c.unites_total"></span>
                                        </td>
                                        <td class="chiffre text-right"
                                            x-bind:class="c.manquantes > 0 ? 'text-warning-texte font-semibold' : ''"
                                            x-text="c.manquantes"></td>
                                    </tr>
                                </template>
                            </tbody>
                        </table>

                        <p class="mt-3 max-w-prose text-sm text-white-dark">
                            Une unité chargée en français et pas en bulu n'atteint pas les
                            locuteurs bulu, quel que soit le nombre total de réalisations.
                        </p>
                    </div>
                </section>
            </div>
        </template>
    </div>
</x-layouts.delegation>
