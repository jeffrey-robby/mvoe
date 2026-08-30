<?php

namespace App\Support;

use App\Models\Arrondissement;
use App\Models\Departement;
use App\Models\Facilitateur;
use App\Models\Region;
use App\Models\User;
use Illuminate\Support\Collection;

/**
 * La portée d'un compte : ce qu'il a le droit de voir.
 *
 * Tout le système hiérarchique tient dans une seule observation : facilitateurs,
 * cohortes, activités, foyers et signalements portent tous un
 * `arrondissement_id`. Une portée n'est donc JAMAIS qu'une liste
 * d'arrondissements — le national les a tous les vingt-neuf, une délégation
 * régionale a ceux de sa région, un superviseur en a exactement un.
 *
 * Ce qui suit calcule cette liste une fois. `LimiteParPortee` l'applique. Il
 * n'existe aucun autre endroit où filtrer : une condition de portée écrite à la
 * main dans un contrôleur est un défaut, parce que c'est celle-là qu'on oubliera
 * un jour dans une nouvelle requête.
 *
 * Le facilitateur n'est pas un `user` : sa portée est lui-même, et se restreint
 * en plus par `facilitateur_id`.
 */
readonly class Portee
{
    private function __construct(
        public string $niveau,
        public ?int $regionId,
        public ?int $departementId,
        public ?int $arrondissementId,
        public ?int $facilitateurId,
        public string $libelle,
    ) {}

    public static function pour(User $user): self
    {
        return new self(
            niveau: $user->niveau,
            regionId: $user->region_id,
            departementId: $user->departement_id,
            arrondissementId: $user->arrondissement_id,
            facilitateurId: null,
            libelle: match ($user->niveau) {
                'national' => 'Cameroun',
                'region' => $user->region?->libelle ?? '',
                'departement' => $user->departement?->libelle ?? '',
                'arrondissement' => $user->arrondissement?->libelle ?? '',
            },
        );
    }

    public static function pourFacilitateur(Facilitateur $facilitateur): self
    {
        return new self(
            niveau: 'facilitateur',
            regionId: null,
            departementId: null,
            arrondissementId: $facilitateur->arrondissement_id,
            facilitateurId: $facilitateur->id,
            libelle: $facilitateur->arrondissement?->libelle ?? '',
        );
    }

    /** Le national ne filtre rien : il voit jusqu'au dernier parent des dix régions. */
    public function estNationale(): bool
    {
        return $this->niveau === 'national';
    }

    /**
     * Les arrondissements que cette portée couvre.
     *
     * `null` signifie « tous », et non « aucun » — la distinction est capitale :
     * une liste vide interprétée comme « tous » ouvrirait tout le pays à un
     * compte mal configuré.
     *
     * @return ?Collection<int, int>
     */
    public function arrondissements(): ?Collection
    {
        return match ($this->niveau) {
            'national' => null,

            'region' => Arrondissement::where('region_id', $this->regionId)->pluck('id'),

            'departement' => Arrondissement::where('departement_id', $this->departementId)->pluck('id'),

            'arrondissement', 'facilitateur' => collect([$this->arrondissementId])->filter(),
        };
    }

    /** Une portée peut-elle atteindre cet arrondissement ? */
    public function couvre(?int $arrondissementId): bool
    {
        if ($this->estNationale()) {
            return true;
        }

        if ($arrondissementId === null) {
            return false;
        }

        return $this->arrondissements()?->contains($arrondissementId) ?? true;
    }

    public function estFacilitateur(): bool
    {
        return $this->niveau === 'facilitateur';
    }

    /* ---------------------------------------------------------------------- */
    /* La descente                                                            */
    /* ---------------------------------------------------------------------- */

    /**
     * Le niveau immédiatement en dessous.
     *
     * C'est lui qui découpe le tableau de bord : le national se lit en régions,
     * une région en départements, un arrondissement en facilitateurs. Un seul
     * tableau de bord, découpé différemment selon d'où on le regarde.
     */
    public function sousNiveau(): ?string
    {
        return match ($this->niveau) {
            'national' => 'region',
            'region' => 'departement',
            'departement' => 'arrondissement',
            'arrondissement' => 'facilitateur',
            'facilitateur' => null,
        };
    }

    public static function region(Region $region): self
    {
        return new self('region', $region->id, null, null, null, $region->libelle);
    }

    public static function departement(Departement $departement): self
    {
        return new self('departement', $departement->region_id, $departement->id,
            null, null, $departement->libelle);
    }

    public static function arrondissement(Arrondissement $arrondissement): self
    {
        return new self('arrondissement', $arrondissement->region_id,
            $arrondissement->departement_id, $arrondissement->id, null,
            $arrondissement->libelle);
    }

    /**
     * Cette portée en contient-elle une autre ?
     *
     * La question de sécurité de toute la descente : un délégué régional peut
     * ouvrir le tableau de bord d'un de SES départements, jamais d'un autre.
     * La réponse ne repose sur aucun raisonnement hiérarchique : une portée est
     * une liste d'arrondissements, et contenir, c'est inclure cette liste.
     */
    public function contient(self $autre): bool
    {
        if ($this->estNationale()) {
            return true;
        }

        $siens = $autre->arrondissements();

        // Le national ne peut jamais être contenu par autre chose que lui-même.
        if ($siens === null) {
            return false;
        }

        // Une portée vide n'est contenue nulle part : elle signale un compte mal
        // configuré, et on ne lui ouvre rien.
        return $siens->isNotEmpty() && $siens->diff($this->arrondissements())->isEmpty();
    }
}
