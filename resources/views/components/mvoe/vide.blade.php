@props(['action' => null])

{{-- Un écran vide dit quoi faire. Il ne s'excuse pas, il ne se justifie pas,
     et il n'affiche pas de dessin triste. --}}
<div class="rounded-carte border border-dashed border-ligne px-4 py-10 text-center">
    <p class="text-lg font-semibold [font-family:var(--font-titre)]">{{ $slot }}</p>

    @if ($action)
        <div class="mt-4 flex justify-center">{{ $action }}</div>
    @endif
</div>
