{{--
    État du réseau, lu par le navigateur lui-même.

    En mode avion, l'application ne doit produire AUCUNE erreur réseau visible :
    elle annonce simplement qu'elle travaille hors ligne, ce qui est son mode
    normal et non une panne.
--}}
<span x-data="{ enLigne: navigator.onLine }"
      x-on:online.window="enLigne = true"
      x-on:offline.window="enLigne = false"
      class="hidden sm:flex h-9 items-center gap-2 rounded-full bg-white-light/40 dark:bg-dark/40 px-3"
      role="status">

    <span class="size-2.5 shrink-0 rounded-full"
          x-bind:class="enLigne ? 'bg-success' : 'bg-warning'"
          aria-hidden="true"></span>

    {{-- Jamais la couleur seule : le mot est toujours là.

         Le texte de repli dit « Réseau » et non « Hors ligne » : le magasin
         local s'ouvre avant qu'Alpine ne monte quoi que ce soit, et pendant ce
         court instant le repli est ce qui s'affiche. Annoncer « hors ligne »
         à chaque ouverture d'écran serait un mensonge répété. --}}
    <span class="intitule text-xs text-white-dark" x-text="enLigne ? 'En ligne' : 'Hors ligne'">Réseau</span>
</span>
