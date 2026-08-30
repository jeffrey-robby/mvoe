@props(['demo' => null])

{{--
    Le compteur de synchronisation. Permanent, jamais masqué, y compris à zéro.

    À zéro il reste lisible mais discret ; dès qu'une séance attend, il devient
    une surface jaune pleine : c'est la seule chose de l'écran qui doit attirer
    l'œil quand le facilitateur sort de la salle.

    Il lit la file locale, jamais le serveur : hors ligne, il reste juste.
--}}
<span x-data="compteurSync({{ $demo === null ? 'null' : (int) $demo }})"
      x-bind:class="total > 0
        ? 'bg-jaune text-noir'
        : 'border border-blanc/30 text-blanc'"
      class="inline-flex h-9 items-center gap-2 rounded-net px-2.5"
      role="status"
      x-bind:aria-label="libelle">

    <svg class="size-4 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor"
         stroke-width="2.5" stroke-linecap="square" aria-hidden="true">
        <path d="M4 12a8 8 0 0 1 13.7-5.7M20 12a8 8 0 0 1-13.7 5.7"/>
        <path d="M18 3v4h-4M6 21v-4h4"/>
    </svg>

    <span class="chiffre text-sm font-semibold leading-none" x-text="total">0</span>
</span>
