@props(['titre' => null, 'meta' => null, 'rappel' => false])

{{-- Surface de base. `rappel` pose le jaune sourd : une zone calme, une
     information à retenir, jamais une alerte. --}}
<section {{ $attributes->class([
    'rounded-carte border border-ligne p-4',
    'bg-jaune-sourd' => $rappel,
    'bg-blanc' => ! $rappel,
]) }}>
    @if ($titre)
        <div class="mb-3 flex items-baseline justify-between gap-3">
            <h2 class="text-xl">{{ $titre }}</h2>
            @if ($meta)
                <span class="chiffre shrink-0 text-sm text-gris-texte">{{ $meta }}</span>
            @endif
        </div>
    @endif

    {{ $slot }}
</section>
