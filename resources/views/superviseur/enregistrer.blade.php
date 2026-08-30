{{--
    Enregistrement d'un facilitateur.

    Un facilitateur ne s'inscrit jamais lui-même : son superviseur l'enregistre
    et lui remet ses identifiants en main propre.

    Le formulaire ne demande NI arrondissement NI mot de passe. Le premier est
    celui du superviseur connecté, le second est généré côté serveur. Rien ici
    ne permet de créer un facilitateur ailleurs que chez soi.
--}}
<x-layouts.delegation titre="Enregistrer un facilitateur">

    <div x-data="enregistrerFacilitateur" x-cloak class="space-y-6">

        {{-- ---------------------------------------------------------------- --}}
        {{-- Les niveaux au-dessus de l'arrondissement lisent, ils n'enrôlent   --}}
        {{-- pas : le rattachement d'un facilitateur se décide là où quelqu'un --}}
        {{-- l'a vu animer. Le serveur le refuse ; l'écran l'explique.          --}}
        <template x-if="niveau && ! peutEnregistrer">
            <div class="max-w-xl space-y-4">
                <h1 class="text-3xl">Enregistrer un facilitateur</h1>

                <div class="panel border-l-4 border-warning">
                    <p class="text-base">
                        Un facilitateur est enregistré par la délégation
                        d'arrondissement où il anime, jamais depuis un niveau
                        supérieur : c'est elle qui l'a vu travailler et qui lui
                        remettra ses identifiants en main propre.
                    </p>
                    <p class="mt-2 text-base text-white-dark">
                        Votre compte couvre <span class="font-semibold" x-text="portee"></span>.
                        Vous en lisez le registre et le rapport.
                    </p>
                </div>

                <a href="/superviseur" class="btn btn-neutre">Revenir au registre</a>
            </div>
        </template>

        {{-- ---------------------------------------------------------------- --}}
        {{-- Le formulaire.                                                    --}}
        <template x-if="peutEnregistrer && ! resultat">
            <div class="space-y-6">
                <div>
                    <h1 class="text-3xl">Enregistrer un facilitateur</h1>
                    <p class="mt-2 max-w-prose text-white-dark">
                        Il sera rattaché à votre arrondissement. Ses identifiants seront générés et
                        affichés une seule fois : prévoyez de quoi les noter.
                    </p>
                </div>

                <p x-show="erreur" x-text="erreur"
                   class="panel border-l-4 border-warning"></p>

                <form x-on:submit.prevent="valider()" class="max-w-xl space-y-4">
                    <div>
                        <label for="nom" class="etiquette">Nom et prénom</label>
                        <input id="nom" x-model="nom" type="text" required
                               class="champ">
                        <p x-show="erreurs.nom" x-text="erreurs.nom?.[0]" class="mt-1 text-sm text-danger-texte"></p>
                    </div>

                    <div>
                        <label for="tel" class="etiquette">Téléphone</label>
                        <input id="tel" x-model="telephone" type="tel" inputmode="tel" required
                               placeholder="699 00 00 00"
                               class="champ chiffre">
                        <p x-show="erreurs.telephone" x-text="erreurs.telephone?.[0]"
                           class="mt-1 text-sm text-danger-texte"></p>
                    </div>

                    <div>
                        <label for="email" class="etiquette">
                            Adresse e-mail <span class="text-white-dark">— facultative</span>
                        </label>
                        <input id="email" x-model="email" type="email"
                               class="champ">
                        <p class="mt-1 text-sm text-white-dark">
                            Laissez vide : elle sera dérivée de son nom.
                        </p>
                    </div>

                    {{-- Ce n'est pas décoratif : c'est ce qui permettra de savoir
                         quel type de facilitateur reste actif le plus longtemps. --}}
                    <div>
                        <label for="type" class="etiquette">Type juridique</label>
                        <select id="type" x-model="typeJuridique" required
                                class="champ">
                            <option value="">Choisir…</option>
                            <template x-for="t in types" x-bind:key="t.valeur">
                                <option x-bind:value="t.valeur" x-text="t.libelle"></option>
                            </template>
                        </select>
                    </div>

                    <div>
                        <label for="orga" class="etiquette">
                            Organisation de rattachement <span class="text-white-dark">— facultative</span>
                        </label>
                        <input id="orga" x-model="organisation" type="text"
                               class="champ">
                    </div>

                    <div>
                        <label for="formation" class="etiquette">Date de formation initiale</label>
                        <input id="formation" x-model="dateFormation" type="date" required
                               class="champ chiffre">
                    </div>

                    <button type="submit" class="btn btn-primary" x-bind:disabled="! peutValider || occupe">
                        <span x-text="occupe ? 'Un instant…' : 'Enregistrer le facilitateur'">
                            Enregistrer le facilitateur
                        </span>
                    </button>
                </form>
            </div>
        </template>

        {{-- ---------------------------------------------------------------- --}}
        {{-- La remise des identifiants.                                       --}}
        {{--                                                                   --}}
        {{-- L'écran le plus important de cette page. Ces valeurs n'existent    --}}
        {{-- en clair qu'ici : en base tout est haché, et personne ne pourra    --}}
        {{-- les relire. Grands caractères en Plex Mono, sans caractères        --}}
        {{-- ambigus, parce qu'ils vont être recopiés à la main.                --}}
        <template x-if="resultat">
            <div class="space-y-5">
                <div>
                    <h1 class="text-3xl">Identifiants à remettre</h1>
                    <p class="mt-2 text-lg">
                        <span x-text="resultat.facilitateur.nom"></span> ·
                        <span x-text="resultat.facilitateur.arrondissement"></span>
                    </p>
                </div>

                <div class="panel">
                    <p class="intitule">Sur le terrain — pour ouvrir son kit</p>
                    <dl class="mt-2 space-y-2">
                        <div class="flex flex-wrap items-baseline gap-x-4">
                            <dt class="w-40 text-base text-white-dark">Téléphone</dt>
                            <dd class="chiffre text-2xl" x-text="resultat.identifiants.telephone"></dd>
                        </div>
                        <div class="flex flex-wrap items-baseline gap-x-4">
                            <dt class="w-40 text-base text-white-dark">Code d'appareil</dt>
                            <dd class="chiffre text-2xl tracking-[0.3em]"
                                x-text="resultat.identifiants.code_appareil"></dd>
                        </div>
                    </dl>

                    <p class="intitule mt-6">Depuis un poste — accès classique</p>
                    <dl class="mt-2 space-y-2">
                        <div class="flex flex-wrap items-baseline gap-x-4">
                            <dt class="w-40 text-base text-white-dark">Adresse e-mail</dt>
                            <dd class="chiffre text-xl" x-text="resultat.identifiants.email"></dd>
                        </div>
                        <div class="flex flex-wrap items-baseline gap-x-4">
                            <dt class="w-40 text-base text-white-dark">Mot de passe</dt>
                            <dd class="chiffre text-2xl tracking-[0.15em]"
                                x-text="resultat.identifiants.mot_de_passe"></dd>
                        </div>
                    </dl>
                </div>

                <p class="panel border-l-4 border-danger text-base"
                   x-text="resultat.avertissement"></p>

                <div class="sans-impression flex flex-wrap gap-2">
                    <button type="button" class="btn btn-primary" x-on:click="imprimer()">Imprimer la fiche</button>
                    <button type="button" class="btn btn-neutre" x-on:click="recommencer()">
                        Enregistrer un autre facilitateur
                    </button>
                    <a href="/superviseur" class="btn btn-neutre">
                        Revenir au registre
                    </a>
                </div>
            </div>
        </template>
    </div>
</x-layouts.delegation>
