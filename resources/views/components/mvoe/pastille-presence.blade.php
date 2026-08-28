@props([
    'code',
    // Libellé local saisi par le facilitateur (« Odile, marché »).
    // Il vit dans le magasin local, sur son seul appareil : jamais synchronisé,
    // jamais envoyé, et inexistant côté serveur.
    'libelleLocal' => null,
    'statut' => 'a_pointer',
])

{{--
    Une pastille de pointage, rendue côté serveur.

    Les états visuels vivent dans `app.css` (.pastille[data-etat]) : ce
    composant et le template Alpine de l'écran de pointage posent le même
    attribut, et ne peuvent donc pas diverger.

    Un appui fait défiler présent → absent → binôme. On ne revient jamais à
    « à pointer » : on ne peut pas dé-pointer quelqu'un.
--}}
<button type="button"
        x-data="{
            statuts: ['present', 'absent', 'rattrape_binome'],
            statut: @js($statut),
            libelles: {
                a_pointer: 'À pointer',
                present: 'Présent',
                absent: 'Absent',
                rattrape_binome: 'Binôme',
            },
            suivant() {
                const rang = this.statuts.indexOf(this.statut);
                this.statut = this.statuts[(rang + 1) % this.statuts.length];
                $dispatch('pointage', { code: @js($code), statut: this.statut });
            },
        }"
        x-on:click="suivant()"
        x-bind:aria-label="@js($libelleLocal ?? $code) + ' : ' + libelles[statut] + '. Appuyer pour changer.'"
        {{ $attributes->class('flex flex-col items-center gap-1.5 rounded-net p-2 text-center hover:bg-jaune-sourd') }}>

    <span class="relative flex size-tactile items-center justify-center">
        <span class="pastille" x-bind:data-etat="statut" aria-hidden="true"></span>

        {{-- Marque du rattrapage : deux maillons, pour dire « reçu par son
             binôme » sans ajouter de couleur. --}}
        <svg class="absolute size-5" viewBox="0 0 24 24" fill="none" stroke="#121212"
             stroke-width="2.5" stroke-linecap="square"
             x-cloak x-show="statut === 'rattrape_binome'" aria-hidden="true">
            <path d="M9 12h6M8 8H6a4 4 0 0 0 0 8h2M16 8h2a4 4 0 0 1 0 8h-2"/>
        </svg>
    </span>

    <span class="chiffre text-xs leading-none text-gris-texte">{{ $code }}</span>

    @if ($libelleLocal)
        <span class="max-w-20 truncate text-sm leading-tight">{{ $libelleLocal }}</span>
    @endif

    <span class="intitule text-[0.6875rem]"
          x-bind:class="statut === 'a_pointer' ? 'text-gris-texte' : ''"
          x-text="libelles[statut]">À pointer</span>
</button>
