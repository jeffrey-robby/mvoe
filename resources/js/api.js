import { session } from './magasin.js';

/*
|------------------------------------------------------------------------------
| Client de l'API
|------------------------------------------------------------------------------
|
| Le kit web et les écrans de la délégation parlent à l'API exactement comme le
| fera l'application Flutter : même jeton, mêmes routes, mêmes limites. Rien ici
| ne s'appuie sur une session de navigateur.
|
| Toute panne réseau est traduite en `ErreurHorsLigne`. L'appelant doit la
| traiter comme un état NORMAL — le mode avion est le mode de travail prévu du
| facilitateur — et surtout jamais l'afficher comme une erreur.
|
*/

export class ErreurHorsLigne extends Error {}

export class ErreurApi extends Error {
    constructor(message, statut, corps) {
        super(message);
        this.statut = statut;
        this.corps = corps;
    }
}

/**
 * `jeton` peut être fourni explicitement : le superviseur travaille sur un
 * poste de la délégation et sa session ne vit pas dans le magasin hors ligne
 * du facilitateur.
 */
async function appeler(chemin, options = {}) {
    const jeton = options.jeton ?? session.jeton();

    const entetes = {
        Accept: 'application/json',
        ...(options.corps ? { 'Content-Type': 'application/json' } : {}),
        ...(jeton ? { Authorization: `Bearer ${jeton}` } : {}),
    };

    let reponse;

    try {
        reponse = await fetch(`/api/${chemin}`, {
            method: options.methode ?? 'GET',
            headers: entetes,
            body: options.corps ? JSON.stringify(options.corps) : undefined,
        });
    } catch {
        // fetch ne rejette que sur une panne réseau : c'est le mode avion.
        throw new ErreurHorsLigne('Hors ligne');
    }

    const corps = await reponse.json().catch(() => null);

    if (!reponse.ok) {
        throw new ErreurApi(corps?.message ?? 'Erreur', reponse.status, corps);
    }

    return corps;
}

export const api = {
    /* --- Kit facilitateur ------------------------------------------------ */

    connexionFacilitateur: (identifiants) =>
        appeler('facilitateur/session', { methode: 'POST', corps: identifiants }),

    cohortes: () => appeler('facilitateur/cohortes'),

    paquet: (cohorteId) => appeler(`facilitateur/cohortes/${cohorteId}/paquet`),

    envoyerEvenements: (evenements) =>
        appeler('facilitateur/evenements', { methode: 'POST', corps: { evenements } }),

    /**
     * Le tableau de bord du facilitateur : le MÊME service que celui des
     * délégations, à la cinquième portée. Il demande du réseau, et c'est le
     * seul écran du kit dans ce cas : il regarde en arrière, pas en séance.
     */
    tableauDeBordFacilitateur: () => appeler('facilitateur/tableau-de-bord'),

    /** Ses signalements ET la suite qui leur a été donnée. */
    mesSignalements: () => appeler('facilitateur/signalements'),

    /** Le catalogue de formation, quand le paquet n'est pas encore là. */
    formation: () => appeler('facilitateur/formation'),
    moduleFormation: (code) => appeler(`facilitateur/formation/${code}`),

    /* --- Délégation ------------------------------------------------------ */

    connexionSuperviseur: (identifiants) =>
        appeler('superviseur/session', { methode: 'POST', corps: identifiants }),

    /**
     * Le tableau de bord. Sans `cible`, celui du compte ; avec, celui d'une
     * entité en dessous — le serveur refuse tout ce qui sort de la portée.
     */
    tableauDeBord: (jeton, cible = null) =>
        appeler(
            'superviseur/tableau-de-bord'
                + (cible ? `?niveau=${cible.niveau}&entite=${cible.entite}` : ''),
            { jeton },
        ),

    registre: (jeton) => appeler('superviseur/facilitateurs', { jeton }),

    /* --- Signalements ---------------------------------------------------- */

    signalements: (jeton) => appeler('superviseur/signalements', { jeton }),

    /* --- Ministère -------------------------------------------------------- */

    bibliotheque: (jeton) => appeler('superviseur/bibliotheque', { jeton }),

    validerModule: (jeton, code, statut) =>
        appeler(`superviseur/bibliotheque/modules/${code}`, {
            methode: 'PATCH', jeton, corps: { statut_validation: statut },
        }),

    enregistrerLangue: (jeton, donnees, id = null) =>
        appeler(id ? `superviseur/bibliotheque/langues/${id}` : 'superviseur/bibliotheque/langues', {
            methode: id ? 'PATCH' : 'POST', jeton, corps: donnees,
        }),

    /* --- La redaction des contenus, cote ministere --------------------- */

    referentielContenus: (jeton) =>
        appeler('superviseur/contenus/referentiel', { jeton }),

    creerModuleFormation: (jeton, corps) =>
        appeler('superviseur/contenus/modules-formation', { methode: 'POST', jeton, corps }),

    ajouterSection: (jeton, code, corps) =>
        appeler(`superviseur/contenus/modules-formation/${code}/sections`, {
            methode: 'POST', jeton, corps,
        }),

    creerUnite: (jeton, corps) =>
        appeler('superviseur/contenus/unites', { methode: 'POST', jeton, corps }),

    chargerRealisation: (jeton, uniteId, corps) =>
        appeler(`superviseur/contenus/unites/${uniteId}/realisations`, {
            methode: 'POST', jeton, corps,
        }),

    validerRealisation: (jeton, id, statut) =>
        appeler(`superviseur/contenus/realisations/${id}`, {
            methode: 'PATCH', jeton, corps: { statut_validation: statut },
        }),

    campagnes: (jeton) => appeler('superviseur/campagnes', { jeton }),

    creerCampagne: (jeton, corps) =>
        appeler('superviseur/campagnes', { methode: 'POST', jeton, corps }),

    accuserCampagne: (jeton, id) =>
        appeler(`superviseur/campagnes/${id}/reception`, { methode: 'POST', jeton }),

    canaux: (jeton) => appeler('superviseur/canaux', { jeton }),

    traiterSignalement: (jeton, id, donnees) =>
        appeler(`superviseur/signalements/${id}`, { methode: 'PATCH', jeton, corps: donnees }),

    cohortesSuperviseur: (jeton) => appeler('superviseur/cohortes', { jeton }),

    rapport: (jeton, annee, trimestre) =>
        appeler(`superviseur/rapport?annee=${annee}&trimestre=${trimestre}`, { jeton }),

    typesJuridiques: (jeton) => appeler('superviseur/types-juridiques', { jeton }),

    enregistrerFacilitateur: (jeton, donnees) =>
        appeler('superviseur/facilitateurs', { methode: 'POST', jeton, corps: donnees }),

    regenererIdentifiants: (jeton, facilitateurId) =>
        appeler(`superviseur/facilitateurs/${facilitateurId}/identifiants`, { methode: 'POST', jeton }),

    changerRatio: (jeton, cohorteId, ratioMax) =>
        appeler(`superviseur/cohortes/${cohorteId}`, {
            methode: 'PATCH',
            jeton,
            corps: { ratio_max: ratioMax },
        }),

    /* --- Espace parent --------------------------------------------------- */

    /**
     * Les langues du programme. Publique : le parent choisit la sienne avant
     * de se connecter.
     */
    langues: () => appeler('langues'),

    connexionParent: (identifiants) =>
        appeler('parent/session', { methode: 'POST', corps: identifiants }),

    modulesParent: (jeton) => appeler('parent/modules', { jeton }),

    unitesParent: (jeton, moduleId, langue) =>
        appeler(`parent/modules/${moduleId}/unites?langue=${langue}`, { jeton }),

    uniteParent: (jeton, uniteId, langue, modalite) =>
        appeler(`parent/unites/${uniteId}?langue=${langue}&modalite=${modalite}`, { jeton }),

    feuilletonsParent: (jeton, langue) =>
        appeler(`parent/feuilletons?langue=${langue}`, { jeton }),

    questionsSemaine: (jeton) => appeler('parent/questions', { jeton }),

    repondreQuestion: (jeton, questionId, optionId) =>
        appeler(`parent/questions/${questionId}/reponse`, {
            methode: 'POST', jeton, corps: { option_id: optionId },
        }),

    situations: (jeton, langue) => appeler(`parent/situations?langue=${langue}`, { jeton }),

    poserQuestion: (jeton, charge) =>
        appeler('parent/assistant', { methode: 'POST', jeton, corps: charge }),

    /* --- Public ----------------------------------------------------------- */

    arrondissements: () => appeler('arrondissements'),

    annuaire: (arrondissement) =>
        appeler(`annuaire?arrondissement=${encodeURIComponent(arrondissement)}`),

    fermerSession: (jeton) => appeler('session', { methode: 'DELETE', jeton }),
};

/**
 * Le message d'une connexion refusée.
 *
 * Un refus de débit N'EST PAS un refus d'identifiants. Les confondre envoie
 * quelqu'un vérifier un code qui était juste — c'est arrivé, et l'on cherche
 * alors du côté du mot de passe pendant que le compteur redescend tout seul.
 */
export function messageDeConnexion(e, messages) {
    if (e instanceof ErreurHorsLigne) return messages.horsLigne;

    if (e?.statut === 429) {
        return 'Trop d\'essais depuis cet appareil. Attendez une minute avant de réessayer.';
    }

    if (e?.statut >= 500) {
        return 'Le serveur ne répond pas correctement. Réessayez dans un instant.';
    }

    return messages.refus;
}
