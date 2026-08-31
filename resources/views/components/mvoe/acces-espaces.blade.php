@props(['actuel' => null])

@php
    // Les trois portes d'entrée du prototype. L'adresse est affichée en toutes
    // lettres : le jury la recopie dans un autre navigateur, sur son téléphone,
    // sans avoir à la deviner.
    $espaces = [
        [
            'cle' => 'kit',
            'nom' => 'Facilitateur',
            'sous' => 'Kit de séance hors ligne',
            'url' => 'mvoe.hcmbooking.com/kit',
            'href' => route('kit.accueil'),
        ],
        [
            'cle' => 'superviseur',
            'nom' => 'Superviseur',
            'sous' => 'Registre, signalements, rapport',
            'url' => 'mvoe.hcmbooking.com/superviseur',
            'href' => route('superviseur.registre'),
        ],
        [
            'cle' => 'parent',
            'nom' => 'Parent',
            'sous' => 'Contenus dans sa langue',
            'url' => 'mvoe.hcmbooking.com/parent',
            'href' => route('parent.entree'),
        ],
    ];
@endphp

<div {{ $attributes->class('mt-10') }}>
    <div class="mb-4 flex items-center gap-3">
        <span class="h-px flex-1 bg-white-light dark:bg-[#253b5c]"></span>
        <span class="text-[10px] font-semibold uppercase tracking-wider text-white-dark">Les trois espaces</span>
        <span class="h-px flex-1 bg-white-light dark:bg-[#253b5c]"></span>
    </div>

    <ul class="space-y-2">
        @foreach ($espaces as $espace)
            @php $ici = $espace['cle'] === $actuel; @endphp
            <li>
                <a href="{{ $espace['href'] }}"
                   @class([
                       'group flex items-center gap-3 rounded-md border px-4 py-3 transition',
                       'border-primary bg-primary/10' => $ici,
                       'border-white-light bg-white/70 hover:border-primary hover:bg-primary/10 dark:border-[#253b5c] dark:bg-black/30' => ! $ici,
                   ])
                   @if ($ici) aria-current="page" @endif>

                    <span @class([
                        'flex h-9 w-9 shrink-0 items-center justify-center rounded-md text-xs font-extrabold uppercase',
                        'bg-primary text-white' => $ici,
                        'bg-primary/10 text-primary group-hover:bg-primary group-hover:text-white' => ! $ici,
                    ])>{{ mb_substr($espace['nom'], 0, 1) }}</span>

                    <span class="min-w-0 flex-1 leading-tight">
                        <span class="block text-sm font-bold text-black dark:text-white">
                            {{ $espace['nom'] }}
                            @if ($ici)
                                <span class="ms-1 text-[10px] font-semibold uppercase tracking-wider text-primary">vous êtes ici</span>
                            @endif
                        </span>
                        <span class="block truncate chiffre text-xs text-white-dark">{{ $espace['url'] }}</span>
                    </span>

                    <svg class="h-4 w-4 shrink-0 text-white-dark transition group-hover:text-primary rtl:rotate-180"
                         viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                         stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                        <path d="M5 12h14M13 6l6 6-6 6" />
                    </svg>
                </a>
            </li>
        @endforeach
    </ul>

    <p class="mt-3 text-center text-xs text-white-dark">
        Chaque espace a ses propres identifiants.
    </p>
</div>
