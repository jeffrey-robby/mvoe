<x-layouts.parent titre="Espace parent" composant="entreeParent" :barre="false" :plein="true">

    <div class="absolute inset-0">
        <img src="/assets/images/auth/bg-gradient.png" alt="image" class="h-full w-full object-cover" />
    </div>

    <div class="relative flex min-h-screen items-center justify-center bg-[url(/assets/images/auth/map.png)] bg-cover bg-center bg-no-repeat px-6 py-10 sm:px-16">
        <img src="/assets/images/auth/coming-soon-object1.png" alt="image" class="absolute left-0 top-1/2 h-full max-h-[893px] -translate-y-1/2" />
        <img src="/assets/images/auth/coming-soon-object2.png" alt="image" class="absolute left-24 top-0 h-40 md:left-[30%]" />
        <img src="/assets/images/auth/coming-soon-object3.png" alt="image" class="absolute right-0 top-0 h-[300px]" />
        <img src="/assets/images/auth/polygon-object.svg" alt="image" class="absolute bottom-0 end-[28%]" />

        <div class="relative w-full max-w-[870px] rounded-md bg-[linear-gradient(45deg,#fff9f9_0%,rgba(255,255,255,0)_25%,rgba(255,255,255,0)_75%,_#fff9f9_100%)] p-2">
            <div class="relative flex flex-col justify-center rounded-md bg-white/60 backdrop-blur-lg px-6 lg:min-h-[758px] py-20">

                <div class="absolute top-6 start-6 flex items-center gap-3">
                    <img src="/icones/mvoe-512.png" alt="Mvoé" class="w-10 h-10 rounded-lg" />
                    <div class="leading-tight">
                        <div class="text-lg font-extrabold text-black">Mvoé</div>
                        <div class="text-[10px] font-semibold uppercase tracking-wider text-white-dark">Espace parent</div>
                    </div>
                </div>

                <div class="mx-auto w-full max-w-[440px]">

                    {{-- 1. La langue. Chaque option est énoncée à voix haute :
                         on ne peut pas demander à quelqu'un de lire « Français »
                         dans une langue qu'il n'a pas encore choisie. --}}
                    <template x-if="etape === 'langue'">
                        <div>
                            <div class="mb-10">
                                <h1 class="text-3xl font-extrabold uppercase !leading-snug text-primary md:text-4xl">Votre langue</h1>
                                <p class="text-base font-bold leading-normal text-white-dark">Choisissez, ou appuyez sur le haut-parleur pour écouter.</p>
                            </div>

                            <div class="space-y-3">
                                <template x-for="l in langues" x-bind:key="l.code">
                                    <div class="flex gap-2">
                                        <button type="button" x-on:click="choisirLangue(l.code)"
                                                class="btn btn-outline-primary btn-lg flex-1 justify-start"
                                                x-text="l.nom"></button>

                                        <button type="button" x-on:click="ecouterLangue(l.code)"
                                                class="btn btn-primary btn-lg w-16 shrink-0"
                                                x-bind:aria-label="'Écouter : ' + l.nom">
                                            <svg class="w-6 h-6" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true">
                                                <path d="M4 9v6h4l5 4V5L8 9H4z" />
                                            </svg>
                                        </button>
                                    </div>
                                </template>
                            </div>
                        </div>
                    </template>

                    {{-- 2. La voie. Consulter d'abord, le code ensuite : il ne
                         sert qu'à ce qui s'attache à une personne. Une mère sans
                         code ne doit pas repartir en croyant que ce n'est pas
                         pour elle. --}}
                    <template x-if="etape === 'acces'">
                        <div>
                            <div class="mb-10">
                                <h1 class="text-3xl font-extrabold uppercase !leading-snug text-primary md:text-4xl">Bienvenue</h1>
                                <p class="text-base font-bold leading-normal text-white-dark">
                                    Les contenus du programme sont ouverts à tous.
                                </p>
                            </div>

                            <button type="button" x-on:click="consulterSansCompte()"
                                    class="btn btn-gradient btn-lg w-full border-0 uppercase shadow-[0_10px_20px_-10px_rgba(67,97,238,0.44)]">
                                Consulter les contenus
                            </button>

                            <p class="text-center text-white-dark mt-3">
                                Sans code, sans inscription. Écoutez, lisez, revenez quand vous voulez.
                            </p>

                            <div class="relative my-7 text-center md:mb-9">
                                <span class="absolute inset-x-0 top-1/2 h-px w-full -translate-y-1/2 bg-white-light"></span>
                                <span class="relative bg-white px-2 font-bold uppercase text-white-dark">Vous avez un code ?</span>
                            </div>

                            <button type="button" x-on:click="ouvrirLeCode()"
                                    class="btn btn-outline-primary btn-lg w-full">
                                Ouvrir ma session
                            </button>

                            <p class="text-center text-white-dark mt-3">
                                Votre facilitateur vous l'a remis en main propre. Il sert à répondre
                                aux questions de la semaine et à recevoir les contenus dans votre langue.
                            </p>

                            <button type="button" x-on:click="etape = 'langue'"
                                    class="btn btn-outline-primary w-full mt-8">
                                Changer de langue
                            </button>
                        </div>
                    </template>

                    {{-- 3. Les codes. --}}
                    <template x-if="etape === 'code' && ! refusMineur">
                        <div>
                            <div class="mb-10">
                                <div class="flex items-start justify-between gap-3">
                                    <h1 class="text-3xl font-extrabold uppercase !leading-snug text-primary md:text-4xl">Vos codes</h1>
                                    <button type="button" x-on:click="ecouterConsigne()"
                                            class="btn btn-primary w-14 h-14 shrink-0"
                                            aria-label="Écouter la consigne">
                                        <svg class="w-6 h-6" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true">
                                            <path d="M4 9v6h4l5 4V5L8 9H4z" />
                                        </svg>
                                    </button>
                                </div>
                                <p class="text-base font-bold leading-normal text-white-dark">
                                    Votre facilitateur vous les a remis en main propre.
                                </p>
                            </div>

                            <form class="space-y-5" x-on:submit.prevent="valider()">
                                <div>
                                    <label for="code-parent">Code parent</label>
                                    <input id="code-parent" x-model="codeParent" type="text"
                                           autocomplete="off" autocapitalize="characters" required
                                           placeholder="EB2-00" class="form-input form-input-lg chiffre">
                                </div>

                                <div>
                                    <label for="code-acces">Code à 4 chiffres</label>
                                    <input id="code-acces" x-model="codeAcces" type="password"
                                           inputmode="numeric" maxlength="4" autocomplete="off" required
                                           class="form-input form-input-lg chiffre tracking-[0.5em]">
                                </div>

                                {{-- Deux choix explicites, et non une case à cocher :
                                     déclarer qu'on a moins de 18 ans doit être
                                     possible, et doit ORIENTER vers un facilitateur.
                                     Une case laisserait un mineur bloqué sans
                                     comprendre pourquoi, ce qui n'aide personne. --}}
                                <div>
                                    <label>Votre âge</label>
                                    <div class="flex flex-wrap gap-2">
                                        <button type="button" class="btn flex-1" x-on:click="declarerAge('majeur')"
                                                x-bind:class="age === 'majeur' ? 'btn-primary' : 'btn-outline-primary'"
                                                x-bind:aria-pressed="age === 'majeur'">18 ans ou plus</button>

                                        <button type="button" class="btn flex-1" x-on:click="declarerAge('mineur')"
                                                x-bind:class="age === 'mineur' ? 'btn-primary' : 'btn-outline-primary'"
                                                x-bind:aria-pressed="age === 'mineur'">Moins de 18 ans</button>
                                    </div>
                                </div>

                                <div x-cloak x-show="erreur"
                                     class="flex items-center rounded border border-danger bg-danger-light p-3.5 text-danger">
                                    <span x-text="erreur"></span>
                                </div>

                                <button type="submit" x-bind:disabled="! peutValider || occupe"
                                        class="btn btn-gradient btn-lg !mt-6 w-full border-0 uppercase shadow-[0_10px_20px_-10px_rgba(67,97,238,0.44)]">
                                    <span x-text="occupe ? 'Un instant…' : 'Entrer'">Entrer</span>
                                </button>
                            </form>

                            <button type="button" x-on:click="etape = 'acces'"
                                    class="btn btn-outline-primary w-full mt-6">
                                Consulter sans code
                            </button>
                        </div>
                    </template>

                    {{-- 4. Le refus des moins de 18 ans. Ce n'est pas une erreur
                         et ce n'est pas un reproche : la loi exige le consentement
                         du représentant légal, que ce canal ne permet pas de
                         recueillir. On oriente vers la seule personne qui peut
                         aider — et les contenus restent ouverts. --}}
                    <template x-if="refusMineur">
                        <div>
                            <div class="mb-10">
                                <h1 class="text-3xl font-extrabold uppercase !leading-snug text-primary md:text-4xl">Voyez votre facilitateur</h1>
                                <p class="text-base font-bold leading-normal text-white-dark">
                                    L'ouverture de session est réservée aux personnes de 18 ans ou plus.
                                </p>
                            </div>

                            <div class="flex items-center rounded border border-warning bg-warning-light p-3.5 text-warning mb-6">
                                <span>
                                    Votre facilitateur peut vous accompagner autrement : il anime la
                                    séance chaque semaine, et il répondra à vos questions sur place.
                                </span>
                            </div>

                            <a href="/parent/facilitateur" class="btn btn-primary btn-lg w-full">
                                Trouver un facilitateur
                            </a>

                            <button type="button" x-on:click="refusMineur = false; age = null; etape = 'acces'"
                                    class="btn btn-outline-primary w-full mt-3">
                                Revenir
                            </button>
                        </div>
                    </template>
                </div>
            </div>
        </div>
    </div>
</x-layouts.parent>
