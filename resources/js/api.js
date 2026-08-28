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

    /* --- Délégation ------------------------------------------------------ */

    connexionSuperviseur: (identifiants) =>
        appeler('superviseur/session', { methode: 'POST', corps: identifiants }),

    registre: (jeton) => appeler('superviseur/facilitateurs', { jeton }),

    cohortesSuperviseur: (jeton) => appeler('superviseur/cohortes', { jeton }),

    rapport: (jeton, annee, trimestre) =>
        appeler(`superviseur/rapport?annee=${annee}&trimestre=${trimestre}`, { jeton }),

    changerRatio: (jeton, cohorteId, ratioMax) =>
        appeler(`superviseur/cohortes/${cohorteId}`, {
            methode: 'PATCH',
            jeton,
            corps: { ratio_max: ratioMax },
        }),

    /* --- Espace parent --------------------------------------------------- */

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
