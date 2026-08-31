@props([
    'titre' => 'Mvoé',
    // Le compteur lit la file locale tout seul. `compteurDemo` ne sert qu'à
    // la page du système de design, pour montrer l'état chargé.
    'compteurDemo' => null,
])

@php
    $liens = [
        ['/kit', 'Mon kit', 'M4 6h16M4 12h16M4 18h10', null],
        ['/kit/seance', 'Séance', 'M8 5v14l11-7z', null],
        ['/kit/pointage', 'Pointage', 'M9 11l3 3 8-8M20 12v7a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2V6a2 2 0 0 1 2-2h9', null],
        ['/kit/fidelite', 'Fiche de fidélité', 'M8 4h9l3 3v13H8zM8 9h8M8 13h8M8 17h5', null],
        ['/kit/inscrire', 'Inscrire un parent', 'M12 5v14M5 12h14', null],
        ['/kit/activite', 'Activités', 'M12 2l3 7h7l-5.5 4.5L18 21l-6-4-6 4 1.5-7.5L2 9h7z', null],
        ['/kit/visite', 'Visites à domicile', 'M3 10l9-7 9 7v10a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z M9 22V12h6v10', null],
        ['/kit/signaler', 'Signaler', 'M12 4l9 16H3zM12 10v4M12 17h.01', null],
        ['/kit/formation', 'Ma formation', 'M22 10L12 5 2 10l10 5 10-5zM6 12v5c0 1.7 2.7 3 6 3s6-1.3 6-3v-5', null],
        ['/kit/tableau-de-bord', 'Mon activité', 'M4 19h16M7 16V9M12 16V5M17 16v-4', null],
    ];
@endphp

<x-layouts.coquille :titre="$titre"
                    :entrees="['resources/css/kit.css', 'resources/js/app.js']"
                    donnees="enteteKit"
                    :alpine-template="false"
                    :liens="$liens"
                    accueil="/kit"
                    section="Facilitateur"
                    note="Ce kit fonctionne sans réseau."
                    :reglages="null"
                    :manifeste="true">

    <x-slot:outils>
        {{-- L'état du réseau et la file en attente ne quittent jamais l'écran :
             ce sont les deux choses qu'un facilitateur doit pouvoir vérifier
             d'un coup d'œil en sortant de la salle. --}}
        <x-mvoe.etat-reseau/>
        <x-mvoe.compteur-sync :demo="$compteurDemo"/>
    </x-slot:outils>

    {{ $slot }}
</x-layouts.coquille>
