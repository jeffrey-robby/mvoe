<?php

namespace App\Enums;

enum TypeSequence: string
{
    // Sequence portant un contenu numerique consultable par le facilitateur.
    case UniteDigitale = 'unite_digitale';

    // Sequence que l'outil n'accompagne pas : il affiche la consigne et se retire.
    case ConsigneAnimation = 'consigne_animation';
}
