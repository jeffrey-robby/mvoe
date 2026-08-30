{{--
    Signaler une situation préoccupante, et lire la suite qui y a été donnée.

    Trois choses que cet écran dit explicitement, parce qu'elles décident de
    l'usage qu'on en fera :

     1. Le signalement REMONTE. Il entre dans la file du superviseur, qui juge
        et décide. Aucune autorité n'est prévenue automatiquement — une alerte
        automatique préviendrait avant que quiconque ait vérifié, et parfois
        elle préviendrait l'agresseur.
     2. Aucune identité n'est demandée. Il n'y a aucun champ où mettre un nom.
     3. La suite donnée est TOUJOURS visible. Un signalement sans retour est un
        signalement qu'on ne refait pas.
--}}
<x-layouts.kit titre="Signaler">

    <div x-data="signalerTerrain" x-cloak class="space-y-6">

        <template x-if="! enregistre">
            <div class="space-y-5">

                <div>
                    <h1 class="text-3xl">Signaler une situation</h1>
                    <p class="mt-2 text-gris-texte">
                        Votre superviseur la recevra et décidera de la suite. Personne
                        d'autre n'est prévenu, et rien n'est envoyé automatiquement.
                    </p>
                </div>

                <div class="rounded-carte bg-jaune-sourd px-4 py-3">
                    <p class="text-base">
                        <strong>Ne mettez aucun nom.</strong> Ni celui de l'enfant, ni celui
                        du parent, ni celui du foyer. C'est ce qui vous permet de signaler
                        sans exposer qui que ce soit.
                    </p>
                </div>

                <fieldset>
                    <legend class="intitule">De quoi s'agit-il</legend>
                    <div class="mt-2 grid grid-cols-1 gap-2">
                        <template x-for="t in types" x-bind:key="t.valeur">
                            <button type="button" x-on:click="type = t.valeur"
                                    x-bind:aria-pressed="type === t.valeur"
                                    x-bind:class="type === t.valeur ? 'bg-noir text-blanc' : 'bg-blanc text-noir'"
                                    class="min-h-tactile rounded-net border-2 border-noir px-4 text-left text-base font-semibold [font-family:var(--font-titre)]"
                                    x-text="t.libelle"></button>
                        </template>
                    </div>
                </fieldset>

                <fieldset>
                    <legend class="intitule">Gravité</legend>
                    <div class="mt-2 flex flex-wrap gap-2">
                        <template x-for="g in gravites" x-bind:key="g.valeur">
                            <button type="button" x-on:click="gravite = g.valeur"
                                    x-bind:aria-pressed="gravite === g.valeur"
                                    x-bind:class="gravite === g.valeur ? 'bg-noir text-blanc' : 'bg-blanc text-noir'"
                                    class="min-h-tactile flex-1 rounded-net border-2 border-noir px-4 text-base font-semibold [font-family:var(--font-titre)]"
                                    x-text="g.libelle"></button>
                        </template>
                    </div>
                </fieldset>

                <div class="flex flex-wrap gap-2">
                    <x-mvoe.bouton x-on:click="valider()" x-bind:disabled="occupe">
                        <span x-text="occupe ? 'Un instant…' : 'Envoyer à mon superviseur'">
                            Envoyer à mon superviseur
                        </span>
                    </x-mvoe.bouton>
                    <x-mvoe.bouton variante="second" href="/kit">Annuler</x-mvoe.bouton>
                </div>
            </div>
        </template>

        <template x-if="enregistre">
            <div class="space-y-5">
                <x-mvoe.carte rappel>
                    <p class="text-xl [font-family:var(--font-titre)] font-semibold">
                        Signalement enregistré.
                    </p>
                    <p class="mt-2 text-base">
                        Il partira vers votre superviseur dès que vous retrouverez du réseau.
                        Vous verrez ici ce qu'il en aura fait.
                    </p>
                </x-mvoe.carte>

                <div class="flex flex-wrap gap-2">
                    <x-mvoe.bouton x-on:click="recommencer()">En signaler un autre</x-mvoe.bouton>
                    <x-mvoe.bouton variante="second" href="/kit">Revenir à mon kit</x-mvoe.bouton>
                </div>
            </div>
        </template>

        {{-- ------------------------------------------------------------ --}}
        {{-- La suite donnée. C'est ce qui décide s'il en fera un deuxième. --}}
        <section x-show="! chargement">
            <x-mvoe.intitule>Mes signalements</x-mvoe.intitule>

            <div class="mt-2" x-show="horsLigne">
                <x-mvoe.vide>
                    La réponse de votre superviseur demande du réseau.
                </x-mvoe.vide>
            </div>

            <div class="mt-2" x-show="! horsLigne && miens.length === 0">
                <x-mvoe.vide>Vous n'avez encore rien signalé.</x-mvoe.vide>
            </div>

            <div class="mt-2 space-y-2" x-show="! horsLigne && miens.length > 0">
                <template x-for="s in miens" x-bind:key="s.uuid">
                    <div class="rounded-carte border border-ligne p-4">
                        <div class="flex flex-wrap items-baseline justify-between gap-2">
                            <p class="text-lg font-semibold [font-family:var(--font-titre)]"
                               x-text="s.type_libelle"></p>
                            <p class="chiffre text-sm"
                               x-text="s.soumis_le.split('-').reverse().join('/')"></p>
                        </div>

                        <p class="mt-1 text-base text-gris-texte">
                            Gravité <span x-text="s.gravite_libelle.toLowerCase()"></span> ·
                            <span x-text="s.statut_libelle"></span>
                        </p>

                        {{-- Le retour, en toutes lettres. --}}
                        <div class="mt-3" x-show="s.suite_donnee">
                            <p class="intitule text-xs">Suite donnée</p>
                            <p class="mt-1 text-base" x-text="s.suite_donnee"></p>
                        </div>

                        <p class="mt-3 text-base" x-show="! s.suite_donnee">
                            En attente depuis
                            <span class="chiffre" x-text="s.jours_attente"></span> jours.
                            Votre superviseur ne l'a pas encore traité.
                        </p>
                    </div>
                </template>
            </div>
        </section>
    </div>
</x-layouts.kit>
