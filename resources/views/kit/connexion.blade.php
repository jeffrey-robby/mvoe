<x-layouts.auth titre="Ouvrir mon kit" entree="resources/js/kit-auth.js">

    <div x-data="connexion">
        <div class="absolute inset-0">
            <img src="/assets/images/auth/bg-gradient.png" alt="image" class="h-full w-full object-cover" />
        </div>

        <div class="relative flex min-h-screen items-center justify-center bg-[url(/assets/images/auth/map.png)] bg-cover bg-center bg-no-repeat px-6 py-10 dark:bg-[#060818] sm:px-16">
            <img src="/assets/images/auth/coming-soon-object1.png" alt="image" class="absolute left-0 top-1/2 h-full max-h-[893px] -translate-y-1/2" />
            <img src="/assets/images/auth/coming-soon-object2.png" alt="image" class="absolute left-24 top-0 h-40 md:left-[30%]" />
            <img src="/assets/images/auth/coming-soon-object3.png" alt="image" class="absolute right-0 top-0 h-[300px]" />
            <img src="/assets/images/auth/polygon-object.svg" alt="image" class="absolute bottom-0 end-[28%]" />

            <div class="relative w-full max-w-[870px] rounded-md bg-[linear-gradient(45deg,#fff9f9_0%,rgba(255,255,255,0)_25%,rgba(255,255,255,0)_75%,_#fff9f9_100%)] p-2 dark:bg-[linear-gradient(52.22deg,#0E1726_0%,rgba(14,23,38,0)_18.66%,rgba(14,23,38,0)_51.04%,rgba(14,23,38,0)_80.07%,#0E1726_100%)]">
                <div class="relative flex flex-col justify-center rounded-md bg-white/60 backdrop-blur-lg dark:bg-black/50 px-6 lg:min-h-[758px] py-20">

                    <div class="absolute top-6 start-6 flex items-center gap-3">
                        <img src="/icones/mvoe-512.png" alt="Mvoé" class="w-10 h-10 rounded-lg" />
                        <div class="leading-tight">
                            <div class="text-lg font-extrabold text-black dark:text-white">Mvoé</div>
                            <div class="text-[10px] font-semibold uppercase tracking-wider text-white-dark">Kit du facilitateur</div>
                        </div>
                    </div>

                    <div class="absolute top-6 end-6">
                        <span x-data="{ enLigne: navigator.onLine }"
                              x-on:online.window="enLigne = true"
                              x-on:offline.window="enLigne = false"
                              class="flex items-center gap-2 rounded-full bg-white/70 px-3 py-1.5 dark:bg-black/40"
                              role="status">
                            <span class="w-2 h-2 rounded-full shrink-0"
                                  x-bind:class="enLigne ? 'bg-success' : 'bg-warning'" aria-hidden="true"></span>
                            <span class="text-[10px] font-semibold uppercase tracking-wider text-white-dark"
                                  x-text="enLigne ? 'En ligne' : 'Hors ligne'">Réseau</span>
                        </span>
                    </div>

                    <div class="mx-auto w-full max-w-[440px]">
                        <div class="mb-10">
                            <h1 class="text-3xl font-extrabold uppercase !leading-snug text-primary md:text-4xl">Ouvrir mon kit</h1>
                            <p class="text-base font-bold leading-normal text-white-dark">Vos identifiants vous ont été remis par votre superviseur.</p>
                        </div>

                        @if (request()->query('session') === 'expiree')
                            <div class="mb-5 flex items-center rounded border border-warning bg-warning-light p-3.5 text-warning dark:bg-warning-dark-light">
                                <span class="ltr:pr-2 rtl:pl-2"><strong class="ltr:mr-1 rtl:ml-1">Session expirée.</strong>Reconnectez-vous pour envoyer ce qui attend : rien n'a été perdu, tout est resté sur cet appareil.</span>
                            </div>
                        @endif

                        <form class="space-y-5 dark:text-white" @submit.prevent="valider()">

                            <template x-if="! parEmail">
                                <div class="space-y-5">
                                    <div>
                                        <label for="telephone">Numéro de téléphone</label>
                                        <div class="relative text-white-dark">
                                            <input id="telephone" x-model="telephone" type="tel" inputmode="tel"
                                                   autocomplete="tel" required placeholder="699 00 00 00"
                                                   class="form-input chiffre ps-10 placeholder:text-white-dark" />
                                            <span class="absolute start-4 top-1/2 -translate-y-1/2">
                                                <svg width="18" height="18" viewBox="0 0 24 24" fill="none"
                                                     stroke="currentColor" stroke-width="1.8" stroke-linecap="round"
                                                     stroke-linejoin="round">
                                                    <path d="M22 16.9v3a2 2 0 0 1-2.2 2 19.8 19.8 0 0 1-8.6-3.1 19.5 19.5 0 0 1-6-6A19.8 19.8 0 0 1 2.1 4.2 2 2 0 0 1 4.1 2h3a2 2 0 0 1 2 1.7c.1 1 .4 1.9.7 2.8a2 2 0 0 1-.5 2.1L8.1 9.9a16 16 0 0 0 6 6l1.3-1.3a2 2 0 0 1 2.1-.4c.9.3 1.8.5 2.8.6a2 2 0 0 1 1.7 2z" />
                                                </svg>
                                            </span>
                                        </div>
                                    </div>

                                    <div>
                                        <label for="code">Code d'appareil</label>
                                        <div class="relative text-white-dark">
                                            <input id="code" x-model="codeAppareil" type="password" inputmode="numeric"
                                                   autocomplete="off" required placeholder="••••••"
                                                   class="form-input chiffre ps-10 tracking-[0.3em] placeholder:tracking-normal placeholder:text-white-dark" />
                                            <span class="absolute start-4 top-1/2 -translate-y-1/2">
                                                <svg width="18" height="18" viewBox="0 0 24 24" fill="none"
                                                     stroke="currentColor" stroke-width="1.8" stroke-linecap="round"
                                                     stroke-linejoin="round">
                                                    <rect x="3" y="11" width="18" height="11" rx="2" />
                                                    <path d="M7 11V7a5 5 0 0 1 10 0v4" />
                                                </svg>
                                            </span>
                                        </div>
                                    </div>
                                </div>
                            </template>

                            <template x-if="parEmail">
                                <div class="space-y-5">
                                    <div>
                                        <label for="email">Adresse e-mail</label>
                                        <div class="relative text-white-dark">
                                            <input id="email" x-model="email" type="email" autocomplete="email" required
                                                   placeholder="prenom.nom@minproff.cm"
                                                   class="form-input ps-10 placeholder:text-white-dark" />
                                            <span class="absolute start-4 top-1/2 -translate-y-1/2">
                                                <svg width="18" height="18" viewBox="0 0 18 18" fill="none">
                                                    <path opacity="0.5"
                                                        d="M10.65 2.25H7.35C4.23873 2.25 2.6831 2.25 1.71655 3.23851C0.75 4.22703 0.75 5.81802 0.75 9C0.75 12.182 0.75 13.773 1.71655 14.7615C2.6831 15.75 4.23873 15.75 7.35 15.75H10.65C13.7613 15.75 15.3169 15.75 16.2835 14.7615C17.25 13.773 17.25 12.182 17.25 9C17.25 5.81802 17.25 4.22703 16.2835 3.23851C15.3169 2.25 13.7613 2.25 10.65 2.25Z"
                                                        fill="currentColor" />
                                                    <path
                                                        d="M14.3465 6.02574C14.609 5.80698 14.6445 5.41681 14.4257 5.15429C14.207 4.89177 13.8168 4.8563 13.5543 5.07507L11.7732 6.55931C11.0035 7.20072 10.4691 7.6446 10.018 7.93476C9.58125 8.21564 9.28509 8.30993 9.00041 8.30993C8.71572 8.30993 8.41956 8.21564 7.98284 7.93476C7.53168 7.6446 6.9973 7.20072 6.22761 6.55931L4.44652 5.07507C4.184 4.8563 3.79384 4.89177 3.57507 5.15429C3.3563 5.41681 3.39177 5.80698 3.65429 6.02574L5.4664 7.53583C6.19764 8.14522 6.79033 8.63914 7.31343 8.97558C7.85834 9.32604 8.38902 9.54743 9.00041 9.54743C9.6118 9.54743 10.1425 9.32604 10.6874 8.97558C11.2105 8.63914 11.8032 8.14522 12.5344 7.53582L14.3465 6.02574Z"
                                                        fill="currentColor" />
                                                </svg>
                                            </span>
                                        </div>
                                    </div>

                                    <div>
                                        <label for="mdp">Mot de passe</label>
                                        <div class="relative text-white-dark">
                                            <input id="mdp" x-model="motDePasse" type="password"
                                                   autocomplete="current-password" required placeholder="••••••••"
                                                   class="form-input ps-10 placeholder:text-white-dark" />
                                            <span class="absolute start-4 top-1/2 -translate-y-1/2">
                                                <svg width="18" height="18" viewBox="0 0 18 18" fill="none">
                                                    <path opacity="0.5"
                                                        d="M1.5 12C1.5 9.87868 1.5 8.81802 2.15901 8.15901C2.81802 7.5 3.87868 7.5 6 7.5H12C14.1213 7.5 15.182 7.5 15.841 8.15901C16.5 8.81802 16.5 9.87868 16.5 12C16.5 14.1213 16.5 15.182 15.841 15.841C15.182 16.5 14.1213 16.5 12 16.5H6C3.87868 16.5 2.81802 16.5 2.15901 15.841C1.5 15.182 1.5 14.1213 1.5 12Z"
                                                        fill="currentColor" />
                                                    <path
                                                        d="M5.0625 6C5.0625 3.82538 6.82538 2.0625 9 2.0625C11.1746 2.0625 12.9375 3.82538 12.9375 6V7.50268C13.363 7.50665 13.7351 7.51651 14.0625 7.54096V6C14.0625 3.20406 11.7959 0.9375 9 0.9375C6.20406 0.9375 3.9375 3.20406 3.9375 6V7.54096C4.26488 7.51651 4.63698 7.50665 5.0625 7.50268V6Z"
                                                        fill="currentColor" />
                                                </svg>
                                            </span>
                                        </div>
                                    </div>
                                </div>
                            </template>

                            <div x-cloak x-show="erreur"
                                 class="flex items-center rounded border border-danger bg-danger-light p-3.5 text-danger dark:bg-danger-dark-light">
                                <span x-text="erreur"></span>
                            </div>

                            <button type="submit" :disabled="occupe"
                                    class="btn btn-gradient !mt-6 w-full border-0 uppercase shadow-[0_10px_20px_-10px_rgba(67,97,238,0.44)]">
                                <span x-text="occupe ? 'Un instant…' : 'Ouvrir mon kit'">Ouvrir mon kit</span>
                            </button>

                            <button type="button" x-on:click="parEmail = ! parEmail; erreur = null"
                                    class="btn btn-outline-primary w-full">
                                <span x-text="parEmail
                                    ? 'Utiliser mon numéro et mon code d\'appareil'
                                    : 'Utiliser mon e-mail et mon mot de passe'">…</span>
                            </button>
                        </form>

                      
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-layouts.auth>
