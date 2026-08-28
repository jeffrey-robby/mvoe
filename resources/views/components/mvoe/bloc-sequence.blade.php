@props(['ordre', 'titre', 'dureeMinutes', 'etat' => 'a_venir'])

{{--
    Un bloc de la colonne de séance, rendu côté serveur.

    Les états visuels vivent dans `app.css` (.bloc-sequence[data-etat]) : ce
    composant et le template Alpine de l'écran de séance posent le même
    attribut, et ne peuvent donc pas diverger.
--}}
<div style="--minutes: {{ (int) $dureeMinutes }}"
     data-etat="{{ $etat }}"
     {{ $attributes->class('bloc-sequence') }}
     @if ($etat === 'en_cours') aria-current="step" @endif>

    <div class="flex items-start justify-between gap-3">
        <p class="text-lg font-semibold [font-family:var(--font-titre)]">
            <span class="chiffre">{{ $ordre }}.</span> {{ $titre }}
        </p>
        <span class="chiffre shrink-0 text-sm">{{ $dureeMinutes }} min</span>
    </div>

    {{-- L'état est écrit, jamais porté par la seule couleur. --}}
    <p @class([
        'intitule text-xs',
        'text-gris-texte' => $etat === 'a_venir',
    ])>
        {{ ['passee' => 'Terminée', 'en_cours' => 'En cours', 'a_venir' => 'À venir'][$etat] }}
    </p>
</div>
