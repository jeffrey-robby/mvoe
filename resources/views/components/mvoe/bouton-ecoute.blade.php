@props(['libelle' => 'Écouter'])

{{--
    Chaque écran de l'espace parent dispose d'un bouton d'écoute : aucun
    parcours ne dépend de la capacité à lire. Cible large, et le libellé est
    toujours écrit à côté du symbole — un pictogramme seul ne se comprend pas
    de la même façon par tout le monde.
--}}
<button type="button"
        {{ $attributes->class('inline-flex min-h-tactile items-center gap-3 rounded-net bg-jaune px-4 text-noir hover:bg-noir hover:text-blanc') }}>
    <svg class="size-6 shrink-0" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true">
        <path d="M4 9v6h4l5 4V5L8 9H4z"/>
        <path d="M16 8.5a5 5 0 0 1 0 7" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="square"/>
    </svg>
    <span class="text-base font-semibold [font-family:var(--font-titre)]">{{ $libelle }}</span>
</button>
