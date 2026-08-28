@props([
    // 'principal' : surface jaune, texte noir. Une seule action principale
    //               par écran, sinon plus rien n'est principal.
    // 'second'    : contour noir sur blanc.
    // 'discret'   : texte souligné, pour les sorties et les liens de service.
    'variante' => 'principal',
    'href' => null,
])

@php
    $classes = [
        // Hauteur minimale 48 px : cible tactile pour un doigt, pas un curseur.
        'inline-flex min-h-tactile items-center justify-center gap-2 rounded-net',
        'px-5 py-2.5 text-base font-semibold [font-family:var(--font-titre)]',
        'transition-[background-color,color] select-none',
        'disabled:opacity-40 disabled:pointer-events-none',
    ];

    $classes[] = match ($variante) {
        // Le jaune est une SURFACE, jamais une couleur de texte : jaune sur
        // blanc échoue tous les seuils de contraste. Le texte qui s'y pose est
        // noir, toujours.
        'principal' => 'bg-jaune text-noir hover:bg-noir hover:text-blanc',
        'second' => 'border-2 border-noir bg-blanc text-noir hover:bg-jaune-sourd',
        'discret' => 'min-h-tactile px-2 text-noir underline underline-offset-4 hover:bg-jaune-sourd',
    };
@endphp

@if ($href)
    <a href="{{ $href }}" {{ $attributes->class($classes) }}>{{ $slot }}</a>
@else
    <button type="{{ $attributes->get('type', 'button') }}" {{ $attributes->class($classes) }}>{{ $slot }}</button>
@endif
