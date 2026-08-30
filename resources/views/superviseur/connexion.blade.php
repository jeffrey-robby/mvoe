{{--
    Connexion des comptes de l'administration.

    Un seul écran pour les quatre niveaux : ministère, région, département,
    arrondissement. C'est la portée du compte qui décide de ce qu'il voit,
    jamais la page par laquelle il est entré.

    C'est le seul compte du système avec un mot de passe classique : le
    facilitateur ouvre un kit sur un téléphone, le parent entre un code à
    quatre chiffres reçu en main propre.
--}}
<x-layouts.delegation titre="Connexion" :navigation="false">

    <div x-data="connexionDelegation" class="mx-auto max-w-md py-6">

        <div class="mb-5 flex items-center gap-3">
            <span class="grid h-11 w-11 place-items-center rounded-md bg-primary
                         [font-family:var(--font-titre)] text-xl font-bold text-white">M</span>
            <span class="[font-family:var(--font-titre)] text-2xl font-bold">Mvoé</span>
        </div>

        <div class="panel">
            <h1 class="text-2xl">Ouvrir une session</h1>

            {{-- Le même écran sert les cinq niveaux : ce que chacun verra
                 ensuite dépend de sa portée, pas de la page où il s'est connecté. --}}
            <p class="mt-2 text-white-dark">
                Ministère, délégation régionale, départementale ou d'arrondissement.
            </p>

            <form x-on:submit.prevent="valider()" class="mt-5 space-y-4">
                <div>
                    <label for="email" class="etiquette">Adresse e-mail</label>
                    <input id="email" x-model="email" type="email" autocomplete="email" required
                           class="champ">
                </div>

                <div>
                    <label for="mdp" class="etiquette">Mot de passe</label>
                    <input id="mdp" x-model="motDePasse" type="password"
                           autocomplete="current-password" required
                           class="champ">
                </div>

                <p x-cloak x-show="erreur" x-text="erreur"
                   class="rounded-md border-l-4 border-danger bg-danger-light
                          px-3 py-2 text-sm text-danger-texte"></p>

                <button type="submit" class="btn btn-primary w-full" x-bind:disabled="occupe">
                    <span x-text="occupe ? 'Un instant…' : 'Ouvrir la session'">Ouvrir la session</span>
                </button>

                <p class="text-sm text-white-dark">
                    La session se ferme à la fermeture de l'onglet. Ce poste est souvent partagé.
                </p>
            </form>
        </div>
    </div>
</x-layouts.delegation>
