import { api, ErreurHorsLigne } from './api.js';
import { file, libelles, paquet, seanceEnCours, session } from './magasin.js';

/*
|------------------------------------------------------------------------------
| Composants Alpine du kit facilitateur
|------------------------------------------------------------------------------
|
| Le kit lit le PAQUET stocké en local, jamais le serveur : après le
| téléchargement initial, aucun écran ne dépend du réseau.
|
| Toute action du facilitateur suit le même ordre, sans exception :
|
|     1. on écrit l'événement dans la file locale ;
|     2. seulement ensuite on met l'écran à jour.
|
| Jamais l'inverse. C'est ce qui garantit qu'un écran affiché correspond bien à
| quelque chose d'enregistré, même si l'application est fermée dans la seconde.
|
*/

/** Événement personnalisé : le compteur permanent s'y raccroche. */
function fileModifiee() {
    window.dispatchEvent(new CustomEvent('file-modifiee'));
}

/* -------------------------------------------------------------------------- */

export function compteurSync(demo = null) {
    return {
        // `demo` n'existe que pour la page du système de design, qui doit
        // pouvoir montrer le compteur chargé sans qu'aucune séance n'attende.
        demo,
        enAttente: demo ?? file.seancesEnAttente(),

        init() {
            if (this.demo !== null) return;

            // Le compteur ne disparaît jamais et n'est jamais recalculé à la
            // main : il se met à jour dès que la file bouge, d'où qu'elle bouge.
            window.addEventListener('file-modifiee', () => {
                this.enAttente = file.seancesEnAttente();
            });
        },

        get libelle() {
            return this.enAttente === 1
                ? '1 séance non synchronisée'
                : `${this.enAttente} séances non synchronisées`;
        },
    };
}

/* -------------------------------------------------------------------------- */

export function connexion() {
    return {
        telephone: '',
        codeAppareil: '',
        email: '',
        motDePasse: '',
        parEmail: false,
        erreur: null,
        occupe: false,

        async valider() {
            this.erreur = null;
            this.occupe = true;

            try {
                const reponse = await api.connexionFacilitateur(
                    this.parEmail
                        ? { email: this.email, password: this.motDePasse }
                        : { telephone: this.telephone, code_appareil: this.codeAppareil },
                );

                await session.ouvrir(reponse.jeton, reponse.facilitateur);
                window.location.href = '/kit';
            } catch (e) {
                this.erreur =
                    e instanceof ErreurHorsLigne
                        ? "Vous êtes hors ligne. La première connexion demande du réseau, une seule fois."
                        : 'Ces identifiants ne correspondent pas.';
            } finally {
                this.occupe = false;
            }
        },
    };
}

/* -------------------------------------------------------------------------- */

export function accueil() {
    return {
        facilitateur: session.facilitateur(),
        paquetPresent: paquet.existe(),
        cohorte: paquet.cohorte(),
        modules: paquet.lire()?.modules ?? [],
        enCours: seanceEnCours.lire(),
        telechargement: false,
        audiosEnCache: 0,
        message: null,

        init() {
            if (!session.estOuverte()) {
                window.location.href = '/kit/connexion';
            }
        },

        get effectif() {
            return paquet.parents().length;
        },

        get prochainModule() {
            // Le premier module renseigné du curriculum. Le kit ne prétend pas
            // tenir un calendrier : il propose, le facilitateur décide.
            return this.modules.find((m) => m.renseigne) ?? null;
        },

        async telecharger() {
            this.telechargement = true;
            this.message = null;

            try {
                const liste = await api.cohortes();
                const premiere = liste.cohortes[0];

                if (!premiere) {
                    this.message = 'Aucune cohorte ne vous est attribuée.';
                    return;
                }

                const donnees = await api.paquet(premiere.id);

                // Écrit avant d'être affiché, comme tout le reste.
                await paquet.enregistrer(donnees);

                // Les audios rejoignent la Cache API : sans eux, le mode avion
                // afficherait un lecteur muet.
                await this.mettreLesAudiosEnCache(donnees.audios);

                this.paquetPresent = true;
                this.cohorte = donnees.cohorte;
                this.modules = donnees.modules;
                this.message = `Paquet téléchargé, ${this.audiosEnCache} audios en cache. Vous pouvez passer hors ligne.`;
            } catch (e) {
                this.message =
                    e instanceof ErreurHorsLigne
                        ? 'Le téléchargement du paquet demande du réseau, une seule fois.'
                        : 'Le téléchargement a échoué.';
            } finally {
                this.telechargement = false;
            }
        },

        /**
         * Les audios du paquet vont dans la Cache API, un par un.
         *
         * Un par un, et non `addAll` : `addAll` échoue en bloc dès qu'un seul
         * fichier manque, et l'on perdrait tout le reste. Un enregistrement qui
         * n'existe pas encore ne doit pas empêcher les onze autres d'être
         * disponibles hors ligne — l'interface sait déjà se passer d'un audio.
         *
         * On n'accepte QUE des réponses 200 avec un corps non vide. Un portail
         * captif d'hôtel, un proxy d'opérateur ou une coupure au mauvais moment
         * renvoient volontiers un 204 vide : le mettre en cache remplacerait
         * l'enregistrement par du silence, définitivement et sans rien dire.
         */
        async mettreLesAudiosEnCache(audios) {
            if (!('caches' in window) || !audios?.length) return;

            const cache = await caches.open('mvoe-audio-v1');
            let deposes = 0;

            for (const url of audios) {
                try {
                    const reponse = await fetch(url, { cache: 'reload' });

                    if (reponse.status !== 200) continue;

                    const corps = await reponse.blob();

                    if (corps.size === 0) continue;

                    await cache.put(url, new Response(corps, {
                        status: 200,
                        headers: { 'Content-Type': reponse.headers.get('content-type') ?? 'audio/wav' },
                    }));

                    deposes++;
                } catch {
                    // Fichier injoignable : on continue, l'interface sait faire.
                }
            }

            this.audiosEnCache = deposes;
        },

        async deconnecter() {
            await session.fermer();
            window.location.href = '/kit/connexion';
        },
    };
}

/* -------------------------------------------------------------------------- */

export function seance() {
    return {
        module: null,
        cohorte: null,

        seanceUuid: null,
        indexCourant: null,
        ouvertureCourante: null,
        debutSequence: null,
        secondes: 0,
        minuteur: null,

        // sequenceId -> 'passee' | 'en_cours' | 'a_venir'
        etats: {},

        // Panneau de contenu (écran « unité digitale »)
        uniteOuverte: null,
        langue: 'fr',
        modalite: 'audio',

        init() {
            if (!session.estOuverte()) {
                window.location.href = '/kit/connexion';
                return;
            }

            const params = new URLSearchParams(window.location.search);
            this.module = paquet.module(params.get('module'));
            this.cohorte = paquet.cohorte();

            if (!this.module) return;

            this.module.sequences.forEach((s) => (this.etats[s.id] = 'a_venir'));

            this.reprendre();
        },

        /**
         * Reprise après une veille du téléphone, un rechargement de page ou un
         * aller-retour par l'écran de pointage. Le facilitateur retrouve sa
         * place exacte dans le déroulé, chronomètre compris : perdre sa place
         * au milieu de quatre-vingt-dix minutes n'est pas acceptable.
         */
        reprendre() {
            const enregistree = seanceEnCours.lire();

            if (!enregistree || enregistree.module_id !== this.module.id) return;

            if (enregistree.terminee) {
                window.location.href = '/kit/fidelite';
                return;
            }

            this.seanceUuid = enregistree.uuid;
            this.etats = { ...this.etats, ...enregistree.etats };
            this.indexCourant = enregistree.index;
            this.ouvertureCourante = enregistree.ouverture_uuid;
            this.debutSequence = enregistree.debut_sequence;

            if (this.indexCourant !== null && this.debutSequence) {
                this.lancerMinuteur();
            }
        },

        persister() {
            return seanceEnCours.mettreAJour({
                etats: this.etats,
                index: this.indexCourant,
                ouverture_uuid: this.ouvertureCourante,
                debut_sequence: this.debutSequence,
            });
        },

        get sequences() {
            return this.module?.sequences ?? [];
        },

        get sequenceCourante() {
            return this.indexCourant === null ? null : this.sequences[this.indexCourant];
        },

        get demarree() {
            return this.seanceUuid !== null;
        },

        /* ---------------------------------------------------------------- */

        async demarrer() {
            const date = new Date().toISOString().slice(0, 10);

            const evenement = await file.ajouter({
                type: 'seance',
                seance_uuid: null,
                charge: {
                    cohorte_id: this.cohorte.id,
                    module_id: this.module.id,
                    date,
                },
            });

            fileModifiee();

            // L'UUID de l'événement d'ouverture EST celui de la séance.
            this.seanceUuid = evenement.uuid;

            await seanceEnCours.ouvrir({
                uuid: this.seanceUuid,
                module_id: this.module.id,
                cohorte_id: this.cohorte.id,
                date,
                etats: this.etats,
                index: null,
                ouverture_uuid: null,
                debut_sequence: null,
            });

            await this.ouvrir(0);
        },

        /**
         * L'OBSERVÉ. La trace est écrite au moment exact où le facilitateur
         * ouvre le bloc — il ne déclare rien, il ne coche rien. C'est cette
         * absence de geste délibéré qui rend la trace opposable à sa
         * déclaration d'après séance.
         */
        async ouvrir(index) {
            if (index < 0 || index >= this.sequences.length) return;

            await this.fermerSequenceCourante();

            const sequence = this.sequences[index];

            const evenement = await file.ajouter({
                type: 'sequence_ouverte',
                seance_uuid: this.seanceUuid,
                charge: {
                    sequence_id: sequence.id,
                    ouverte_a: new Date().toISOString(),
                    // Inconnue tant que la séquence n'est pas fermée. Le schéma
                    // l'autorise : une séance peut remonter séquence ouverte.
                    duree_reelle_secondes: null,
                },
            });

            fileModifiee();

            this.indexCourant = index;
            this.ouvertureCourante = evenement.uuid;
            this.debutSequence = Date.now();
            this.secondes = 0;
            this.etats[sequence.id] = 'en_cours';
            this.uniteOuverte = null;

            await this.persister();
            this.lancerMinuteur();
        },

        async fermerSequenceCourante() {
            if (this.indexCourant === null) return;

            const sequence = this.sequences[this.indexCourant];
            const duree = Math.round((Date.now() - this.debutSequence) / 1000);

            await file.completer(this.ouvertureCourante, { duree_reelle_secondes: duree });
            fileModifiee();

            this.etats[sequence.id] = 'passee';
            this.arreterMinuteur();
        },

        suivante() {
            return this.ouvrir(this.indexCourant + 1);
        },

        get estDerniere() {
            return this.indexCourant === this.sequences.length - 1;
        },

        async pointer() {
            await this.persister();
            window.location.href = '/kit/pointage';
        },

        /**
         * Fin de séance. La fiche de fidélité s'ouvre ensuite — jamais avant.
         */
        async terminer() {
            await this.fermerSequenceCourante();
            this.indexCourant = null;
            await this.persister();
            await seanceEnCours.terminer();

            window.location.href = '/kit/fidelite';
        },

        /* ---------------------------------------------------------------- */

        lancerMinuteur() {
            this.arreterMinuteur();
            this.secondes = Math.round((Date.now() - this.debutSequence) / 1000);
            this.minuteur = setInterval(() => {
                this.secondes = Math.round((Date.now() - this.debutSequence) / 1000);
            }, 1000);
        },

        arreterMinuteur() {
            if (this.minuteur) clearInterval(this.minuteur);
            this.minuteur = null;
        },

        get chrono() {
            const m = Math.floor(this.secondes / 60);
            const s = this.secondes % 60;
            return `${String(m).padStart(2, '0')}:${String(s).padStart(2, '0')}`;
        },

        /** Dépassement de la durée officielle, en secondes. Jamais un reproche. */
        get depassement() {
            const prevue = (this.sequenceCourante?.duree_minutes ?? 0) * 60;
            return this.secondes - prevue;
        },

        /* ---------------------------------------------------------------- */

        ouvrirUnite(unite) {
            this.uniteOuverte = unite;
            this.modalite = 'audio';
        },

        get realisation() {
            if (!this.uniteOuverte) return null;

            const dans = (langue) =>
                this.uniteOuverte.realisations.find(
                    (r) => r.langue === langue && r.modalite === this.modalite,
                );

            // Repli sur le français si la langue demandée n'existe pas encore.
            return dans(this.langue) ?? dans('fr') ?? null;
        },

        get langueServie() {
            return this.realisation?.langue ?? null;
        },

        get versionManquante() {
            return this.realisation !== null && this.langueServie !== this.langue;
        },
    };
}

/* -------------------------------------------------------------------------- */

/**
 * Le pointage.
 *
 * Vingt parents en grille, trois états d'un seul geste. Chaque parent démarre
 * « à pointer » : le kit ne suppose la présence de personne. Un parent qu'on
 * oublie reste visiblement non pointé et n'est jamais remonté — mieux vaut un
 * trou déclaré qu'une présence inventée.
 */
export function pointage() {
    return {
        seance: null,
        parents: [],
        statuts: {},
        libellesLocaux: {},
        modeLibelles: false,

        ordre: ['present', 'absent', 'rattrape_binome'],

        etiquettes: {
            a_pointer: 'À pointer',
            present: 'Présent',
            absent: 'Absent',
            rattrape_binome: 'Binôme',
        },

        init() {
            if (!session.estOuverte()) {
                window.location.href = '/kit/connexion';
                return;
            }

            this.seance = seanceEnCours.lire();

            if (!this.seance) return;

            this.parents = paquet.parents();
            this.libellesLocaux = libelles.tous();

            // Les statuts déjà posés sont relus depuis la file : le pointage
            // survit à un aller-retour vers le déroulé.
            const deja = file.presencesDe(this.seance.uuid);

            this.parents.forEach((p) => {
                this.statuts[p.code_parent] = deja[p.code_parent] ?? 'a_pointer';
            });
        },

        /** Un appui fait défiler les trois états. On ne dé-pointe jamais. */
        async pointer(codeParent) {
            const actuel = this.statuts[codeParent];
            const rang = this.ordre.indexOf(actuel);
            const suivant = this.ordre[(rang + 1) % this.ordre.length];

            // Écrit d'abord…
            await file.majPresence(this.seance.uuid, codeParent, suivant);
            fileModifiee();

            // …affiché ensuite.
            this.statuts[codeParent] = suivant;
        },

        get pointes() {
            return Object.values(this.statuts).filter((s) => s !== 'a_pointer').length;
        },

        get total() {
            return this.parents.length;
        },

        get complet() {
            return this.total > 0 && this.pointes === this.total;
        },

        /*
        | Le libellé local. Le facilitateur écrit ce qu'il veut pour reconnaître
        | ses parents — « Odile, marché ». C'est une donnée nominative : elle
        | reste sur cet appareil, elle n'entre dans aucun événement, et elle est
        | purgée en fin de cycle.
        */
        libelleDe(codeParent) {
            return this.libellesLocaux[codeParent] ?? null;
        },

        async definirLibelle(codeParent, valeur) {
            await libelles.definir(codeParent, valeur);
            this.libellesLocaux = libelles.tous();
        },

        retour() {
            window.location.href = `/kit/seance?module=${this.seance.module_id}`;
        },
    };
}

/* -------------------------------------------------------------------------- */

/**
 * La fiche de fidélité — LE DÉCLARÉ.
 *
 * Elle ne s'ouvre qu'APRÈS la séance, jamais pendant.
 *
 * Point capital : cet écran n'affiche RIEN de ce que l'outil a observé. Ni les
 * séquences ouvertes, ni les durées réelles, ni un quelconque pré-remplissage.
 * Montrer la trace ici reviendrait à souffler sa réponse au facilitateur : les
 * deux sources cesseraient d'être indépendantes, et l'écart déclaré/observé —
 * la mesure qui fait tout l'intérêt du système — ne mesurerait plus rien.
 */
export function fidelite() {
    return {
        seance: null,
        module: null,
        reponses: {},
        bilan: '',
        enregistre: false,

        init() {
            if (!session.estOuverte()) {
                window.location.href = '/kit/connexion';
                return;
            }

            this.seance = seanceEnCours.lire();

            if (!this.seance) return;

            this.module = paquet.module(this.seance.module_id);

            this.module?.sequences.forEach((s) => {
                this.reponses[s.id] = { realisee: null, note: null, commentaire: '' };
            });
        },

        /** Tant que la séance n'est pas terminée, la fiche reste fermée. */
        get accessible() {
            return this.seance?.terminee === true;
        },

        get sequences() {
            return this.module?.sequences ?? [];
        },

        get repondues() {
            return Object.values(this.reponses).filter((r) => r.realisee !== null).length;
        },

        repondre(sequenceId, realisee) {
            this.reponses[sequenceId].realisee = realisee;

            // Une séquence non réalisée n'a pas de note de qualité à donner.
            if (realisee === false) {
                this.reponses[sequenceId].note = null;
            }
        },

        noter(sequenceId, note) {
            this.reponses[sequenceId].note = note;
        },

        async valider() {
            for (const [sequenceId, reponse] of Object.entries(this.reponses)) {
                if (reponse.realisee === null) continue;

                await file.ajouter({
                    type: 'fiche_fidelite',
                    seance_uuid: this.seance.uuid,
                    charge: {
                        sequence_id: Number(sequenceId),
                        realisee_bool: reponse.realisee,
                        note_qualite: reponse.note,
                        commentaire: reponse.commentaire || null,
                    },
                });
            }

            if (this.bilan.trim() !== '') {
                await file.ajouter({
                    type: 'bilan_seance',
                    seance_uuid: this.seance.uuid,
                    charge: { commentaire: this.bilan.trim() },
                });
            }

            fileModifiee();

            // La séance est close côté écran. Ses événements, eux, restent en
            // file jusqu'à ce que le serveur les ait acceptés.
            await seanceEnCours.oublier();
            this.enregistre = true;

            window.location.href = '/kit';
        },
    };
}

/* -------------------------------------------------------------------------- */

export { libelles };
