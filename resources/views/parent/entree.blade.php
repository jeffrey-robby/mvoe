{{--
    Entrée dans l'espace parent.

    La langue se choisit EN PREMIER, et chaque option est énoncée à voix haute :
    on ne peut pas demander à quelqu'un de lire « Français » dans une langue
    qu'il n'a pas encore choisie.

    Puis le code parent et le code à 4 chiffres, remis en main propre par le
    facilitateur. Ni e-mail, ni mot de passe, ni SMS de vérification.
--}}
<x-layouts.parent titre="Entrer" composant="entreeParent" :barre="false">

    <div class="mx-auto max-w-md">

        {{-- ---------------------------------------------------------------- --}}
        {{-- 1. La langue.                                                     --}}
        <template x-if="etape === 'langue'">
            <div>
                <h1 class="text-3xl">Mvoé</h1>
                <p class="mt-2 text-gris-texte">Choisissez votre langue.</p>

                <div class="mt-6 space-y-3">
                    <template x-for="l in langues" x-bind:key="l.code">
                        <div class="flex gap-2">
                            <button type="button" x-on:click="choisirLangue(l.code)"
                                    class="min-h-tactile flex-1 rounded-carte border-[3px] border-noir bg-blanc px-4 py-4 text-left text-2xl font-semibold [font-family:var(--font-titre)] hover:bg-jaune"
                                    x-text="l.libelle"></button>

                            {{-- Chaque option est dite dans sa propre langue. --}}
                            <button type="button" x-on:click="ecouterLangue(l.code)"
                                    class="flex min-h-tactile w-16 items-center justify-center rounded-carte bg-jaune text-noir"
                                    x-bind:aria-label="'Écouter : ' + l.libelle">
                                <svg class="size-7" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true">
                                    <path d="M4 9v6h4l5 4V5L8 9H4z"/>
                                </svg>
                            </button>
                        </div>
                    </template>
                </div>
            </div>
        </template>

        {{-- ---------------------------------------------------------------- --}}
        {{-- 2. Les codes.                                                     --}}
        <template x-if="etape === 'code' && ! refusMineur">
            <div>
                <div class="flex items-start justify-between gap-3">
                    <h1 class="text-3xl">Vos codes</h1>

                    <button type="button" x-on:click="ecouterConsigne()"
                            class="flex min-h-tactile w-16 shrink-0 items-center justify-center rounded-carte bg-jaune text-noir"
                            aria-label="Écouter la consigne">
                        <svg class="size-7" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true">
                            <path d="M4 9v6h4l5 4V5L8 9H4z"/>
                        </svg>
                    </button>
                </div>

                <p class="mt-2 text-gris-texte">
                    Votre facilitateur vous les a remis en main propre.
                </p>

                <form x-on:submit.prevent="valider()" class="mt-6 space-y-5">
                    <div>
                        <label for="code-parent" class="intitule block">Code parent</label>
                        <input id="code-parent" x-model="codeParent" type="text"
                               autocomplete="off" autocapitalize="characters" required
                               placeholder="EB2-00"
                               class="chiffre mt-1 min-h-tactile w-full rounded-net border-[3px] border-noir px-3 py-3 text-2xl">
                    </div>

                    <div>
                        <label for="code-acces" class="intitule block">Code à 4 chiffres</label>
                        <input id="code-acces" x-model="codeAcces" type="password"
                               inputmode="numeric" maxlength="4" autocomplete="off" required
                               class="chiffre mt-1 min-h-tactile w-full rounded-net border-[3px] border-noir px-3 py-3 text-2xl tracking-[0.6em]">
                    </div>

                    {{-- Règle 7. Deux choix explicites, et non une case à
                         cocher : déclarer qu'on a moins de 18 ans doit être
                         possible, et doit ORIENTER vers un facilitateur. Une
                         case laisserait un mineur bloqué sans comprendre
                         pourquoi, ce qui n'aide personne.

                         La loi n° 2024/017 exige le consentement du
                         représentant légal, que ce canal ne permet pas de
                         recueillir. --}}
                    <fieldset class="rounded-carte bg-jaune-sourd p-4">
                        <legend class="intitule px-1">Votre âge</legend>

                        <div class="mt-2 flex flex-col gap-2 sm:flex-row">
                            <button type="button" x-on:click="declarerAge('majeur')"
                                    class="min-h-tactile flex-1 rounded-net border-[3px] border-noir px-3 py-3 text-base font-semibold [font-family:var(--font-titre)]"
                                    x-bind:class="age === 'majeur' ? 'bg-jaune' : 'bg-blanc'"
                                    x-bind:aria-pressed="age === 'majeur'">18 ans ou plus</button>

                            <button type="button" x-on:click="declarerAge('mineur')"
                                    class="min-h-tactile flex-1 rounded-net border-[3px] border-noir bg-blanc px-3 py-3 text-base font-semibold [font-family:var(--font-titre)]"
                                    x-bind:aria-pressed="age === 'mineur'">Moins de 18 ans</button>
                        </div>
                    </fieldset>

                    <p x-show="erreur" x-text="erreur"
                       class="rounded-net border-2 border-noir px-3 py-2 text-base"></p>

                    <x-mvoe.bouton type="submit" class="w-full py-4 text-xl"
                                   x-bind:disabled="! peutValider || occupe">
                        <span x-text="occupe ? 'Un instant…' : 'Entrer'">Entrer</span>
                    </x-mvoe.bouton>
                </form>

                <button type="button" x-on:click="etape = 'langue'"
                        class="mt-4 min-h-tactile text-base underline underline-offset-4">
                    Changer de langue
                </button>
            </div>
        </template>

        {{-- ---------------------------------------------------------------- --}}
        {{-- 3. Le refus des moins de 18 ans.                                  --}}
        {{--                                                                   --}}
        {{-- Ce n'est pas une erreur et ce n'est pas un reproche : la loi exige --}}
        {{-- le consentement du représentant légal, que ce canal ne permet pas  --}}
        {{-- de recueillir. On oriente vers la seule personne qui peut aider.   --}}
        <template x-if="refusMineur">
            <div>
                <h1 class="text-3xl">Voyez votre facilitateur</h1>

                <div class="mt-4 rounded-carte border-[3px] border-noir bg-jaune-sourd p-4">
                    <p class="text-lg">
                        Cet espace est réservé aux personnes de 18 ans ou plus.
                    </p>
                    <p class="mt-3 text-lg">
                        Votre facilitateur peut vous accompagner autrement : il anime la séance
                        chaque semaine, et il répondra à vos questions sur place.
                    </p>
                </div>

                <x-mvoe.bouton variante="second" class="mt-4 w-full py-4"
                               href="/parent/facilitateur">
                    Trouver un facilitateur
                </x-mvoe.bouton>

                <button type="button" x-on:click="refusMineur = false; age = null"
                        class="mt-4 min-h-tactile text-base underline underline-offset-4">
                    Revenir
                </button>
            </div>
        </template>
    </div>
</x-layouts.parent>
