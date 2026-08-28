import { api, ErreurHorsLigne } from './api.js';

/*
|------------------------------------------------------------------------------
| L'espace parent
|------------------------------------------------------------------------------
|
| Espace SECONDAIRE et optionnel. La majorité des parents du programme n'y
| accédera jamais : ils sont servis par la séance, par leur binôme et par la
| radio. Ce qui suit ne remplace rien, il s'ajoute pour ceux qui possèdent un
| téléphone personnel.
|
| Les sept règles, traduites en code :
|
|  1. Le parent vient au système ; le système ne va jamais vers lui. Rien ici
|     n'émet, ne notifie, ne relance.
|  2. Tout ce qui s'affiche peut être lu par n'importe qui du foyer sans
|     conséquence : aucun contenu privé, aucun historique de confidences.
|  3. Pas de « rester connecté ». La session vit dans `sessionStorage` : elle
|     meurt avec l'onglet. Un bouton de sortie est visible sur chaque écran.
|  4. Aucun score, aucun échec, aucun classement, aucune série.
|  5. Tout est audible. Aucun parcours ne dépend de la capacité à lire.
|  6. L'assistant retrouve, il ne rédige pas (écran suivant).
|  7. Accès interdit aux moins de 18 ans.
|
*/

const CLE = 'mvoe.parent';
const CLE_LECTURE = 'mvoe.parent.lecture';

export const sessionParent = {
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
        sessionStorage.removeItem(CLE_LECTURE);
    },
    jeton() {
        return this.lire()?.jeton ?? null;
    },
    langue() {
        return this.lire()?.langue ?? 'fr';
    },
    definirLangue(langue) {
        const s = this.lire();

        if (s) this.ouvrir({ ...s, langue });
    },
};

/*
| Où en est le parent dans un épisode.
|
| En `sessionStorage`, donc effacé à la fermeture de l'onglet. Un historique
| d'écoute qui persisterait sur un téléphone partagé dirait à quelqu'un d'autre
| ce que ce parent écoute, et quand. Le serveur, lui, n'en sait jamais rien.
*/
const lecture = {
    tout() {
        try {
            return JSON.parse(sessionStorage.getItem(CLE_LECTURE)) ?? {};
        } catch {
            return {};
        }
    },
    position(episodeId) {
        return this.tout()[episodeId] ?? 0;
    },
    noter(episodeId, secondes) {
        const tout = this.tout();
        tout[episodeId] = Math.floor(secondes);
        sessionStorage.setItem(CLE_LECTURE, JSON.stringify(tout));
    },
};

const LANGUES = [
    { code: 'fr', libelle: 'Français' },
    { code: 'en', libelle: 'English' },
    { code: 'bulu', libelle: 'Bulu' },
];

/** Un seul lecteur à la fois : deux voix qui se superposent sont inaudibles. */
let lecteurCourant = null;

export function jouer(url) {
    if (!url) return;

    if (lecteurCourant) {
        lecteurCourant.pause();
        lecteurCourant.currentTime = 0;
    }

    lecteurCourant = new Audio(url);
    // Un enregistrement absent ne doit rien casser : l'écran reste utilisable.
    lecteurCourant.play().catch(() => {});
}

function exigerUneSession() {
    if (!sessionParent.jeton()) {
        window.location.href = '/parent';

        return false;
    }

    return true;
}

/* -------------------------------------------------------------------------- */

/**
 * Écran d'entrée.
 *
 * La langue se choisit EN PREMIER, et les trois options sont énoncées à voix
 * haute : on ne peut pas demander à quelqu'un de lire « Français » dans une
 * langue qu'il n'a pas encore choisie.
 */
export function entreeParent() {
    return {
        etape: 'langue',
        langue: null,
        langues: LANGUES,

        codeParent: '',
        codeAcces: '',

        // 'majeur' | 'mineur' | null. Deux choix explicites, et non une case a
        // cocher : avec une case, declarer qu'on a moins de 18 ans est
        // impossible — on ne peut que rester bloque sans comprendre pourquoi.
        // Or cette declaration doit ORIENTER vers un facilitateur, pas murer.
        age: null,

        erreur: null,
        refusMineur: false,
        occupe: false,

        choisirLangue(code) {
            this.langue = code;
            this.etape = 'code';
            jouer(`/audio/interface/entree-code-${code}.wav`);
        },

        ecouterLangue(code) {
            jouer(`/audio/interface/langue-${code}.wav`);
        },

        ecouterConsigne() {
            jouer(`/audio/interface/entree-code-${this.langue}.wav`);
        },

        declarerAge(valeur) {
            this.age = valeur;

            // Le refus est immediat : inutile de lui faire saisir des codes
            // pour lui dire ensuite que l'acces lui est ferme.
            this.refusMineur = valeur === 'mineur';
        },

        get peutValider() {
            return this.codeParent.trim() !== ''
                && this.codeAcces.trim() !== ''
                && this.age === 'majeur';
        },

        async valider() {
            this.erreur = null;
            this.refusMineur = false;
            this.occupe = true;

            try {
                const reponse = await api.connexionParent({
                    code_parent: this.codeParent.trim().toUpperCase(),
                    code_acces: this.codeAcces.trim(),
                    majeur: this.age === 'majeur',
                });

                sessionParent.ouvrir({
                    jeton: reponse.jeton,
                    code_parent: reponse.parent.code_parent,
                    // La langue choisie à l'écran prime sur celle enregistrée
                    // au dossier : c'est celle que le parent vient de dire.
                    langue: this.langue ?? reponse.parent.langue_pref,
                });

                window.location.href = '/parent/accueil';
            } catch (e) {
                if (e.statut === 403) {
                    // Règle 7. Pas de reproche, pas de blocage définitif :
                    // on oriente vers la seule personne qui peut aider.
                    this.refusMineur = true;

                    return;
                }

                this.erreur =
                    e instanceof ErreurHorsLigne
                        ? "Pas de réseau pour l'instant."
                        : 'Ce code parent et ce code à 4 chiffres ne vont pas ensemble.';
            } finally {
                this.occupe = false;
            }
        },
    };
}

/* -------------------------------------------------------------------------- */

/**
 * Base commune aux écrans de l'espace parent : la langue, la sortie, l'écoute.
 * Chaque écran en hérite pour que le bouton de sortie et le sélecteur de langue
 * soient partout, sans exception.
 */
function baseParent() {
    return {
        langue: sessionParent.langue(),
        langues: LANGUES,
        erreur: null,

        changerLangue(code) {
            this.langue = code;
            sessionParent.definirLangue(code);

            if (typeof this.recharger === 'function') this.recharger();
        },

        ecouter(url) {
            jouer(url);
        },

        /**
         * `tel:` n'accepte pas les espaces du numéro affiché.
         *
         * Un appel, jamais un message : le système n'écrit à personne. C'est le
         * parent qui décide d'appeler, et rien n'est envoyé en son nom.
         */
        lienTelephone(numero) {
            return 'tel:' + numero.split(' ').join('');
        },

        /** Règle 3 : un bouton de sortie visible sur chaque écran. */
        async sortir() {
            await api.fermerSession(sessionParent.jeton()).catch(() => null);
            sessionParent.fermer();
            window.location.href = '/parent';
        },

        signaler(e) {
            if (e?.statut === 401) {
                // La session est courte et ne se renouvelle pas.
                sessionParent.fermer();
                window.location.href = '/parent';

                return;
            }

            this.erreur = 'Impossible de charger pour le moment.';
        },
    };
}

/* -------------------------------------------------------------------------- */

/** Trois grandes cartes, chacune audible d'un appui. */
export function accueilParent() {
    return {
        ...baseParent(),

        cartes: [
            { cle: 'ecouter', titre: 'Écouter', lien: '/parent/ecouter' },
            { cle: 'feuilleton', titre: 'Le feuilleton', lien: '/parent/feuilleton' },
            { cle: 'question', titre: 'Poser une question', lien: '/parent/question' },
        ],

        init() {
            exigerUneSession();
        },

        audioCarte(cle) {
            return `/audio/interface/accueil-${cle}-${this.langue}.wav`;
        },
    };
}

/* -------------------------------------------------------------------------- */

/** Écran « Écouter » : les modules, puis les unités, puis une unité. */
export function ecouterParent() {
    return {
        ...baseParent(),

        vue: 'modules',
        modules: [],
        module: null,
        unites: [],
        unite: null,
        modalite: 'audio',
        chargement: true,

        async init() {
            if (!exigerUneSession()) return;

            await this.chargerModules();
        },

        recharger() {
            if (this.vue === 'unite') return this.ouvrirUnite(this.unite.id);
            if (this.vue === 'unites') return this.ouvrirModule(this.module);

            return this.chargerModules();
        },

        async chargerModules() {
            this.chargement = true;

            try {
                this.modules = (await api.modulesParent(sessionParent.jeton())).modules;
            } catch (e) {
                this.signaler(e);
            } finally {
                this.chargement = false;
            }
        },

        async ouvrirModule(module) {
            if (!module.renseigne) return;

            this.chargement = true;
            this.module = module;

            try {
                const donnees = await api.unitesParent(
                    sessionParent.jeton(), module.id, this.langue,
                );

                this.unites = donnees.unites;
                this.vue = 'unites';
            } catch (e) {
                this.signaler(e);
            } finally {
                this.chargement = false;
            }
        },

        async ouvrirUnite(uniteId) {
            this.chargement = true;

            try {
                this.unite = await api.uniteParent(
                    sessionParent.jeton(), uniteId, this.langue, this.modalite,
                );
                this.vue = 'unite';
            } catch (e) {
                this.signaler(e);
            } finally {
                this.chargement = false;
            }
        },

        async basculer(modalite) {
            this.modalite = modalite;
            await this.ouvrirUnite(this.unite.id);
        },

        retour() {
            if (this.vue === 'unite') {
                this.vue = 'unites';
                this.unite = null;

                return;
            }

            if (this.vue === 'unites') {
                this.vue = 'modules';
                this.module = null;

                return;
            }

            window.location.href = '/parent/accueil';
        },

        get versionManquante() {
            return this.unite && this.unite.langue_servie !== this.unite.langue_demandee;
        },
    };
}

/* -------------------------------------------------------------------------- */

/**
 * Le feuilleton.
 *
 * La reprise se fait « là où le parent s'était arrêté, sans jamais lui
 * reprocher son absence » : pas de « vous avez manqué trois épisodes », pas de
 * série à ne pas briser, pas de pourcentage d'avancement.
 */
export function feuilletonParent() {
    return {
        ...baseParent(),

        feuilleton: null,
        episodeCourant: null,
        chargement: true,

        async init() {
            if (!exigerUneSession()) return;

            await this.recharger();
        },

        async recharger() {
            this.chargement = true;

            try {
                const donnees = await api.feuilletonsParent(sessionParent.jeton(), this.langue);

                this.feuilleton = donnees.feuilletons[0] ?? null;
            } catch (e) {
                this.signaler(e);
            } finally {
                this.chargement = false;
            }
        },

        get episodes() {
            return this.feuilleton?.episodes ?? [];
        },

        /** Là où il s'était arrêté. Zéro si l'épisode n'a jamais été ouvert. */
        positionDe(episode) {
            return lecture.position(episode.id);
        },

        commence(episode) {
            return this.positionDe(episode) > 0;
        },

        ouvrir(episode) {
            this.episodeCourant = episode;
        },

        /** Reprise silencieuse : on repositionne, on ne commente pas. */
        reprendre(element, episode) {
            const position = this.positionDe(episode);

            if (position > 0 && position < episode.duree_secondes - 2) {
                element.currentTime = position;
            }
        },

        noter(element, episode) {
            lecture.noter(episode.id, element.currentTime);
        },

        minutes(secondes) {
            const m = Math.floor(secondes / 60);
            const s = Math.floor(secondes % 60);

            return `${m}:${String(s).padStart(2, '0')}`;
        },

        fermer() {
            this.episodeCourant = null;
        },
    };
}

/* -------------------------------------------------------------------------- */

/**
 * « Trouver un facilitateur ».
 *
 * AUCUN compte n'est nécessaire : c'est le seul écran du système où un inconnu
 * obtient quelque chose, et c'est voulu — quelqu'un qui a besoin d'un contact
 * humain ne doit pas d'abord se connecter. C'est aussi la sortie proposée à un
 * parent mineur, dont l'accès vient d'être refusé : un refus sans issue serait
 * une porte fermée, pas une orientation.
 *
 * L'arrondissement choisi n'est JAMAIS enregistré : savoir qu'une personne d'un
 * arrondissement donné a cherché de l'aide est déjà une information de trop.
 */
export function annuaireParent() {
    return {
        ...baseParent(),

        arrondissements: [],
        arrondissement: '',
        resultat: null,
        chargement: true,

        /** Accessible sans session : la sortie n'a alors pas lieu d'être. */
        get connecte() {
            return Boolean(sessionParent.jeton());
        },

        async init() {
            try {
                this.arrondissements = (await api.arrondissements()).arrondissements;
            } catch {
                this.erreur = "La liste des arrondissements n'a pas pu être chargée.";
            } finally {
                this.chargement = false;
            }
        },

        async chercher() {
            if (this.arrondissement === '') return;

            this.erreur = null;
            this.resultat = null;

            try {
                this.resultat = await api.annuaire(this.arrondissement);
            } catch {
                this.erreur = 'Impossible de chercher pour le moment.';
            }
        },
    };
}

/* -------------------------------------------------------------------------- */

/**
 * Les questions de la semaine.
 *
 * Trois questions, deux ou trois réponses illustrées. Après chaque réponse,
 * l'application dit ce que propose le programme et pourquoi.
 *
 * JAMAIS de bonne ou mauvaise réponse affichée, jamais de score, jamais de
 * total. L'API ne renvoie d'ailleurs aucun champ qui permettrait d'en fabriquer
 * un : `est_attendue` est masqué côté serveur, et la réponse à un choix ne
 * contient que l'explication et sa référence.
 *
 * L'explication est portée par la QUESTION, pas par l'option : le texte lu est
 * le même quel que soit le choix du parent. C'est cela qui rend l'absence de
 * verdict structurelle plutôt que déclarative.
 */
export function questionsSemaine() {
    return {
        ...baseParent(),

        questions: [],
        rang: 0,
        choix: null,
        explication: null,
        chargement: true,
        termine: false,

        async init() {
            if (!exigerUneSession()) return;

            await this.recharger();
        },

        async recharger() {
            this.chargement = true;

            try {
                this.questions = (await api.questionsSemaine(sessionParent.jeton())).questions;
            } catch (e) {
                this.signaler(e);
            } finally {
                this.chargement = false;
            }
        },

        get question() {
            return this.questions[this.rang] ?? null;
        },

        async repondre(option) {
            if (this.explication) return;

            this.choix = option.id;

            try {
                const reponse = await api.repondreQuestion(
                    sessionParent.jeton(), this.question.id, option.id,
                );

                this.explication = reponse;
            } catch (e) {
                this.signaler(e);
                this.choix = null;
            }
        },

        suivante() {
            this.choix = null;
            this.explication = null;

            if (this.rang + 1 >= this.questions.length) {
                this.termine = true;

                return;
            }

            this.rang++;
        },

        get derniere() {
            return this.rang + 1 >= this.questions.length;
        },
    };
}

/* -------------------------------------------------------------------------- */

/**
 * L'assistant à corpus fermé.
 *
 * AUCUN modèle de langage n'intervient. L'assistant RETROUVE une unité validée
 * du curriculum et la restitue mot pour mot, avec sa référence de module. Toute
 * phrase lue ici a été écrite puis validée par le ministère.
 *
 * Deux entrées, une seule mécanique : la liste de situations fréquentes — pour
 * qui ne sait pas écrire — et le champ libre passent par le même appariement.
 * Les libellés de situations ne sont pas des réponses pré-écrites, et plusieurs
 * ne trouvent rien.
 *
 * LE REFUS EST SOIGNÉ AUTANT QUE LA RÉPONSE. Ce n'est pas une erreur : c'est ce
 * que le système doit savoir faire sur un sujet de protection de l'enfance.
 */
export function assistantParent() {
    return {
        ...baseParent(),

        situations: [],
        texte: '',
        resultat: null,
        question: null,
        chargement: true,
        occupe: false,

        async init() {
            if (!exigerUneSession()) return;

            await this.recharger();
        },

        async recharger() {
            this.chargement = true;

            try {
                this.situations = (await api.situations(sessionParent.jeton(), this.langue)).situations;
            } catch (e) {
                this.signaler(e);
            } finally {
                this.chargement = false;
            }
        },

        async poserSituation(situation) {
            this.question = situation.libelle;
            await this.envoyer({ situation_id: situation.id, langue: this.langue });
        },

        async poserTexte() {
            if (this.texte.trim() === '') return;

            this.question = this.texte.trim();
            await this.envoyer({ texte: this.texte.trim(), langue: this.langue });
        },

        async envoyer(charge) {
            this.occupe = true;
            this.resultat = null;
            this.erreur = null;

            try {
                this.resultat = await api.poserQuestion(sessionParent.jeton(), charge);
            } catch (e) {
                this.signaler(e);
            } finally {
                this.occupe = false;
            }
        },

        recommencer() {
            this.resultat = null;
            this.question = null;
            this.texte = '';
        },
    };
}
