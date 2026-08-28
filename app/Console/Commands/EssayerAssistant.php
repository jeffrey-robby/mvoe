<?php

namespace App\Console\Commands;

use App\Enums\Langue;
use App\Models\SituationFrequente;
use App\Services\AppariementCorpus;
use Illuminate\Console\Command;

/**
 * Banc d'essai de l'assistant à corpus fermé, pour régler le seuil de refus.
 *
 * Sans argument, rejoue les douze situations fréquentes : les huit premières
 * sont couvertes par le module 8, les quatre dernières ne le sont pas et
 * DOIVENT être refusées. C'est ce tableau qui sert à choisir le seuil.
 */
class EssayerAssistant extends Command
{
    protected $signature = 'mvoe:assistant {texte? : Question à poser ; sinon rejoue les 12 situations}';

    protected $description = "Essaie l'assistant à corpus fermé et affiche les scores";

    public function handle(AppariementCorpus $assistant): int
    {
        $this->line(sprintf('Seuil de refus : %.2f  (config/mvoe.php)', $assistant->seuil()));
        $this->newLine();

        if ($texte = $this->argument('texte')) {
            return $this->uneQuestion($assistant, $texte);
        }

        return $this->lesDouzeSituations($assistant);
    }

    private function uneQuestion(AppariementCorpus $assistant, string $texte): int
    {
        $resultat = $assistant->chercher($texte);

        $this->info($texte);
        $this->newLine();

        if ($resultat->trouve()) {
            $this->line(sprintf('  TROUVÉ (score %.3f)', $resultat->score));
            $this->line('  '.$resultat->unite->message_cle);
            $this->line('  — '.$resultat->unite->reference());
        } else {
            $this->line(sprintf('  REFUS (meilleur score %.3f, sous le seuil)', $resultat->score));
            $this->line("  L'application dit qu'elle ne sait pas et propose un facilitateur.");
        }

        $this->newLine();
        $this->table(['Unité', 'Score'], collect($resultat->details)->map(fn (array $d) => [
            mb_strimwidth($d['message_cle'], 0, 70, '…'),
            number_format($d['score'], 3),
        ])->all());

        return self::SUCCESS;
    }

    private function lesDouzeSituations(AppariementCorpus $assistant): int
    {
        $situations = SituationFrequente::where('langue', Langue::Fr)->ordonnees()->get();

        $lignes = [];
        $refusAttendusTenus = true;

        foreach ($situations as $situation) {
            $resultat = $assistant->chercher($situation->libelle);

            // Par construction du jeu de démonstration, les situations 9 à 12
            // relèvent de modules encore vides ou sortent du programme.
            $devraitRefuser = $situation->ordre >= 9;

            if ($devraitRefuser && $resultat->trouve()) {
                $refusAttendusTenus = false;
            }

            $lignes[] = [
                $situation->ordre,
                mb_strimwidth($situation->libelle, 0, 48, '…'),
                number_format($resultat->score, 3),
                $resultat->trouve() ? 'répond' : 'REFUSE',
                $devraitRefuser ? 'refus attendu' : '',
            ];
        }

        $this->table(['#', 'Situation', 'Score', 'Verdict', 'Attendu'], $lignes);

        if ($refusAttendusTenus) {
            $this->info('Les quatre situations hors corpus sont bien refusées.');

            return self::SUCCESS;
        }

        $this->error('Au moins une situation hors corpus reçoit une réponse : le seuil est trop bas.');

        return self::FAILURE;
    }
}
