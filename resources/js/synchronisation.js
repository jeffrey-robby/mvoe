import { api, ErreurHorsLigne } from './api.js';
import { file, session } from './magasin.js';

/*
|------------------------------------------------------------------------------
| La remontée
|------------------------------------------------------------------------------
|
| Le facilitateur ne déclenche jamais la synchronisation : elle part seule dès
| que le réseau revient. Il n'a pas à savoir ce qu'est une synchronisation, et
| encore moins à y penser en sortant d'une séance.
|
| Deux règles tiennent tout le reste :
|
|   1. AUCUNE ERREUR RÉSEAU N'EST VISIBLE. Le mode avion est le mode de travail
|      prévu, pas une panne. Un échec d'envoi ne produit ni message, ni
|      vibration, ni point rouge : la file reste pleine, le compteur reste haut,
|      et on réessaiera.
|
|   2. Un événement n'est retiré de la file que si le serveur l'a rangé dans
|      `acceptes` OU dans `doublons`. Tout le reste est renvoyé. Un envoi coupé
|      au milieu peut donc être rejoué entier sans rien perdre ni dupliquer.
|
*/

/** Nouvel essai tant qu'il reste quelque chose à envoyer. */
const INTERVALLE_MS = 30_000;

let enCours = false;
let minuteur = null;

function annoncer(etat) {
    window.dispatchEvent(new CustomEvent('sync-etat', { detail: { etat } }));
}

/**
 * `rang` ne sert qu'à ordonner la file localement. Il ne fait pas partie du
 * contrat de l'API et ne quitte pas l'appareil.
 */
function pourEnvoi(evenement) {
    const { rang, ...utile } = evenement;

    return utile;
}

export async function tenter() {
    if (enCours) return 'deja-en-cours';
    if (!navigator.onLine) return 'hors-ligne';
    if (!session.estOuverte()) return 'pas-de-session';
    if (file.estVide()) return 'rien-a-envoyer';

    enCours = true;
    annoncer('envoi');

    // On fige la file au moment de l'envoi : ce qui est ajouté pendant la
    // requête part au tour suivant, et ne risque pas d'être retiré sans avoir
    // été transmis.
    const lot = file.tous().map(pourEnvoi);

    try {
        const bilan = await api.envoyerEvenements(lot);

        // Les doublons comptent comme reçus : le serveur les avait déjà.
        const traites = [...bilan.acceptes, ...bilan.doublons];

        if (traites.length > 0) {
            await file.retirer(traites);
        }

        window.dispatchEvent(new CustomEvent('file-modifiee'));
        annoncer(file.estVide() ? 'a-jour' : 'partiel');

        // Un événement rejeté ne partira jamais : le renvoyer indéfiniment
        // ferait tourner la boucle pour rien. On le laisse en file et on le
        // signale dans la console, pour que le problème soit diagnosticable
        // sans jamais être jeté à la figure du facilitateur.
        if (bilan.rejetes.length > 0) {
            console.warn('[mvoe] événements rejetés par le serveur', bilan.rejetes);
        }

        return 'envoye';
    } catch (e) {
        // Hors ligne ou serveur injoignable : on ne dit rien, on réessaiera.
        annoncer(e instanceof ErreurHorsLigne ? 'hors-ligne' : 'differe');

        return 'differe';
    } finally {
        enCours = false;
        programmer();
    }
}

/** Tant qu'il reste des séances en attente, on repasse régulièrement. */
function programmer() {
    clearTimeout(minuteur);

    if (file.estVide()) return;

    minuteur = setTimeout(tenter, INTERVALLE_MS);
}

export function demarrerSynchronisation() {
    // Le retour du réseau est le seul signal qui compte vraiment.
    window.addEventListener('online', () => tenter());

    // Une nouvelle donnée écrite mérite un essai immédiat si le réseau est là.
    window.addEventListener('file-modifiee', () => {
        if (navigator.onLine && !enCours) {
            clearTimeout(minuteur);
            minuteur = setTimeout(tenter, 1_000);
        }
    });

    tenter();
}
