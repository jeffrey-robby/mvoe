<?php

namespace App\Enums;

/**
 * Les canaux par lesquels un contenu atteint un parent sans passer par une
 * seance.
 *
 * Une abstraction, des pilotes interchangeables. Le passage a une
 * infrastructure nationale ne change qu'un pilote : c'est l'argument de
 * replicabilite, et il doit se lire dans le code.
 */
enum Canal: string
{
    case Sms = 'sms';
    case Ussd = 'ussd';
    case Ivr = 'ivr';
    case Radio = 'radio';

    public function libelle(): string
    {
        return match ($this) {
            self::Sms => 'SMS',
            self::Ussd => 'USSD',
            self::Ivr => 'Serveur vocal',
            self::Radio => 'Radio communautaire',
        };
    }

    /** Ce que compte `volume` : le mot change avec le canal. */
    public function unite(): string
    {
        return match ($this) {
            self::Sms => 'messages envoyés',
            self::Ussd => 'sessions ouvertes',
            self::Ivr => 'appels reçus',
            self::Radio => 'diffusions',
        };
    }

    /** Ce que compte `aboutis` : la partie qui a vraiment porté. */
    public function aboutissement(): string
    {
        return match ($this) {
            self::Sms => 'messages remis',
            self::Ussd => 'sessions menées au bout',
            self::Ivr => 'appels écoutés jusqu\'au bout',
            self::Radio => 'diffusions attestées',
        };
    }

    /**
     * La radio ne se mesure pas comme les autres.
     *
     * Aucune audience n'est fabriquee : on mesure le surcroit d'appels et de
     * sessions dans les 48 heures qui suivent une diffusion attestee.
     */
    public function seMesureParLeSurcroit(): bool
    {
        return $this === self::Radio;
    }
}
