import { idb, MAGASINS } from './idb.js';

/*
|------------------------------------------------------------------------------
| Le magasin local
|------------------------------------------------------------------------------
|
| Règle absolue du projet : TOUTE DONNÉE EST ÉCRITE EN LOCAL AVANT D'ÊTRE
| AFFICHÉE À L'ÉCRAN. Jamais l'inverse. Un facilitateur en mode avion ne doit
| pas pouvoir distinguer une action réussie d'une action perdue — donc rien ne
| s'affiche tant que ce n'est pas écrit.
|
| Les données vivent dans IndexedDB. Comme IndexedDB est asynchrone :
|
|   - les ÉCRITURES sont asynchrones et doivent être attendues (`await`) avant
|     de toucher à l'écran. C'est la règle ci-dessus, rendue impossible à
|     contourner par distraction ;
|   - les LECTURES restent synchrones, servies par un miroir en mémoire chargé
|     une fois au démarrage par `ouvrirMagasin()`. Les gabarits Alpine restent
|     donc simples et ne manipulent jamais de promesse.
|
| Les fichiers audio ne sont pas ici : ils vont dans la Cache API, par le
| service worker.
|
*/

const CLES = {
    jeton: 'jeton',
    facilitateur: 'facilitateur',
    paquet: 'paquet',
    seance: 'seance-en-cours',
    libelles: 'libelles-locaux',
};

/** Miroir synchrone d'IndexedDB. Rempli une fois, tenu à jour à chaque écriture. */
const memoire = {
    jeton: null,
    facilitateur: null,
    paquet: null,
    seance: null,
    libelles: {},
    file: [],
};

/**
 * À appeler UNE fois, avant de démarrer Alpine : sans ce chargement, les
 * écrans liraient un magasin vide et afficheraient un kit inexistant.
 */
export async function ouvrirMagasin() {
    if (!(await idb.utilisable())) {
        // Navigation privée, stockage refusé : on ne fait pas semblant.
        return false;
    }

    memoire.jeton = (await idb.lire(MAGASINS.etat, CLES.jeton)) ?? null;
    memoire.facilitateur = (await idb.lire(MAGASINS.etat, CLES.facilitateur)) ?? null;
    memoire.paquet = (await idb.lire(MAGASINS.etat, CLES.paquet)) ?? null;
    memoire.seance = (await idb.lire(MAGASINS.etat, CLES.seance)) ?? null;
    memoire.libelles = (await idb.lire(MAGASINS.etat, CLES.libelles)) ?? {};

    const evenements = (await idb.tout(MAGASINS.file)) ?? [];

    // L'ordre d'émission est l'ordre d'envoi : le serveur traite les ouvertures
    // de séance en premier, mais une correction doit rester après ce qu'elle
    // corrige.
    memoire.file = evenements.sort((a, b) => a.rang - b.rang);

    return true;
}

const etat = (cle, valeur) => idb.ecrire(MAGASINS.etat, valeur, cle);

/* -------------------------------------------------------------------------- */
/* Session                                                                     */
/* -------------------------------------------------------------------------- */

export const session = {
    jeton: () => memoire.jeton,
    facilitateur: () => memoire.facilitateur,
    estOuverte: () => Boolean(memoire.jeton),

    async ouvrir(jeton, facilitateur) {
        await etat(CLES.jeton, jeton);
        await etat(CLES.facilitateur, facilitateur);

        memoire.jeton = jeton;
        memoire.facilitateur = facilitateur;
    },

    async fermer() {
        // On oublie la session, jamais le paquet ni la file : des séances non
        // remontées survivent à une déconnexion. Les perdre serait perdre le
        // travail d'une après-midi entière.
        await idb.supprimer(MAGASINS.etat, CLES.jeton);
        await idb.supprimer(MAGASINS.etat, CLES.facilitateur);

        memoire.jeton = null;
        memoire.facilitateur = null;
    },
};

/* -------------------------------------------------------------------------- */
/* Paquet de cohorte                                                           */
/* -------------------------------------------------------------------------- */

export const paquet = {
    lire: () => memoire.paquet,
    existe: () => Boolean(memoire.paquet),
    cohorte: () => memoire.paquet?.cohorte ?? null,
    parents: () => memoire.paquet?.parents ?? [],
    audios: () => memoire.paquet?.audios ?? [],

    module(id) {
        return memoire.paquet?.modules.find((m) => m.id === Number(id)) ?? null;
    },

    async enregistrer(donnees) {
        await etat(CLES.paquet, donnees);
        memoire.paquet = donnees;
    },
};

/* -------------------------------------------------------------------------- */
/* Libellés locaux — NE SORTENT JAMAIS DE CET APPAREIL                         */
/* -------------------------------------------------------------------------- */

/*
| Le facilitateur doit reconnaître ses vingt parents pour les pointer, mais le
| serveur ne doit jamais connaître leur identité. Il saisit donc un libellé de
| son choix pour chaque code parent — « Odile, marché », « la maman du petit
| garçon ».
|
| Ces libellés vivent SOUS LEUR PROPRE CLÉ, volontairement hors de la file
| d'envoi. Aucune fonction de ce fichier ne les recopie dans un événement, et il
| ne faut jamais en écrire une : ce sont des données nominatives, et c'est
| précisément ce que la loi n° 2024/017 nous interdit de remonter.
*/

export const libelles = {
    tous: () => memoire.libelles,
    pour: (codeParent) => memoire.libelles[codeParent] ?? null,

    async definir(codeParent, libelle) {
        const tous = { ...memoire.libelles, [codeParent]: libelle };

        await etat(CLES.libelles, tous);
        memoire.libelles = tous;
    },

    /** Purge de fin de cycle : les repères du facilitateur disparaissent. */
    async purger() {
        await idb.supprimer(MAGASINS.etat, CLES.libelles);
        memoire.libelles = {};
    },
};

/* -------------------------------------------------------------------------- */
/* Séance en cours                                                             */
/* -------------------------------------------------------------------------- */

/*
| Où en est le facilitateur dans son déroulé.
|
| Ce n'est PAS de la donnée métier : c'est l'état de son écran, gardé en local
| pour qu'un téléphone qui se met en veille, une page rechargée ou un passage
| par l'écran de pointage ne lui fassent pas perdre sa place au milieu d'une
| séance de quatre-vingt-dix minutes. Rien de tout cela n'est jamais envoyé.
|
| La séance elle-même vit dans la file d'événements, pas ici.
*/

export const seanceEnCours = {
    lire: () => memoire.seance,
    estTerminee: () => memoire.seance?.terminee === true,

    async ouvrir(donnees) {
        const seance = { ...donnees, terminee: false };

        await etat(CLES.seance, seance);
        memoire.seance = seance;
    },

    async mettreAJour(champs) {
        if (!memoire.seance) return null;

        const suivante = { ...memoire.seance, ...champs };

        await etat(CLES.seance, suivante);
        memoire.seance = suivante;

        return suivante;
    },

    /** La fiche de fidélité ne s'ouvre qu'après la séance, jamais pendant. */
    terminer() {
        return this.mettreAJour({ terminee: true });
    },

    async oublier() {
        await idb.supprimer(MAGASINS.etat, CLES.seance);
        memoire.seance = null;
    },
};

/* -------------------------------------------------------------------------- */
/* File d'envoi                                                                */
/* -------------------------------------------------------------------------- */

/*
| Des ÉVÉNEMENTS horodatés et idempotents, jamais des états. Chaque événement
| porte un UUID généré ici, hors ligne, avant tout contact avec le serveur :
| c'est lui qui permet de rejouer un envoi coupé sans rien dupliquer.
|
| Un événement n'est retiré de la file que lorsque le serveur l'a rangé dans
| `acceptes` ou dans `doublons`. Tant qu'il reste, il sera renvoyé.
*/

let rangSuivant = 0;

export const file = {
    tous: () => memoire.file,
    estVide: () => memoire.file.length === 0,

    async ajouter(evenement) {
        const complet = {
            uuid: crypto.randomUUID(),
            emis_a: new Date().toISOString(),
            // `rang` n'est pas envoyé : il ne sert qu'à conserver l'ordre des
            // gestes après un rechargement, IndexedDB ne garantissant pas
            // l'ordre d'insertion à la relecture.
            rang: Date.now() * 1000 + (rangSuivant++ % 1000),
            ...evenement,
        };

        await idb.ecrire(MAGASINS.file, complet);
        memoire.file.push(complet);

        return complet;
    },

    async retirer(uuids) {
        for (const uuid of uuids) {
            await idb.supprimer(MAGASINS.file, uuid);
        }

        memoire.file = memoire.file.filter((e) => !uuids.includes(e.uuid));
    },

    /**
     * Complète la charge d'un événement ENCORE EN FILE.
     *
     * Sert à un seul cas : une séquence est ouverte, on écrit la trace tout de
     * suite — c'est le principe même de l'observé — mais sa durée réelle n'est
     * connue qu'à la fermeture. Si l'événement est déjà parti, on ne le
     * rattrape pas : la durée reste nulle côté serveur, ce que le schéma
     * autorise explicitement. Un événement envoyé n'est jamais réécrit.
     */
    async completer(uuid, champs) {
        const cible = memoire.file.find((e) => e.uuid === uuid);

        if (!cible) return false;

        cible.charge = { ...cible.charge, ...champs };
        await idb.ecrire(MAGASINS.file, cible);

        return true;
    },

    /**
     * Pointage d'un parent.
     *
     * Le facilitateur fait défiler les états d'un appui : présent, puis
     * absent, puis binôme. Écrire un événement à chaque appui remplirait la
     * file de décisions intermédiaires qui n'ont jamais existé. On remplace
     * donc l'événement TANT QU'IL N'EST PAS PARTI : la file contient des
     * intentions encore locales, seul ce qui a quitté l'appareil est immuable.
     *
     * Si l'événement est déjà envoyé, on en ajoute un nouveau — c'est alors
     * une vraie correction, et le serveur en garde les deux au journal.
     */
    async majPresence(seanceUuid, codeParent, statut) {
        const enAttente = memoire.file.find(
            (e) => e.type === 'presence'
                && e.seance_uuid === seanceUuid
                && e.charge.code_parent === codeParent,
        );

        if (enAttente) {
            enAttente.charge.statut = statut;
            enAttente.emis_a = new Date().toISOString();

            await idb.ecrire(MAGASINS.file, enAttente);

            return enAttente;
        }

        return this.ajouter({
            type: 'presence',
            seance_uuid: seanceUuid,
            charge: { code_parent: codeParent, statut },
        });
    },

    /** Les statuts déjà posés pour une séance, relus depuis la file. */
    presencesDe(seanceUuid) {
        return Object.fromEntries(
            memoire.file
                .filter((e) => e.type === 'presence' && e.seance_uuid === seanceUuid)
                .map((e) => [e.charge.code_parent, e.charge.statut]),
        );
    },

    /**
     * Ce que le compteur permanent affiche : le nombre de SÉANCES qui
     * attendent, pas le nombre d'événements. « 1 séance non synchronisée » se
     * comprend ; « 47 événements » ne veut rien dire pour un facilitateur.
     */
    seancesEnAttente() {
        return new Set(memoire.file.map((e) => e.seance_uuid ?? e.uuid)).size;
    },
};
