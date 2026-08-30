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
    referentiel: () => memoire.paquet?.referentiel ?? null,
    formation: () => memoire.paquet?.formation ?? [],

    moduleFormation(code) {
        return (memoire.paquet?.formation ?? []).find((m) => m.code === code) ?? null;
    },

    /**
     * Les sections déjà lues d'un module.
     *
     * Le paquet apporte ce que le serveur savait ; les lectures faites hors
     * ligne s'y ajoutent au fil de l'eau. Sans cette fusion, un facilitateur
     * rouvrirait un module terminé en croyant n'y avoir jamais touché.
     */
    sectionsVues(code) {
        return this.moduleFormation(code)?.sections_vues ?? [];
    },

    /** Une section lue, retenue localement même si l'envoi attend. */
    async marquerSectionVue(code, ordre) {
        const module = this.moduleFormation(code);

        if (!module || module.sections_vues?.includes(ordre)) return;

        module.sections_vues = [...(module.sections_vues ?? []), ordre].sort((a, b) => a - b);

        await etat(CLES.paquet, memoire.paquet);
    },
    foyers: () => memoire.paquet?.foyers ?? [],
    groupesSoutien: () => memoire.paquet?.groupes_soutien ?? [],

    module(id) {
        return memoire.paquet?.modules.find((m) => m.id === Number(id)) ?? null;
    },

    async enregistrer(donnees) {
        await etat(CLES.paquet, donnees);
        memoire.paquet = donnees;
    },

    /**
     * Ajoute un parent inscrit sur le terrain au paquet local.
     *
     * Sans cela, le facilitateur inscrirait quelqu'un et ne pourrait pas le
     * pointer avant d'avoir retrouvé du réseau — alors que la personne est
     * assise devant lui. Le paquet local est la seule vérité hors ligne : il
     * doit connaître ce parent immédiatement.
     */
    async ajouterParent(parent) {
        if (!memoire.paquet) return;

        const donnees = {
            ...memoire.paquet,
            parents: [...memoire.paquet.parents, parent],
        };

        await etat(CLES.paquet, donnees);
        memoire.paquet = donnees;
    },

    /**
     * Le prochain code libre de la cohorte.
     *
     * Les codes suivent le lieu (« EB2-01 », « EB2-02 ») : on reprend le
     * préfixe des codes existants et on prend le premier numéro non pris. Deux
     * appareils du même facilitateur pourraient tirer le même ; la contrainte
     * d'unicité en base rejette alors le second, qui ressort dans les
     * « rejetés » de la remontée plutôt que de créer un doublon silencieux.
     */
    prochainCodeParent() {
        const codes = this.parents().map((p) => p.code_parent);
        const prefixe = codes[0]?.split('-')[0] ?? 'P';

        const numeros = codes
            .map((c) => Number.parseInt(c.split('-')[1], 10))
            .filter((n) => Number.isInteger(n));

        const suivant = numeros.length ? Math.max(...numeros) + 1 : 1;

        return `${prefixe}-${String(suivant).padStart(2, '0')}`;
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
     * Une activite de terrain.
     *
     * L'arrondissement n'est PAS envoye : le serveur prend celui du compte du
     * facilitateur. Rien de ce qui part d'ici ne doit pouvoir deposer une
     * donnee hors de sa portee.
     */
    async enregistrerActivite(charge) {
        return this.ajouter({ type: 'activite', seance_uuid: null, charge });
    },

    /**
     * Un foyer, puis la visite qui vient de s'y derouler.
     *
     * Les deux partent ensemble : le serveur remet les inscriptions et les
     * foyers en tete de file, la visite retrouve donc toujours son foyer.
     */
    async enregistrerVisite(foyer, visite) {
        const evenementFoyer = await this.ajouter({
            type: 'foyer', seance_uuid: null, charge: foyer,
        });

        await this.ajouter({
            type: 'visite',
            seance_uuid: null,
            charge: { ...visite, foyer_uuid: evenementFoyer.uuid },
        });

        return evenementFoyer;
    },

    /** Une visite sur un foyer deja suivi. */
    async enregistrerVisiteSurFoyer(foyerUuid, visite) {
        return this.ajouter({
            type: 'visite',
            seance_uuid: null,
            charge: { ...visite, foyer_uuid: foyerUuid },
        });
    },

    /**
     * La progression dans un module de formation.
     *
     * ROUVRIR UN MODULE EST UNE ACTIVITE : le serveur en fait avancer la
     * derniere activite du facilitateur, donc il reste actif au registre.
     * C'est le seul dispositif de reactivation qui ne coute ni deplacement,
     * ni per diem, ni convocation.
     */
    async enregistrerProgression(code, sectionsVues) {
        return this.ajouter({
            type: 'progression_formation',
            seance_uuid: null,
            charge: {
                module_code: code,
                sections_vues: sectionsVues,
                ouverte_a: new Date().toISOString(),
            },
        });
    },

    /**
     * Un signalement. Il REMONTE, il ne declenche rien.
     *
     * Aucune identite n'est portee, et aucune autorite n'est prevenue : il
     * entre dans la file du superviseur, qui juge.
     */
    async enregistrerSignalement(charge) {
        return this.ajouter({ type: 'signalement', seance_uuid: null, charge });
    },

    /**
     * Ce que le compteur permanent affiche : le nombre de SÉANCES qui
     * attendent, pas le nombre d'événements. « 1 séance non synchronisée » se
     * comprend ; « 47 événements » ne veut rien dire pour un facilitateur.
     *
     * Seuls les événements RATTACHÉS à une séance comptent ici. Une inscription
     * de parent n'appartient à aucune séance : la compter en ferait afficher
     * « 1 séance non synchronisée » alors qu'aucune séance n'a eu lieu, et le
     * facilitateur chercherait une séance qui n'existe pas.
     */
    seancesEnAttente() {
        const rattaches = memoire.file
            .filter((e) => e.type === 'seance' || e.seance_uuid)
            .map((e) => e.seance_uuid ?? e.uuid);

        return new Set(rattaches).size;
    },

    /**
     * Ce qui attend, nommé par nature.
     *
     * Le compteur doit dire ce qu'il compte. « 1 séance non synchronisée »
     * pour une causerie enverrait le facilitateur chercher une séance qui
     * n'existe pas ; « 3 événements » ne voudrait rien dire pour lui.
     *
     * @returns {Array<{n: number, un: string, plusieurs: string}>}
     */
    resumeEnAttente() {
        const horsSeance = memoire.file.filter((e) => e.type !== 'seance' && !e.seance_uuid);

        const compter = (type) => horsSeance.filter((e) => e.type === type).length;

        const categories = [
            { n: this.seancesEnAttente(), un: 'séance', plusieurs: 'séances' },
            { n: compter('inscription_parent'), un: 'inscription', plusieurs: 'inscriptions' },
            { n: compter('activite'), un: 'activité', plusieurs: 'activités' },
            // Un foyer et sa visite partent ensemble : on compte la visite, qui
            // est le geste, et non le dossier qu'elle a fait naître.
            { n: compter('visite'), un: 'visite', plusieurs: 'visites' },
            { n: compter('groupe_soutien'), un: 'groupe', plusieurs: 'groupes' },
            { n: compter('signalement'), un: 'signalement', plusieurs: 'signalements' },
            { n: compter('progression_formation'), un: 'module lu', plusieurs: 'modules lus' },
        ];

        return categories.filter((c) => c.n > 0);
    },

    /** Le total à envoyer, tous types confondus. */
    totalEnAttente() {
        const foyers = memoire.file.filter((e) => e.type === 'foyer').length;

        // Le foyer voyage avec sa visite : le compter séparément ferait dire
        // « 2 à envoyer » pour une seule visite à domicile.
        return this.seancesEnAttente()
            + memoire.file.filter((e) => e.type !== 'seance' && !e.seance_uuid).length
            - foyers;
    },

    /**
     * Inscription d'un parent, hors ligne.
     *
     * Le code à quatre chiffres est tiré ICI, sur l'appareil : le facilitateur
     * doit pouvoir le remettre en main propre séance tenante, sans attendre
     * d'avoir retrouvé du réseau. Il ne repartira jamais du serveur — celui-ci
     * ne le stocke que haché, et le journal ne le conserve pas du tout.
     */
    async inscrireParent(cohorteId, codeParent, codeAcces, profil) {
        return this.ajouter({
            type: 'inscription_parent',
            seance_uuid: null,
            charge: { cohorte_id: cohorteId, code_parent: codeParent,
                code_acces: codeAcces, ...profil },
        });
    },
};
