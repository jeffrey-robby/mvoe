{{--
    État du réseau, lu par le navigateur lui-même.

    En mode avion, l'application ne doit produire AUCUNE erreur réseau visible :
    elle annonce simplement qu'elle travaille hors ligne, ce qui est son mode
    normal et non une panne.
--}}
<span x-data="{ enLigne: navigator.onLine }"
      x-on:online.window="enLigne = true"
      x-on:offline.window="enLigne = false"
      class="inline-flex h-9 items-center gap-2 rounded-net border border-blanc/30 px-2.5"
      role="status">

    <span class="size-2.5 shrink-0 rounded-full"
          x-bind:class="enLigne ? 'bg-blanc' : 'border border-blanc'"
          aria-hidden="true"></span>

    {{-- Jamais la couleur seule : le mot est toujours là. --}}
    <span class="intitule text-xs" x-text="enLigne ? 'En ligne' : 'Hors ligne'">Hors ligne</span>
</span>
