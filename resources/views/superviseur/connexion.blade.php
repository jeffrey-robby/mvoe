{{--
    Connexion de la délégation d'arrondissement.

    C'est le seul compte du système avec un mot de passe classique : le
    facilitateur ouvre un kit sur un téléphone, le parent entre un code à
    quatre chiffres reçu en main propre.
--}}
<x-layouts.delegation titre="Connexion" :navigation="false">

    <div x-data="connexionDelegation" class="mx-auto max-w-md">

        <h1 class="text-3xl">Délégation d'arrondissement</h1>
        <p class="mt-2 text-gris-texte">
            Registre des facilitateurs, rapport trimestriel, paramètres des cohortes.
        </p>

        <form x-on:submit.prevent="valider()" class="mt-6 space-y-4">
            <div>
                <label for="email" class="intitule block">Adresse e-mail</label>
                <input id="email" x-model="email" type="email" autocomplete="email" required
                       class="mt-1 min-h-tactile w-full rounded-net border-2 border-noir px-3 text-base">
            </div>

            <div>
                <label for="mdp" class="intitule block">Mot de passe</label>
                <input id="mdp" x-model="motDePasse" type="password"
                       autocomplete="current-password" required
                       class="mt-1 min-h-tactile w-full rounded-net border-2 border-noir px-3 text-base">
            </div>

            <p x-cloak x-show="erreur" x-text="erreur"
               class="rounded-net bg-jaune-sourd px-3 py-2 text-sm"></p>

            <x-mvoe.bouton type="submit" class="w-full" x-bind:disabled="occupe">
                <span x-text="occupe ? 'Un instant…' : 'Ouvrir la session'">Ouvrir la session</span>
            </x-mvoe.bouton>

            <p class="text-sm text-gris-texte">
                La session se ferme à la fermeture de l'onglet. Ce poste est souvent partagé.
            </p>
        </form>
    </div>
</x-layouts.delegation>
