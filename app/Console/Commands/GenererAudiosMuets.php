<?php

namespace App\Console\Commands;

use App\Models\Episode;
use App\Models\Question;
use App\Models\Realisation;
use App\Models\SituationFrequente;
use Illuminate\Console\Command;

/**
 * Crée un fichier audio muet pour chaque chemin référencé en base et encore
 * absent du disque, en attendant les vrais enregistrements.
 *
 * La liste des fichiers est déduite de la base, jamais écrite en dur : les noms
 * ne peuvent donc pas diverger de ce que les seeders ont enregistré.
 *
 * Format : WAV PCM 8 kHz, 8 bits, mono. Pas de MP3 : sans encodeur disponible
 * ici, un MP3 fabriqué à la main risquerait de ne pas se lire, et une démo qui
 * plante sur un lecteur audio ne vaut rien. Quand les vrais enregistrements
 * arriveront, seule l'extension change dans les seeders.
 */
class GenererAudiosMuets extends Command
{
    protected $signature = 'mvoe:audios-muets
                            {--duree=6 : Durée en secondes des fichiers générés}
                            {--force : Réécrit aussi les fichiers déjà présents}';

    protected $description = 'Génère les fichiers audio muets manquants référencés en base';

    private const FREQUENCE = 8000;

    public function handle(): int
    {
        $duree = max(1, (int) $this->option('duree'));
        $chemins = $this->cheminsReferences();

        $crees = 0;
        $existants = 0;

        foreach ($chemins as $chemin) {
            $absolu = public_path($chemin);

            if (file_exists($absolu) && ! $this->option('force')) {
                $existants++;

                continue;
            }

            if (! is_dir(dirname($absolu))) {
                mkdir(dirname($absolu), 0755, true);
            }

            file_put_contents($absolu, $this->wavSilencieux($duree));
            $crees++;
        }

        $this->info(sprintf(
            '%d fichier(s) créé(s), %d déjà présent(s), sur %d chemin(s) référencé(s) en base.',
            $crees,
            $existants,
            $chemins->count(),
        ));

        return self::SUCCESS;
    }

    /**
     * @return \Illuminate\Support\Collection<int, string>
     */
    private function cheminsReferences()
    {
        return collect()
            ->merge(Realisation::whereNotNull('fichier_audio')->pluck('fichier_audio'))
            ->merge(Episode::whereNotNull('fichier_audio')->pluck('fichier_audio'))
            ->merge(Question::whereNotNull('enonce_audio')->pluck('enonce_audio'))
            ->merge(SituationFrequente::whereNotNull('fichier_audio')->pluck('fichier_audio'))
            ->unique()
            ->values();
    }

    /**
     * WAV PCM 8 bits non signé : le silence vaut 0x80, pas 0x00.
     */
    private function wavSilencieux(int $secondes): string
    {
        $octets = self::FREQUENCE * $secondes;

        $entete = 'RIFF'
            .pack('V', 36 + $octets)
            .'WAVEfmt '
            .pack('V', 16)          // taille du bloc fmt
            .pack('v', 1)           // PCM
            .pack('v', 1)           // mono
            .pack('V', self::FREQUENCE)
            .pack('V', self::FREQUENCE) // octets par seconde
            .pack('v', 1)           // alignement de bloc
            .pack('v', 8)           // bits par échantillon
            .'data'
            .pack('V', $octets);

        return $entete.str_repeat("\x80", $octets);
    }
}
