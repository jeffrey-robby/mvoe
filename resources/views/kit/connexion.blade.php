{{--
    Connexion du facilitateur.

    Deux voies vers le même compte : le numéro et le code d'appareil sur le
    terrain, l'e-mail et le mot de passe depuis un poste de la délégation.
    Le numéro est proposé en premier parce que c'est le cas courant.

    C'est le SEUL écran du kit qui demande du réseau, et une seule fois.
--}}
<x-layouts.kit titre="Connexion">

    <div x-data="connexion" class="mx-auto max-w-md">

        <h1 class="text-3xl">Ouvrir mon kit</h1>
        <p class="mt-2 text-gris-texte">
            Vos identifiants vous ont été remis à la formation.
        </p>

        <form x-on:submit.prevent="valider()" class="mt-6 space-y-4">

            {{-- Voie 1 : le terrain --}}
            <template x-if="! parEmail">
                <div class="space-y-4">
                    <div>
                        <label for="telephone" class="intitule block">Numéro de téléphone</label>
                        <input id="telephone" x-model="telephone" type="tel" inputmode="tel"
                               autocomplete="tel" required
                               class="mt-1 min-h-tactile w-full rounded-net border-2 border-noir px-3 text-base">
                    </div>

                    <div>
                        <label for="code" class="intitule block">Code d'appareil</label>
                        <input id="code" x-model="codeAppareil" type="password" inputmode="numeric"
                               autocomplete="off" required
                               class="chiffre mt-1 min-h-tactile w-full rounded-net border-2 border-noir px-3 text-lg tracking-[0.4em]">
                    </div>
                </div>
            </template>

            {{-- Voie 2 : la délégation --}}
            <template x-if="parEmail">
                <div class="space-y-4">
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
                </div>
            </template>

            {{-- Le message d'erreur ne dit jamais lequel des deux champs est
                 faux : inutile d'apprendre à qui essaie qu'un numéro existe. --}}
            <p x-cloak x-show="erreur" x-text="erreur"
               class="rounded-net bg-jaune-sourd px-3 py-2 text-sm"></p>

            <x-mvoe.bouton type="submit" class="w-full" x-bind:disabled="occupe">
                <span x-text="occupe ? 'Un instant…' : 'Ouvrir mon kit'">Ouvrir mon kit</span>
            </x-mvoe.bouton>

            <x-mvoe.bouton variante="discret" class="w-full"
                           x-on:click="parEmail = ! parEmail; erreur = null">
                <span x-text="parEmail
                    ? 'Utiliser mon numéro et mon code d\'appareil'
                    : 'Utiliser mon e-mail et mon mot de passe'">…</span>
            </x-mvoe.bouton>
        </form>
    </div>
</x-layouts.kit>
