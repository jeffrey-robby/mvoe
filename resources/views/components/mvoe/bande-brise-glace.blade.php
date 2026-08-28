@props(['consigne'])

{{--
    Le brise-glace.

    Bande jaune pleine sur toute la largeur, AUCUN contrôle, AUCUN chronomètre,
    une seule ligne de texte. C'est le moment où l'outil se retire et où la
    salle prend le relais.

    Cette rupture visuelle porte la doctrine du projet : tout ne se numérise
    pas. Ne lui ajoutez ni bouton, ni minuteur, ni case à cocher.
--}}
<div class="bande-brise-glace -mx-4 px-6 py-10 text-center text-2xl">
    {{ $consigne }}
</div>
