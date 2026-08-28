<?php

namespace App\Console\Commands;

use App\Models\Cohorte;
use App\Models\Facilitateur;
use App\Models\Module;
use App\Models\ParentProgramme;
use App\Models\Seance;
use App\Models\SituationFrequente;
use App\Models\UniteDigitale;
use Illuminate\Console\Command;

/**
 * Contrôle de la base de démonstration, sans passer par l'interface.
 *
 * Sert surtout à vérifier le calcul de l'écart entre le DÉCLARÉ et l'OBSERVÉ,
 * qui est la fonctionnalité centrale du projet : c'est ici qu'on voit, en
 * clair, qu'une séquence déclarée réalisée n'a jamais été ouverte.
 */
class VerifierDonnees extends Command
{
    protected $signature = 'mvoe:verifier';

    protected $description = 'Vérifie les données de démonstration et affiche les écarts déclaré/observé';

    public function handle(): int
    {
        $this->inventaire();
        $this->registre();
        $this->ecarts();

        return self::SUCCESS;
    }

    private function inventaire(): void
    {
        $module8 = Module::where('numero', 8)->first();

        $this->newLine();
        $this->info('Inventaire');
        $this->table(['Objet', 'Nombre'], [
            ['Modules', Module::count().' (dont '.Module::get()->filter->estRenseigne()->count().' renseigné)'],
            ['Séquences du module 8', $module8?->sequences()->count()],
            ['Unités digitales', UniteDigitale::count()],
            ['Réalisations', UniteDigitale::withCount('realisations')->get()->sum('realisations_count')],
            ['Facilitateurs', Facilitateur::count()],
            ['Cohortes', Cohorte::count()],
            ['Parents', ParentProgramme::count()],
            ['Séances tenues', Seance::count()],
            ['Situations fréquentes', SituationFrequente::count()],
        ]);
    }

    private function registre(): void
    {
        $facilitateurs = Facilitateur::orderBy('arrondissement')->orderBy('nom')->get();

        $this->newLine();
        $this->info(sprintf(
            'Registre — %d actifs, %d inactifs (seuil : %d jours)',
            $facilitateurs->filter->estActif()->count(),
            $facilitateurs->reject->estActif()->count(),
            config('mvoe.facilitateur.jours_inactivite'),
        ));

        $this->table(
            ['Arrondissement', 'Facilitateur', 'Dernière activité', 'Statut'],
            $facilitateurs->map(fn (Facilitateur $f) => [
                $f->arrondissement,
                $f->nom,
                $f->derniere_activite?->format('d/m/Y') ?? 'jamais',
                $f->estActif() ? 'actif' : 'inactif',
            ])->all(),
        );
    }

    private function ecarts(): void
    {
        foreach (Seance::with('module', 'facilitateur')->orderBy('date')->get() as $seance) {
            $this->newLine();
            $this->info(sprintf(
                'Séance du %s — %s — %d écart(s), remontée en %d jour(s)',
                $seance->date->format('d/m/Y'),
                $seance->facilitateur->nom,
                $seance->nombreEcarts(),
                $seance->delaiRemonteeJours(),
            ));

            $this->table(
                ['Séquence', 'Déclaré', 'Observé', 'Écart'],
                $seance->ecarts()->map(fn (array $ligne) => [
                    $ligne['sequence']->ordre.'. '.$ligne['sequence']->titre,
                    match ($ligne['declaree']) {
                        true => 'réalisée',
                        false => 'non réalisée',
                        default => 'non renseignée',
                    },
                    $ligne['observee'] ? 'ouverte' : 'aucune trace',
                    match ($ligne['ecart']) {
                        'declaree_non_observee' => 'DÉCLARÉE, JAMAIS OUVERTE',
                        'observee_non_declaree' => 'ouverte, déclarée non faite',
                        default => '',
                    },
                ])->all(),
            );
        }
    }
}
