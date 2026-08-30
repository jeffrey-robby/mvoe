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
                    niveau: reponse.superviseur.niveau,
                    portee: reponse.superviseur.portee,
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

/**
 * L'en-tête de la coquille.
 *
 * La coquille est rendue par le serveur, qui ne sait rien de la session : le
 * jeton vit dans le navigateur. C'est donc ici qu'on inscrit la portée du
 * compte. Elle n'est pas décorative : personne ne doit croire lire tout un
 * département alors qu'il ne lit qu'un arrondissement.
 */
export function enteteDelegation() {
    const session = sessionDelegation.lire();

    return {
        portee: session?.portee ?? null,
        nom: session?.nom ?? null,
        niveau: session?.niveau ?? null,

        // Seul un superviseur d'arrondissement enregistre un facilitateur : le
        // serveur le refuse aux autres niveaux. Autant ne pas leur proposer un
        // écran dont la seule issue est un refus.
        get peutEnregistrer() {
            return this.niveau === 'arrondissement';
        },
    };
}

/* -------------------------------------------------------------------------- */

/**
 * Le tableau de bord. Un seul, pour les cinq niveaux.
 *
 * Le serveur rend toujours la même forme : une portée, des indicateurs, et le
 * découpage du niveau en dessous. Cet écran ne sait donc pas à quel niveau il
 * est, et n'a pas besoin de le savoir — il affiche ce qu'on lui donne. C'est
 * exactement ce que « un composant, cinq niveaux » veut dire côté interface.
 *
 * La descente ne recharge pas la page : elle rappelle la même route avec une
 * cible. Le serveur vérifie que cette cible est dans la portée du compte ;
 * l'écran ne décide de rien.
 */
export function tableauDeBord() {
    return {
        portee: null,
        indicateurs: null,
        decoupage: null,
        fil: [],
        seuilInactivite: null,
        cible: null,
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
                const donnees = await api.tableauDeBord(sessionDelegation.jeton(), this.cible);

                this.portee = donnees.portee;
                this.indicateurs = donnees.indicateurs;
                this.decoupage = donnees.decoupage;
                this.fil = donnees.fil ?? [];
                this.seuilInactivite = donnees.seuil_inactivite_jours;
            } catch (e) {
                traiterErreur(this, e);
            } finally {
                this.chargement = false;
            }
        },

        /**
         * Descendre d'un cran.
         *
         * Deux lignes ne s'ouvrent pas : un facilitateur, sous lequel il n'y a
         * plus de territoire, et une région où le programme n'est pas déployé,
         * sous laquelle il n'y a rien du tout. Descendre y mènerait à un
         * tableau vide, qui se lit comme une panne.
         */
        async ouvrir(ligne) {
            if (! this.ouvrable(ligne)) return;

            this.cible = { niveau: this.decoupage.niveau, entite: ligne.id };
            await this.charger();
        },

        ouvrable(ligne) {
            return this.descendable && ligne.peuplee !== false;
        },

        async revenir(maillon) {
            this.cible = maillon.niveau ? { niveau: maillon.niveau, entite: maillon.entite } : null;
            await this.charger();
        },

        get descendable() {
            return this.decoupage !== null && this.decoupage.niveau !== 'facilitateur';
        },

        /** Les nombres se lisent alignés ; « aucune donnée » se dit. */
        nombre(valeur) {
            return valeur === null || valeur === undefined
                ? '—'
                : String(valeur).replace('.', ',');
        },
    };
}

/* -------------------------------------------------------------------------- */

/**
 * La file des signalements.
 *
 * Le systeme n'a prevenu personne : ces signalements attendent ici, dans la
 * file d'un humain qui juge et decide. C'est deliberement l'ecran le plus
 * simple de l'administration — une liste, un statut, une suite a ecrire.
 *
 * La suite donnee n'est pas un champ de confort : c'est ce que le facilitateur
 * lira, et la seule raison pour laquelle il en fera un deuxieme.
 */
export function signalements() {
    return {
        portee: null,
        synthese: null,
        liste: [],
        filtre: 'ouverts',
        ouvert: null,
        statut: 'examine',
        suite: '',
        chargement: true,
        occupe: false,
        erreur: null,

        async init() {
            if (!exigerUneSession(this)) return;

            await this.charger();
        },

        async charger() {
            this.chargement = true;
            this.erreur = null;

            try {
                const donnees = await api.signalements(sessionDelegation.jeton());

                this.portee = donnees.portee;
                this.synthese = donnees.synthese;
                this.liste = donnees.signalements;
            } catch (e) {
                traiterErreur(this, e);
            } finally {
                this.chargement = false;
            }
        },

        get affiches() {
            return this.filtre === 'ouverts'
                ? this.liste.filter((s) => s.ouvert)
                : this.liste;
        },

        ouvrir(signalement) {
            this.ouvert = signalement;
            this.statut = signalement.ouvert ? 'examine' : signalement.statut;
            this.suite = signalement.suite_donnee ?? '';
        },

        fermer() {
            this.ouvert = null;
            this.erreur = null;
        },

        /** Orienter ou clore sans ecrire la suite n'a pas de sens. */
        get suiteRequise() {
            return this.statut === 'oriente' || this.statut === 'clos';
        },

        get peutValider() {
            return !this.suiteRequise || this.suite.trim() !== '';
        },

        async traiter() {
            if (!this.peutValider || this.occupe) return;

            this.occupe = true;
            this.erreur = null;

            try {
                await api.traiterSignalement(sessionDelegation.jeton(), this.ouvert.id, {
                    statut: this.statut,
                    suite_donnee: this.suite.trim() || null,
                });

                this.fermer();
                await this.charger();
            } catch (e) {
                traiterErreur(this, e);
            } finally {
                this.occupe = false;
            }
        },
    };
}

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
        portee: null,
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

                this.portee = donnees.portee;
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

/* -------------------------------------------------------------------------- */

/**
 * Enregistrement d'un facilitateur.
 *
 * Un facilitateur ne s'inscrit jamais lui-même : son superviseur l'enregistre et
 * lui remet ses identifiants en main propre.
 *
 * Le formulaire ne demande NI arrondissement NI mot de passe. Le premier est
 * celui du superviseur connecté, le second est généré côté serveur. Rien ici ne
 * permet de créer un facilitateur ailleurs que chez soi.
 */
export function enregistrerFacilitateur() {
    return {
        nom: '',
        telephone: '',
        email: '',
        typeJuridique: '',
        organisation: '',
        dateFormation: new Date().toISOString().slice(0, 10),

        types: [],
        resultat: null,
        erreur: null,
        erreurs: {},
        occupe: false,

        // La portée du compte, pour dire à qui cet écran s'adresse. Le serveur
        // refuse de toute façon les autres niveaux ; c'est ici qu'on l'explique
        // avant que quelqu'un ne remplisse le formulaire pour rien.
        niveau: null,
        portee: null,

        get peutEnregistrer() {
            return this.niveau === 'arrondissement';
        },

        async init() {
            if (!exigerUneSession(this)) return;

            const session = sessionDelegation.lire();
            this.niveau = session?.niveau ?? null;
            this.portee = session?.portee ?? null;

            if (!this.peutEnregistrer) return;

            try {
                this.types = (await api.typesJuridiques(sessionDelegation.jeton())).types;
            } catch (e) {
                traiterErreur(this, e);
            }
        },

        get peutValider() {
            return this.nom.trim() !== ''
                && this.telephone.trim() !== ''
                && this.typeJuridique !== ''
                && this.dateFormation !== '';
        },

        async valider() {
            this.erreur = null;
            this.erreurs = {};
            this.occupe = true;

            try {
                this.resultat = await api.enregistrerFacilitateur(sessionDelegation.jeton(), {
                    nom: this.nom.trim(),
                    telephone: this.telephone.trim(),
                    email: this.email.trim() || null,
                    type_juridique: this.typeJuridique,
                    organisation_rattachement: this.organisation.trim() || null,
                    date_formation_initiale: this.dateFormation,
                });
            } catch (e) {
                if (e.statut === 422) {
                    this.erreurs = e.corps?.errors ?? {};
                    this.erreur = 'Vérifiez les champs signalés.';
                } else {
                    traiterErreur(this, e);
                }
            } finally {
                this.occupe = false;
            }
        },

        /** Le superviseur recopie les identifiants ; l'impression les met au propre. */
        imprimer() {
            window.print();
        },

        recommencer() {
            this.resultat = null;
            this.nom = '';
            this.telephone = '';
            this.email = '';
            this.typeJuridique = '';
            this.organisation = '';
        },
    };
}
