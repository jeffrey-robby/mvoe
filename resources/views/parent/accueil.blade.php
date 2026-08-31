@php
    $cartes = [
        ['cle' => 'ecouter', 'titre' => 'Écouter', 'lien' => '/parent/ecouter',
         'texte' => 'Les unités du programme, en audio ou en texte et images.',
         'ton' => 'bg-primary-light text-primary',
         'trace' => 'M4 9v6h4l5 4V5L8 9H4zM16 8.5a5 5 0 0 1 0 7'],
        ['cle' => 'feuilleton', 'titre' => 'Le feuilleton', 'lien' => '/parent/feuilleton',
         'texte' => 'Quatre épisodes. Des familles comme les vôtres.',
         'ton' => 'bg-secondary-light text-secondary',
         'trace' => 'M8 5v14l11-7z'],
        ['cle' => 'question', 'titre' => 'Poser une question', 'lien' => '/parent/question',
         'texte' => 'Une réponse tirée du programme, ou un facilitateur à appeler.',
         'ton' => 'bg-info-light text-info',
         'trace' => 'M12 18h.01M9.1 9a3 3 0 1 1 4.2 3.2c-.8.4-1.3 1.1-1.3 2'],
    ];

    $secondaires = [
        ['cle' => 'questions', 'titre' => 'Les questions de la semaine', 'lien' => '/parent/questions',
         'trace' => 'M9 11l3 3 8-8M20 12v7a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2V6a2 2 0 0 1 2-2h9'],
        ['cle' => 'facilitateur', 'titre' => 'Trouver un facilitateur', 'lien' => '/parent/facilitateur',
         'trace' => 'M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2M12 3a4 4 0 1 0 0 8 4 4 0 0 0 0-8z'],
    ];
@endphp

{{-- Ni actualités, ni compteur, ni progression, ni « vous avez écouté trois
     unités cette semaine ». Le programme ne fait pas la leçon aux parents. --}}
<x-layouts.parent titre="Accueil" composant="accueilParent">

    <div class="mb-6">
        <h2 class="text-2xl font-bold dark:text-white-light">Mvoé</h2>
        <p class="text-white-dark mt-1">
            Les contenus du programme national de parentalité positive.
        </p>
    </div>

    <div class="panel border-l-4 border-primary mb-6" x-show="! connecte">
        <p>
            Vous consultez sans compte, et c'est très bien ainsi. Un code remis par
            votre facilitateur sert à répondre aux questions de la semaine et à recevoir
            les contenus dans la langue inscrite à votre dossier.
        </p>
        <a href="/parent" class="btn btn-outline-primary mt-4 inline-flex">J'ai un code</a>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-6">
        @foreach ($cartes as $c)
            <div class="panel h-full flex flex-col">
                <div class="w-11 h-11 rounded-lg flex items-center justify-center {{ $c['ton'] }}">
                    <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                         stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round">
                        <path d="{{ $c['trace'] }}" />
                    </svg>
                </div>

                <h5 class="font-semibold text-lg dark:text-white-light mt-4">{{ $c['titre'] }}</h5>
                <p class="text-white-dark mt-1 flex-1">{{ $c['texte'] }}</p>

                <div class="flex gap-2 mt-5">
                    <a href="{{ $c['lien'] }}" class="btn btn-primary flex-1">Ouvrir</a>

                    {{-- Chaque carte s'écoute : aucun parcours ne dépend de la
                         capacité à lire. --}}
                    <button type="button" class="btn btn-outline-primary w-14 shrink-0"
                            x-on:click="ecouter(audioCarte('{{ $c['cle'] }}'))"
                            aria-label="Écouter : {{ $c['titre'] }}">
                        <svg class="w-5 h-5" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true">
                            <path d="M4 9v6h4l5 4V5L8 9H4z" />
                        </svg>
                    </button>
                </div>
            </div>
        @endforeach
    </div>

    <div class="panel">
        <h5 class="font-semibold text-lg dark:text-white-light mb-5">Aussi disponible</h5>

        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
            @foreach ($secondaires as $s)
                <div class="flex items-center gap-3 rounded-md border border-white-light dark:border-[#1b2e4b] p-4">
                    <span class="w-11 h-11 shrink-0 rounded-lg flex items-center justify-center bg-white-light/60 text-white-dark">
                        <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                             stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round">
                            <path d="{{ $s['trace'] }}" />
                        </svg>
                    </span>

                    <a href="{{ $s['lien'] }}" class="font-semibold flex-1 dark:text-white-light hover:text-primary">
                        {{ $s['titre'] }}
                    </a>

                    <button type="button" class="btn btn-outline-primary btn-sm shrink-0"
                            x-on:click="ecouter(audioCarte('{{ $s['cle'] }}'))"
                            aria-label="Écouter : {{ $s['titre'] }}">
                        <svg class="w-4 h-4" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true">
                            <path d="M4 9v6h4l5 4V5L8 9H4z" />
                        </svg>
                    </button>
                </div>
            @endforeach
        </div>
    </div>
</x-layouts.parent>
