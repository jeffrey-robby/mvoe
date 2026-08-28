{{--
    « Trouver un facilitateur ».

    Aucun compte n'est nécessaire. C'est le seul écran du système où un inconnu
    obtient quelque chose, et c'est voulu : quelqu'un qui a besoin d'un contact
    humain ne doit pas d'abord se connecter. C'est aussi la sortie proposée à un
    parent mineur — un refus sans issue serait une porte fermée.

    L'arrondissement choisi n'est jamais enregistré.
--}}
<x-layouts.parent titre="Trouver un facilitateur" composant="annuaireParent" :barre="false">

    <div class="space-y-5">

        <div class="flex items-center gap-3">
            <a x-bind:href="connecte ? '/parent/accueil' : '/parent'"
               class="flex size-tactile shrink-0 items-center justify-center rounded-net border-2 border-noir"
               aria-label="Revenir">
                <svg class="size-6" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                     stroke-width="2.5" stroke-linecap="square" aria-hidden="true">
                    <path d="M15 5 8 12l7 7"/>
                </svg>
            </a>

            <h1 class="text-3xl">Trouver un facilitateur</h1>
        </div>

        <p class="text-base text-gris-texte">
            Choisissez votre arrondissement. Rien de ce que vous choisissez ici n'est enregistré.
        </p>

        <div>
            <label for="arr" class="intitule block">Arrondissement</label>
            <select id="arr" x-model="arrondissement" x-on:change="chercher()"
                    class="mt-1 min-h-tactile w-full rounded-net border-[3px] border-noir bg-blanc px-3 py-3 text-xl">
                <option value="">Choisir…</option>
                <template x-for="a in arrondissements" x-bind:key="a">
                    <option x-bind:value="a" x-text="a"></option>
                </template>
            </select>
        </div>

        <p x-show="erreur" x-text="erreur" class="rounded-net border-2 border-noir px-3 py-2"></p>

        <template x-if="resultat">
            <div class="space-y-3">

                {{-- Certains arrondissements n'ont aucun facilitateur actif. On
                     élargit alors au département en le disant, plutôt que de
                     renvoyer quelqu'un à rien. --}}
                <p x-show="resultat.repli_departement" x-text="resultat.message"
                   class="rounded-carte bg-jaune-sourd px-4 py-3 text-base"></p>

                <ul class="space-y-2">
                    <template x-for="c in resultat.contacts" x-bind:key="c.telephone">
                        <li class="rounded-carte border-[3px] border-noir p-4">
                            <p class="text-xl font-semibold [font-family:var(--font-titre)]"
                               x-text="c.nom"></p>
                            <p class="text-base text-gris-texte" x-text="c.arrondissement"></p>

                            {{-- Un appel, pas un message : le système n'écrit
                                 jamais à personne. C'est le parent qui décide
                                 d'appeler, et rien n'est envoyé en son nom. --}}
                            <a x-bind:href="lienTelephone(c.telephone)"
                               class="mt-3 flex min-h-tactile items-center justify-center gap-3 rounded-net bg-jaune px-4 text-noir">
                                <svg class="size-6 shrink-0" viewBox="0 0 24 24" fill="currentColor"
                                     aria-hidden="true">
                                    <path d="M6 3h3l2 5-2 1a12 12 0 0 0 5 5l1-2 5 2v3a2 2 0 0 1-2 2A16 16 0 0 1 4 5a2 2 0 0 1 2-2z"/>
                                </svg>
                                <span class="chiffre text-xl" x-text="c.telephone"></span>
                            </a>
                        </li>
                    </template>
                </ul>
            </div>
        </template>
    </div>
</x-layouts.parent>
