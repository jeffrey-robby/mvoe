@props(['titre' => 'Délégation'])

{{-- L'administration. Coquille commune, entrées Vite du template, Alpine du
     template : c'est le seul espace qui suppose une connexion permanente. --}}
<x-layouts.coquille :titre="$titre">
    {{ $slot }}
</x-layouts.coquille>
