/*
|------------------------------------------------------------------------------
| IndexedDB — enveloppe minimale
|------------------------------------------------------------------------------
|
| Une centaine de lignes plutôt qu'une dépendance : le kit doit rester léger et
| démarrer hors ligne, et une bibliothèque de plus n'apporterait rien ici.
|
| Deux magasins :
|
|   `etat`  clé → valeur. Le paquet de cohorte, la session, la séance en cours,
|           les libellés locaux.
|   `file`  un enregistrement par événement, indexé par son UUID. Ajouter et
|           retirer un événement précis doit rester peu coûteux, même avec
|           plusieurs séances en attente.
|
*/

const NOM = 'mvoe';
const VERSION = 1;

export const MAGASINS = { etat: 'etat', file: 'file' };

let connexion = null;

function ouvrirBase() {
    if (connexion) return Promise.resolve(connexion);

    return new Promise((resoudre, rejeter) => {
        const requete = indexedDB.open(NOM, VERSION);

        requete.onupgradeneeded = () => {
            const base = requete.result;

            if (!base.objectStoreNames.contains(MAGASINS.etat)) {
                base.createObjectStore(MAGASINS.etat);
            }

            if (!base.objectStoreNames.contains(MAGASINS.file)) {
                base.createObjectStore(MAGASINS.file, { keyPath: 'uuid' });
            }
        };

        requete.onsuccess = () => {
            connexion = requete.result;
            resoudre(connexion);
        };

        requete.onerror = () => rejeter(requete.error);
    });
}

function transaction(magasin, mode, action) {
    return ouvrirBase().then(
        (base) =>
            new Promise((resoudre, rejeter) => {
                const t = base.transaction(magasin, mode);
                const requete = action(t.objectStore(magasin));

                // On résout sur `oncomplete`, pas sur `onsuccess` : une écriture
                // n'est acquise que lorsque la transaction est validée. C'est
                // toute la différence entre « enregistré » et « en cours ».
                t.oncomplete = () => resoudre(requete?.result ?? null);
                t.onerror = () => rejeter(t.error);
                t.onabort = () => rejeter(t.error);
            }),
    );
}

/**
 * IndexedDB copie les valeurs par clonage structuré, qui REFUSE les proxies
 * réactifs d'Alpine. Une donnée venue directement d'un composant ferait donc
 * échouer l'écriture — autrement dit perdre le geste du facilitateur, en
 * silence et hors ligne, là où il n'a aucun moyen de s'en apercevoir.
 *
 * On aplatit systématiquement avant d'écrire. Le coût est négligeable, l'oubli
 * ne l'est pas.
 */
function aplatir(valeur) {
    return valeur === undefined || valeur === null ? valeur : JSON.parse(JSON.stringify(valeur));
}

export const idb = {
    lire: (magasin, cle) => transaction(magasin, 'readonly', (s) => s.get(cle)),

    tout: (magasin) => transaction(magasin, 'readonly', (s) => s.getAll()),

    ecrire: (magasin, valeur, cle) =>
        transaction(magasin, 'readwrite', (s) =>
            cle === undefined ? s.put(aplatir(valeur)) : s.put(aplatir(valeur), cle)),

    supprimer: (magasin, cle) => transaction(magasin, 'readwrite', (s) => s.delete(cle)),

    vider: (magasin) => transaction(magasin, 'readwrite', (s) => s.clear()),

    /** Disponible ? Un navigateur en navigation privée peut refuser. */
    async utilisable() {
        try {
            await ouvrirBase();
            return true;
        } catch {
            return false;
        }
    },
};
