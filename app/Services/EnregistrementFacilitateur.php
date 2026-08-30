<?php

namespace App\Services;

use App\Models\Facilitateur;
use App\Models\User;
use Illuminate\Support\Str;

/**
 * L'enregistrement d'un facilitateur par son superviseur.
 *
 * Un facilitateur ne s'inscrit JAMAIS lui-même. Son superviseur d'arrondissement
 * l'enregistre et lui remet ses identifiants en main propre — c'est ainsi que le
 * programme fonctionne sur le terrain, et c'est aussi ce qui évite tout écran
 * d'inscription publique.
 *
 * Deux conséquences dans le code :
 *
 *  1. L'arrondissement n'est JAMAIS choisi dans la requête : c'est celui du
 *     superviseur. Un superviseur d'Ebolowa II ne peut pas créer un facilitateur
 *     à Kribi I, même en forgeant sa requête.
 *
 *  2. Les identifiants sont GÉNÉRÉS ici, jamais saisis. Un mot de passe choisi
 *     à la va-vite par un superviseur pressé est un mauvais mot de passe, et
 *     celui-ci ne sera de toute façon ni tapé ni mémorisé par son porteur : il
 *     sera recopié depuis une feuille.
 */
class EnregistrementFacilitateur
{
    /**
     * Alphabet sans caractères ambigus.
     *
     * Ni O ni 0, ni I ni l ni 1 : ces identifiants sont recopiés à la main
     * depuis une feuille de papier, souvent mal éclairée. Une confusion coûte
     * un appel au superviseur.
     */
    private const ALPHABET = 'ABCDEFGHJKMNPQRSTUVWXYZ23456789';

    /**
     * @return array{facilitateur: Facilitateur, identifiants: array}
     */
    public function enregistrer(User $superviseur, array $donnees): array
    {
        abort_unless(
            $superviseur->niveau === 'arrondissement',
            403,
            'Seul un superviseur d\'arrondissement enregistre des facilitateurs.',
        );

        $codeAppareil = $this->codeAppareil();
        $motDePasse = $this->motDePasse();

        $facilitateur = Facilitateur::create([
            'nom' => $donnees['nom'],
            'telephone' => $donnees['telephone'],
            'email' => $donnees['email'] ?? $this->email($donnees['nom']),

            // Les deux voies d'entrée, générées et remises en main propre.
            'code_appareil' => $codeAppareil,
            'password' => $motDePasse,

            // JAMAIS depuis la requête : l'arrondissement est celui du compte
            // qui enregistre. C'est le point de sécurité de tout cet écran.
            'arrondissement_id' => $superviseur->arrondissement_id,
            'superviseur_id' => $superviseur->id,

            'type_juridique' => $donnees['type_juridique'],
            'organisation_rattachement' => $donnees['organisation_rattachement'] ?? null,
            'date_formation_initiale' => $donnees['date_formation_initiale'],

            // Null, et c'est exact : il n'a encore rien fait. C'est précisément
            // ce que le registre doit rendre visible.
            'derniere_activite' => null,
        ]);

        return [
            'facilitateur' => $facilitateur,
            'identifiants' => $this->identifiants($facilitateur, $codeAppareil, $motDePasse),
        ];
    }

    /**
     * Régénère les identifiants d'un facilitateur existant.
     *
     * Nécessaire, et pas un confort : les identifiants sont hachés et affichés
     * une seule fois. Sans cette porte de sortie, une feuille perdue rendrait
     * un facilitateur inutilisable — et personne ne renoncerait à un
     * facilitateur formé pour si peu, on contournerait le système.
     *
     * Les anciens jetons sont révoqués : un appareil qui n'a plus les bons
     * identifiants ne doit pas continuer à remonter des séances.
     */
    public function regenerer(User $superviseur, Facilitateur $facilitateur): array
    {
        abort_unless(
            $facilitateur->superviseur_id === $superviseur->id
                || $superviseur->portee()->couvre($facilitateur->arrondissement_id),
            403,
        );

        $codeAppareil = $this->codeAppareil();
        $motDePasse = $this->motDePasse();

        $facilitateur->update([
            'code_appareil' => $codeAppareil,
            'password' => $motDePasse,
        ]);

        $facilitateur->tokens()->delete();

        return $this->identifiants($facilitateur, $codeAppareil, $motDePasse);
    }

    /**
     * Ce que le superviseur recopie et remet. Ces valeurs en clair n'existent
     * qu'ici, le temps d'une réponse : en base tout est haché, et rien ne
     * permet de les retrouver ensuite.
     */
    private function identifiants(Facilitateur $facilitateur, string $codeAppareil, string $motDePasse): array
    {
        return [
            'telephone' => $facilitateur->telephone,
            'code_appareil' => $codeAppareil,
            'email' => $facilitateur->email,
            'mot_de_passe' => $motDePasse,
        ];
    }

    /** Six chiffres, saisissables d'une main en plein soleil. */
    private function codeAppareil(): string
    {
        return str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);
    }

    /** Huit caractères non ambigus, en deux groupes de quatre. */
    private function motDePasse(): string
    {
        $tirer = fn (int $n) => collect(range(1, $n))
            ->map(fn () => self::ALPHABET[random_int(0, strlen(self::ALPHABET) - 1)])
            ->implode('');

        return $tirer(4).'-'.$tirer(4);
    }

    /** Dérivé du nom, suffixé si un homonyme existe déjà. */
    private function email(string $nom): string
    {
        $base = Str::slug($nom, '.');
        $email = "{$base}@minproff.cm";
        $suffixe = 2;

        while (Facilitateur::where('email', $email)->exists()) {
            $email = "{$base}{$suffixe}@minproff.cm";
            $suffixe++;
        }

        return $email;
    }
}
