@props(['secondes' => 0, 'taille' => 'grand'])

{{--
    Chronomètre. IBM Plex Mono et chiffres tabulaires : la largeur ne bouge pas
    quand les secondes défilent. Un chrono qui gigote est illisible d'un coup
    d'œil, et c'est précisément ce qu'on lui demande.
--}}
<time {{ $attributes->class([
        'block tabular-nums leading-none font-medium',
        'text-5xl' => $taille === 'grand',
        'text-2xl' => $taille === 'moyen',
        'text-base' => $taille === 'petit',
    ]) }}
    datetime="PT{{ (int) $secondes }}S">{{ sprintf('%02d:%02d', intdiv((int) $secondes, 60), (int) $secondes % 60) }}</time>
