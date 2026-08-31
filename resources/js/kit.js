import { api, ErreurHorsLigne, messageDeConnexion } from './api.js';
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
        total: demo ?? file.totalEnAttente(),
        resume: demo === null ? file.resumeEnAttente() : [],

        init() {
            if (this.demo !== null) return;

            // Le compteur ne disparaît jamais et n'est jamais recalculé à la
            // main : il se met à jour dès que la file bouge, d'où qu'elle bouge.
            window.addEventListener('file-modifiee', () => {
                this.total = file.totalEnAttente();
                this.resume = file.resumeEnAttente();
            });
        },

        /**
         * Le libellé nomme ce qui attend réellement : « 1 séance · 2 activités
         * non synchronisées ». Dire « séance » pour une causerie enverrait le
         * facilitateur chercher une séance qui n'existe pas.
         */
        get libelle() {
            if (this.resume.length === 0) return 'Tout est synchronisé';

            const morceaux = this.resume.map(
                (c) => `${c.n} ${c.n === 1 ? c.un : c.plusieurs}`,
            );

            return `${morceaux.join(' · ')} à envoyer`;
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
                this.erreur = messageDeConnexion(e, {
                    horsLigne: "Vous êtes hors ligne. La première connexion demande du réseau, une seule fois.",
                    refus: 'Ces identifiants ne correspondent pas.',
                });
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

        // Un facilitateur anime souvent plusieurs cohortes. Le kit n'en garde
        // qu'UNE hors ligne — celle de la séance du jour — mais c'est à lui de
        // dire laquelle, pas au code de prendre la première venue.
        cohortes: [],
        choix: false,

        init() {
            if (!session.estOuverte()) {
                window.location.href = '/kit/connexion';

                return;
            }

            if (!this.paquetPresent) this.listerLesCohortes();
        },

        async listerLesCohortes() {
            try {
                this.cohortes = (await api.cohortes()).cohortes;
            } catch {
                // Hors ligne sans paquet, il n'y a rien à faire ici : l'écran
                // le dit déjà, inutile d'ajouter une erreur par-dessus.
                this.cohortes = [];
            }
        },

        /** Le nombre de choses en attente d'envoi, toutes natures confondues. */
        get enAttente() {
            return file.totalEnAttente();
        },

        async ouvrirLeChoix() {
            this.message = null;

            // Changer de paquet remplace la liste des parents en local. Ce qui
            // n'est pas encore parti se rattache à l'ancienne cohorte : on
            // envoie d'abord, on change ensuite.
            if (this.enAttente > 0) {
                this.message = 'Envoyez d’abord ce qui attend avant de changer de cohorte.';

                return;
            }

            this.choix = true;
            await this.listerLesCohortes();
        },

        /**
         * L'effectif est une PROPRIÉTÉ, pas un accesseur.
         *
         * Un accesseur qui lit `paquet.parents()` interroge un magasin que
         * Alpine ne suit pas : après un changement de cohorte, l'écran gardait
         * l'effectif de la précédente. Vingt-et-un parents affichés pour une
         * cohorte qui en compte vingt, et le facilitateur cherche quelqu'un qui
         * n'est pas dans la salle.
         */
        effectif: paquet.parents().length,

        get prochainModule() {
            // Le premier module renseigné du curriculum. Le kit ne prétend pas
            // tenir un calendrier : il propose, le facilitateur décide.
            return this.modules.find((m) => m.renseigne) ?? null;
        },

        async telecharger(cohorteId = null) {
            this.telechargement = true;
            this.message = null;

            try {
                // Une seule cohorte : inutile de faire choisir. Plusieurs :
                // c'est le facilitateur qui désigne celle du jour.
                if (!cohorteId) {
                    if (!this.cohortes.length) await this.listerLesCohortes();

                    if (this.cohortes.length === 0) {
                        this.message = 'Aucune cohorte ne vous est attribuée.';

                        return;
                    }

                    if (this.cohortes.length > 1) {
                        this.choix = true;

                        return;
                    }

                    cohorteId = this.cohortes[0].id;
                }

                // Changer de cohorte, c'est changer de salle : les repères
                // écrits pour les parents de l'ancienne (« Odile, marché ») ne
                // désignent plus personne. On les purge — c'est la fin de cycle
                // dont parle le brief, et le seul moment où elle se produit
                // vraiment sur l'appareil.
                const changeDeCohorte = this.cohorte !== null
                    && this.cohorte.id !== cohorteId;

                const donnees = await api.paquet(cohorteId);

                if (changeDeCohorte) await libelles.purger();

                // Écrit avant d'être affiché, comme tout le reste.
                await paquet.enregistrer(donnees);

                // Les audios rejoignent la Cache API : sans eux, le mode avion
                // afficherait un lecteur muet.
                await this.mettreLesAudiosEnCache(donnees.audios);

                this.paquetPresent = true;
                this.choix = false;
                this.cohorte = donnees.cohorte;
                this.modules = donnees.modules;
                this.effectif = paquet.parents().length;
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

/**
 * Inscription d'un parent, sur le terrain.
 *
 * Le parent ne s'inscrit jamais depuis un écran public : le facilitateur crée
 * le dossier et lui remet son code en main propre. Le parent l'ACTIVE ensuite
 * en se connectant à l'espace parent, s'il a un téléphone. S'il n'en a pas,
 * le dossier existe quand même et il est pointé en séance comme les autres :
 * un dossier, pas forcément un compte.
 *
 * Tout se passe hors ligne. Le code à quatre chiffres est donc tiré ICI :
 * attendre le réseau pour le connaître reviendrait à ne rien pouvoir remettre.
 *
 * AUCUN NOM n'est demandé. Le facilitateur saisit un repère pour se souvenir
 * de qui il s'agit au pointage ; ce repère reste sur son appareil et ne part
 * jamais dans la file.
 */
export function inscrireParent() {
    return {
        cohorte: paquet.cohorte(),
        codeParent: '',
        repere: '',
        langue: 'bulu',
        statut: 'non_renseigne',
        revenu: 'non_renseigne',
        telephonePartage: false,
        resultat: null,
        occupe: false,

        init() {
            if (!session.estOuverte()) {
                window.location.href = '/kit/connexion';

                return;
            }

            this.codeParent = paquet.prochainCodeParent();
        },

        /**
         * L'effectif est une PROPRIÉTÉ, pas un accesseur.
         *
         * Un accesseur qui lit `paquet.parents()` interroge un magasin que
         * Alpine ne suit pas : après un changement de cohorte, l'écran gardait
         * l'effectif de la précédente. Vingt-et-un parents affichés pour une
         * cohorte qui en compte vingt, et le facilitateur cherche quelqu'un qui
         * n'est pas dans la salle.
         */
        effectif: paquet.parents().length,

        /** Le plafond se signale, il ne bloque pas : on n'exclut personne. */
        get depassePlafond() {
            return this.cohorte ? this.effectif >= this.cohorte.ratio_max : false;
        },

        async valider() {
            if (this.occupe || !this.cohorte) return;

            this.occupe = true;

            // Quatre chiffres tirés au hasard de l'appareil. `crypto` plutôt
            // que `Math.random` : c'est un secret, même court.
            const code = String(crypto.getRandomValues(new Uint32Array(1))[0] % 10000)
                .padStart(4, '0');

            const profil = {
                langue: this.langue,
                statut_matrimonial: this.statut,
                revenu_regularite: this.revenu,
                telephone_partage: this.telephonePartage,
            };

            await file.inscrireParent(this.cohorte.id, this.codeParent, code, profil);

            // Le paquet local connaît ce parent tout de suite : il est assis
            // dans la salle, il sera pointé dans dix minutes.
            await paquet.ajouterParent({ code_parent: this.codeParent, ...profil });

            // Le repère du facilitateur, s'il en a saisi un. Il vit sous sa
            // propre clé, hors de la file d'envoi, et ne quitte pas l'appareil.
            if (this.repere.trim() !== '') {
                await libelles.definir(this.codeParent, this.repere.trim());
            }

            fileModifiee();

            this.effectif = paquet.parents().length;
            this.resultat = { code_parent: this.codeParent, code_acces: code };
            this.occupe = false;
        },

        recommencer() {
            this.resultat = null;
            this.repere = '';
            this.telephonePartage = false;
            this.codeParent = paquet.prochainCodeParent();
            this.effectif = paquet.parents().length;
        },
    };
}

/* -------------------------------------------------------------------------- */

/**
 * Le tableau de bord du facilitateur.
 *
 * C'est le MÊME service serveur que celui des délégations, à la cinquième
 * portée : la sienne, c'est-à-dire lui-même. Rien n'est recalculé autrement,
 * et c'est la démonstration que le mécanisme de portée tient sur cinq niveaux.
 *
 * C'est le seul écran du kit qui demande du réseau, et c'est assumé : il
 * regarde en arrière, il ne sert pas en séance. Hors ligne il le DIT, au lieu
 * d'afficher des zéros qu'on prendrait pour la vérité.
 */
export function tableauDeBordFacilitateur() {
    return {
        facilitateur: session.facilitateur(),
        indicateurs: null,
        portee: null,
        chargement: true,
        horsLigne: false,

        async init() {
            if (!session.estOuverte()) {
                window.location.href = '/kit/connexion';

                return;
            }

            try {
                const donnees = await api.tableauDeBordFacilitateur();

                this.indicateurs = donnees.indicateurs;
                this.portee = donnees.portee;
            } catch (e) {
                this.horsLigne = e instanceof ErreurHorsLigne;
            } finally {
                this.chargement = false;
            }
        },

        nombre(valeur) {
            return valeur === null || valeur === undefined
                ? '—'
                : String(valeur).replace('.', ',');
        },
    };
}

/* -------------------------------------------------------------------------- */

/**
 * Enregistrer une activite de terrain.
 *
 * Le programme ne se resume pas aux seances de cohorte. Une causerie sous
 * l'arbre, un porte-a-porte, une sensibilisation au marche comptent autant, et
 * ne pas les enregistrer revient a conclure qu'elles n'ont pas eu lieu.
 *
 * La repartition par sexe et le nombre de participants en situation de
 * handicap sont demandes a chaque fois. C'est ce qui rend le critere
 * « handicap » mesurable plutot que declaratif : personne ne peut ecrire
 * « le programme est inclusif » sans que quelqu'un ait compte.
 */
export function activiteTerrain() {
    return {
        types: paquet.referentiel()?.types_activite ?? [],
        groupes: paquet.groupesSoutien(),
        cohorte: paquet.cohorte(),

        type: 'causerie_educative',
        date: new Date().toISOString().slice(0, 10),
        lieu: '',
        duree: 60,
        touches: 0,
        hommes: 0,
        femmes: 0,
        handicap: 0,
        gspUuid: '',
        commentaire: '',
        enregistre: false,
        occupe: false,

        init() {
            if (!session.estOuverte()) window.location.href = '/kit/connexion';
        },

        get estReunionGsp() {
            return this.type === 'reunion_gsp';
        },

        /** Ce qui n'a pas ete reparti par sexe. On le dit, on ne le comble pas. */
        get nonRenseigne() {
            return Math.max(0, this.touches - this.hommes - this.femmes);
        },

        get incoherent() {
            return this.hommes + this.femmes > this.touches
                || this.handicap > this.touches;
        },

        get peutValider() {
            return this.lieu.trim() !== '' && this.touches > 0 && !this.incoherent;
        },

        async valider() {
            if (!this.peutValider || this.occupe) return;

            this.occupe = true;

            await file.enregistrerActivite({
                type: this.type,
                date: this.date,
                lieu: this.lieu.trim(),
                duree_minutes: Number(this.duree),
                nb_parents_touches: Number(this.touches),
                nb_hommes: Number(this.hommes),
                nb_femmes: Number(this.femmes),
                nb_participants_handicap: Number(this.handicap),
                cohorte_id: this.estReunionGsp ? this.cohorte?.id : null,
                gsp_uuid: this.estReunionGsp && this.gspUuid ? this.gspUuid : null,
                commentaire: this.commentaire.trim() || null,
            });

            fileModifiee();

            this.enregistre = true;
            this.occupe = false;
        },

        recommencer() {
            this.enregistre = false;
            this.lieu = '';
            this.touches = 0;
            this.hommes = 0;
            this.femmes = 0;
            this.handicap = 0;
            this.commentaire = '';
        },
    };
}

/* -------------------------------------------------------------------------- */

/**
 * Enregistrer une visite a domicile.
 *
 * AUCUN nom, aucune adresse precise, aucune position. Une localite, une
 * composition, des difficultes fonctionnelles graduees. C'est la seule facon
 * d'enregistrer ce travail sans constituer un fichier de familles vulnerables
 * — document qui, une fois copie, ne protege plus personne.
 */
export function visiteDomicile() {
    return {
        difficultesPossibles: paquet.referentiel()?.difficultes_fonctionnelles ?? [],
        foyersConnus: paquet.foyers(),

        foyerUuid: '',
        localite: '',
        adultes: 2,
        enfants: 2,
        difficultes: [],
        dejaSuivi: false,
        date: new Date().toISOString().slice(0, 10),
        observations: [],
        suiviPrevu: false,
        enregistre: false,
        occupe: false,

        /** Les observations sont des cases a cocher, jamais un recit libre. */
        observationsPossibles: [
            { valeur: 'espace_de_jeu', libelle: 'Un espace pour jouer' },
            { valeur: 'routine_du_coucher', libelle: 'Une routine du coucher' },
            { valeur: 'repas_partages', libelle: 'Des repas pris ensemble' },
            { valeur: 'enfant_scolarise', libelle: 'Les enfants vont a l\u2019ecole' },
            { valeur: 'tensions_dans_le_foyer', libelle: 'Des tensions dans le foyer' },
            { valeur: 'entourage_present', libelle: 'Un entourage present' },
        ],

        init() {
            if (!session.estOuverte()) window.location.href = '/kit/connexion';
        },

        get nouveauFoyer() {
            return this.foyerUuid === '';
        },

        basculer(liste, valeur) {
            const index = this[liste].indexOf(valeur);

            if (index === -1) this[liste].push(valeur);
            else this[liste].splice(index, 1);
        },

        get peutValider() {
            return !this.nouveauFoyer || this.localite.trim() !== '';
        },

        async valider() {
            if (!this.peutValider || this.occupe) return;

            this.occupe = true;

            const visite = {
                date: this.date,
                observations_structurees: [...this.observations],
                suivi_prevu: this.suiviPrevu,
            };

            if (this.nouveauFoyer) {
                await file.enregistrerVisite({
                    localite: this.localite.trim(),
                    nb_adultes: Number(this.adultes),
                    nb_enfants: Number(this.enfants),
                    difficultes_fonctionnelles_foyer: [...this.difficultes],
                    deja_suivi_programme: this.dejaSuivi,
                }, visite);
            } else {
                await file.enregistrerVisiteSurFoyer(this.foyerUuid, visite);
            }

            fileModifiee();

            this.enregistre = true;
            this.occupe = false;
        },
    };
}

/* -------------------------------------------------------------------------- */

/**
 * Signaler une situation preoccupante, et lire la suite qui y a ete donnee.
 *
 * Le signalement REMONTE : il entre dans la file du superviseur, qui juge et
 * decide. Aucune autorite n'est prevenue automatiquement — une alerte
 * automatique ferait courir un risque a l'enfant qu'elle pretend proteger.
 *
 * Aucune identite n'est demandee. L'ecran ne propose aucun champ ou en mettre
 * une, et le serveur n'a aucune colonne pour l'accueillir.
 */
export function signalerTerrain() {
    return {
        types: paquet.referentiel()?.types_signalement ?? [],
        gravites: paquet.referentiel()?.gravites ?? [],

        type: 'maltraitance',
        gravite: 'moyenne',
        enregistre: false,
        occupe: false,

        // La relecture demande du reseau : elle regarde ce que le superviseur a
        // repondu. Hors ligne, l'ecran le dit.
        miens: [],
        horsLigne: false,
        chargement: true,

        async init() {
            if (!session.estOuverte()) {
                window.location.href = '/kit/connexion';

                return;
            }

            try {
                this.miens = (await api.mesSignalements()).signalements;
            } catch (e) {
                this.horsLigne = e instanceof ErreurHorsLigne;
            } finally {
                this.chargement = false;
            }
        },

        async valider() {
            if (this.occupe) return;

            this.occupe = true;

            await file.enregistrerSignalement({ type: this.type, gravite: this.gravite });

            fileModifiee();

            this.enregistre = true;
            this.occupe = false;
        },

        recommencer() {
            this.enregistre = false;
        },
    };
}

/* -------------------------------------------------------------------------- */

/**
 * Les modules de formation du facilitateur.
 *
 * Un facilitateur forme il y a deux ans ne se refait pas former : il rouvre ses
 * modules. Cet ecran existe pour cela, et il fonctionne HORS LIGNE — on revise
 * dans un car, sur un banc, en attendant que la salle se remplisse. Un
 * catalogue de formation qui exige une connexion ne sert qu'a ceux qui n'en ont
 * pas besoin.
 *
 * La progression part par la file. Le serveur en fait avancer la derniere
 * activite : rouvrir un module, c'est rester actif au registre.
 */
export function formationFacilitateur() {
    return {
        modules: paquet.formation(),
        code: new URLSearchParams(window.location.search).get('module'),
        // Recalculé après chaque lecture : le catalogue dit où il en est.
        avancementDe(m) {
            if (!m.sections.length) return 0;

            return Math.round(((m.sections_vues ?? []).length / m.sections.length) * 100);
        },
        module: null,
        section: 1,
        vues: [],

        chargement: false,
        horsLigne: false,

        async init() {
            if (!session.estOuverte()) {
                window.location.href = '/kit/connexion';

                return;
            }

            // Un facilitateur qui vient d'être enregistré n'a pas encore de
            // cohorte, donc pas de paquet — et c'est précisément lui qui a le
            // plus besoin de ses modules. On les demande alors au serveur : il
            // est encore assis en face de son superviseur, il a du réseau.
            if (this.modules.length === 0) await this.chargerDepuisLeServeur();

            if (this.code) this.ouvrir(this.code);
        },

        async chargerDepuisLeServeur() {
            this.chargement = true;

            try {
                this.modules = (await api.formation()).modules.map((m) => ({
                    ...m,
                    // L'API rend le nombre de sections ; l'écran attend la liste.
                    sections: Array.from({ length: m.sections }, (_, i) => ({ ordre: i + 1 })),
                }));
            } catch (e) {
                this.horsLigne = e instanceof ErreurHorsLigne;
            } finally {
                this.chargement = false;
            }
        },

        async ouvrir(code) {
            this.code = code;
            this.module = paquet.moduleFormation(code);

            // Pas dans le paquet : on le demande, une fois.
            if (!this.module) {
                try {
                    this.module = await api.moduleFormation(code);
                } catch (e) {
                    this.horsLigne = e instanceof ErreurHorsLigne;

                    return;
                }
            }

            // Ce qu'il a déjà lu vient du paquet quand il existe, du module
            // servi par l'API sinon.
            this.vues = [...(paquet.sectionsVues(code).length
                ? paquet.sectionsVues(code)
                : (this.module.sections_vues ?? []))];

            // On reprend où il en était, pas au début. Rouvrir un module terminé
            // ramène à sa première section ; un module commencé reprend à la
            // suivante.
            const suivante = this.module?.sections
                .map((s) => s.ordre)
                .find((o) => !this.vues.includes(o));

            this.section = suivante ?? 1;

            // Ouvrir un module, c'est déjà l'avoir ouvert : la section affichée
            // compte sans qu'il ait à le déclarer.
            this.marquerVue(this.section);
        },

        fermer() {
            this.module = null;
            this.code = null;
        },

        get sectionCourante() {
            return this.module?.sections.find((s) => s.ordre === this.section) ?? null;
        },

        get derniereSection() {
            return this.module ? this.section >= this.module.sections.length : false;
        },

        get avancement() {
            if (!this.module?.sections.length) return 0;

            return Math.round((this.vues.length / this.module.sections.length) * 100);
        },

        async aller(ordre) {
            this.section = ordre;
            await this.marquerVue(ordre);
        },

        async suivante() {
            if (this.derniereSection) return;

            await this.aller(this.section + 1);
        },

        async precedente() {
            if (this.section <= 1) return;

            this.section -= 1;
        },

        /**
         * Une section lue rejoint la file. On envoie la liste complete a chaque
         * fois plutot qu'un increment : le serveur fusionne, et une remontee
         * perdue ne laisse pas de trou dans la progression.
         */
        async marquerVue(ordre) {
            if (this.vues.includes(ordre)) return;

            this.vues.push(ordre);
            this.vues.sort((a, b) => a - b);

            // Retenue en local D'ABORD : la lecture ne doit pas dépendre de
            // l'envoi. À la relecture du paquet, il reprendra où il en était.
            // Sans paquet, l'appel ne fait rien et la file suffit.
            await paquet.marquerSectionVue(this.code, ordre);

            await file.enregistrerProgression(this.code, [...this.vues]);

            fileModifiee();
        },
    };
}

/* -------------------------------------------------------------------------- */

export { libelles };
