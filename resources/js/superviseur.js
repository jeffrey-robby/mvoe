import { api, ErreurHorsLigne } from './api.js';

/*
|------------------------------------------------------------------------------
| Les écrans de la délégation
|------------------------------------------------------------------------------
|
| Le superviseur travaille assis, en réseau, sur un poste souvent partagé entre
| plusieurs agents. Sa session vit donc dans `sessionStorage` : elle disparaît à
| la fermeture de l'onglet, et elle ne se mélange jamais au magasin hors ligne
| du facilitateur, qui lui doit survivre des jours.
|
| Aucun de ces écrans ne fonctionne hors ligne, et c'est voulu : le livrable du
| superviseur est un DOCUMENT trimestriel, pas un outil de terrain.
|
*/

const CLE = 'mvoe.superviseur';

const sessionDelegation = {
    lire() {
        try {
            return JSON.parse(sessionStorage.getItem(CLE));
        } catch {
            return null;
        }
    },
    ouvrir(donnees) {
        sessionStorage.setItem(CLE, JSON.stringify(donnees));
    },
    fermer() {
        sessionStorage.removeItem(CLE);
    },
    jeton() {
        return this.lire()?.jeton ?? null;
    },
};

/** Redirige vers la connexion si la session n'existe pas ou n'est plus acceptée. */
function exigerUneSession(composant) {
    if (!sessionDelegation.jeton()) {
        window.location.href = '/superviseur/connexion';

        return false;
    }

    return true;
}

function traiterErreur(composant, e) {
    if (e instanceof ErreurHorsLigne) {
        composant.erreur = 'Le serveur est injoignable. Réessayez dans un instant.';

        return;
    }

    if (e.statut === 401 || e.statut === 403) {
        sessionDelegation.fermer();
        window.location.href = '/superviseur/connexion';

        return;
    }

    composant.erreur = 'Une erreur est survenue.';
}

/* -------------------------------------------------------------------------- */

export function connexionDelegation() {
    return {
        email: '',
        motDePasse: '',
        erreur: null,
        occupe: false,

        async valider() {
            this.erreur = null;
            this.occupe = true;

            try {
                const reponse = await api.connexionSuperviseur({
                    email: this.email,
                    password: this.motDePasse,
                });

                sessionDelegation.ouvrir({
                    jeton: reponse.jeton,
                    nom: reponse.superviseur.nom,
                });

                window.location.href = '/superviseur';
            } catch (e) {
                this.erreur =
                    e instanceof ErreurHorsLigne
                        ? 'Le serveur est injoignable.'
                        : 'Ces identifiants ne correspondent pas.';
            } finally {
                this.occupe = false;
            }
        },
    };
}

/* -------------------------------------------------------------------------- */

/**
 * Le registre.
 *
 * Il répond à la question à laquelle personne ne sait répondre aujourd'hui :
 * combien de facilitateurs formés sont encore actifs ? Le statut est recalculé
 * par le serveur à chaque consultation — un statut stocké se périmerait en
 * silence.
 */
export function registre() {
    return {
        nom: sessionDelegation.lire()?.nom ?? '',
        perimetre: null,
        synthese: null,
        facilitateurs: [],
        arrondissement: '',
        chargement: true,
        erreur: null,

        async init() {
            if (!exigerUneSession(this)) return;

            await this.charger();
        },

        async charger() {
            this.chargement = true;
            this.erreur = null;

            try {
                const donnees = await api.registre(sessionDelegation.jeton());

                this.perimetre = donnees.perimetre;
                this.synthese = donnees.synthese;
                this.facilitateurs = donnees.facilitateurs;
            } catch (e) {
                traiterErreur(this, e);
            } finally {
                this.chargement = false;
            }
        },

        get arrondissements() {
            return [...new Set(this.facilitateurs.map((f) => f.arrondissement))].sort();
        },

        get liste() {
            return this.arrondissement === ''
                ? this.facilitateurs
                : this.facilitateurs.filter((f) => f.arrondissement === this.arrondissement);
        },

        /** « jamais » se dit, il ne se laisse pas deviner par une case vide. */
        derniereActivite(f) {
            if (!f.derniere_activite) return 'jamais';

            const [a, m, j] = f.derniere_activite.split('-');

            return `${j}/${m}/${a}`;
        },

        async deconnecter() {
            await api.fermerSession(sessionDelegation.jeton()).catch(() => null);
            sessionDelegation.fermer();
            window.location.href = '/superviseur/connexion';
        },
    };
}

/* -------------------------------------------------------------------------- */

/**
 * Le rapport trimestriel.
 *
 * C'est un DOCUMENT : une photographie d'un trimestre clos, faite pour être
 * imprimée, signée et transmise. Pas un tableau de bord temps réel, pas un
 * écran de graphiques.
 */
export function rapport() {
    const maintenant = new Date();

    return {
        nom: sessionDelegation.lire()?.nom ?? '',
        annee: maintenant.getFullYear(),
        trimestre: Math.floor(maintenant.getMonth() / 3) + 1,
        donnees: null,
        chargement: true,
        erreur: null,

        async init() {
            if (!exigerUneSession(this)) return;

            await this.charger();
        },

        async charger() {
            this.chargement = true;
            this.erreur = null;
            this.donnees = null;

            try {
                this.donnees = await api.rapport(
                    sessionDelegation.jeton(),
                    this.annee,
                    this.trimestre,
                );
            } catch (e) {
                traiterErreur(this, e);
            } finally {
                this.chargement = false;
            }
        },

        get periode() {
            return `${this.trimestre}ᵉ trimestre ${this.annee}`;
        },

        get vide() {
            return this.donnees?.synthese.seances_tenues === 0;
        },

        get genereLe() {
            return new Date().toLocaleDateString('fr-FR', {
                day: '2-digit', month: 'long', year: 'numeric',
            });
        },

        /**
         * Un nombre à la française : virgule décimale, pas point. Dans un
         * document destiné à une administration, « 2.7 » se remarque.
         */
        nombre(valeur, defaut = '—') {
            if (valeur === null || valeur === undefined) return defaut;

            return typeof valeur === 'number'
                ? valeur.toLocaleString('fr-FR', { maximumFractionDigits: 1 })
                : valeur;
        },

        /** Accord en nombre : « 1 a animé », « 3 ont animé ». */
        accord(n, singulier, pluriel) {
            return `${this.nombre(n)} ${n <= 1 ? singulier : pluriel}`;
        },

        get phraseDispositif() {
            const s = this.donnees.synthese;

            return `${s.facilitateurs_actifs} facilitateurs actifs sur ${s.facilitateurs_formes} formés. `
                + this.accord(
                    s.facilitateurs_ayant_anime,
                    'a animé au moins une séance sur le trimestre.',
                    'ont animé au moins une séance sur le trimestre.',
                );
        },

        get phraseDelai() {
            const d = this.donnees.synthese.delai_moyen_remontee_jours;

            if (d === null) return 'Délai moyen de remontée : non calculable.';

            return 'Délai moyen de remontée : ' + this.accord(d, 'jour.', 'jours.');
        },

        /**
         * L'export passe par l'impression du navigateur : « Enregistrer en PDF »
         * produit exactement le document affiché, sans dépendance, sans police
         * manquante, et sur n'importe quel poste de la délégation.
         */
        exporter() {
            window.print();
        },
    };
}

/* -------------------------------------------------------------------------- */

/**
 * Les paramètres.
 *
 * Le plafond d'une cohorte est une DONNÉE, jamais une constante : aucun 20
 * n'est écrit dans le code, et la migration ne lui donne même pas de valeur par
 * défaut. Le passer de 20 à 10 se fait donc ici, en direct, sans déploiement.
 */
export function parametres() {
    return {
        nom: sessionDelegation.lire()?.nom ?? '',
        cohortes: [],
        chargement: true,
        erreur: null,

        // Dernière modification, affichée en clair : devant un jury, une action
        // sans confirmation visible est une action dont on doute.
        modification: null,

        async init() {
            if (!exigerUneSession(this)) return;

            await this.charger();
        },

        async charger() {
            this.chargement = true;

            try {
                this.cohortes = (await api.cohortesSuperviseur(sessionDelegation.jeton())).cohortes;
            } catch (e) {
                traiterErreur(this, e);
            } finally {
                this.chargement = false;
            }
        },

        async changerRatio(cohorte, ratio) {
            if (ratio === cohorte.ratio_max) return;

            this.erreur = null;
            this.modification = null;

            try {
                const reponse = await api.changerRatio(
                    sessionDelegation.jeton(),
                    cohorte.id,
                    ratio,
                );

                Object.assign(cohorte, reponse.cohorte);

                this.modification = {
                    libelle: cohorte.libelle,
                    avant: reponse.modification.ratio_max.avant,
                    apres: reponse.modification.ratio_max.apres,
                    au_dela: reponse.cohorte.effectif_au_dela_du_plafond,
                };
            } catch (e) {
                traiterErreur(this, e);
            }
        },

        async deconnecter() {
            await api.fermerSession(sessionDelegation.jeton()).catch(() => null);
            sessionDelegation.fermer();
            window.location.href = '/superviseur/connexion';
        },
    };
}
