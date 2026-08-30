/*
|------------------------------------------------------------------------------
| Service worker Mvoé
|------------------------------------------------------------------------------
|
| CE FICHIER EST GÉNÉRÉ. Ne le modifiez pas dans `public/` : la source est
| `resources/sw/modele.js`, et `scripts/generer-sw.mjs` y injecte la liste
| exacte des fichiers produits par Vite. Une liste écrite à la main serait
| périmée au premier build, et le kit se retrouverait sans style ni script en
| mode avion — exactement là où personne ne peut le réparer.
|
| Trois caches, trois durées de vie :
|
|   coquille : les pages, le CSS, le JS, les polices. Remplacé à chaque version.
|   audios   : les enregistrements du paquet de cohorte. Survit aux versions,
|              parce qu'il pèse lourd et que le facilitateur l'a téléchargé une
|              fois, en réseau, peut-être loin de chez lui.
|   pages    : les coquilles HTML rafraîchies au passage.
|
| L'API n'est JAMAIS mise en cache. Une réponse d'API périmée serait pire que
| pas de réponse du tout : le kit sait travailler hors ligne, il n'a pas besoin
| qu'on lui mente.
|
*/

const VERSION = '__VERSION__';

const CACHES = {
    coquille: `mvoe-coquille-${VERSION}`,
    pages: `mvoe-pages-${VERSION}`,
    audios: 'mvoe-audio-v1',
};

/** Injecté au build depuis le manifeste de Vite. */
const RESSOURCES = __RESSOURCES__;

/** Les écrans du kit. Ce sont des coquilles vides : aucune donnée dedans. */
/*
| Les coquilles du kit, précachées à l'installation.
|
| « /kit/tableau-de-bord » en fait partie bien qu'il demande du réseau : sans sa
| coquille, le facilitateur hors ligne tombe sur la page d'erreur du navigateur.
| Avec elle, l'écran lui dit que ces chiffres viennent du serveur. Une phrase
| vaut mieux qu'un dinosaure.
*/
const PAGES = ['/kit', '/kit/connexion', '/kit/seance', '/kit/pointage', '/kit/fidelite',
    '/kit/inscrire', '/kit/tableau-de-bord',
    '/kit/activite', '/kit/visite', '/kit/signaler', '/kit/formation'];

self.addEventListener('install', (evenement) => {
    evenement.waitUntil(
        (async () => {
            const coquille = await caches.open(CACHES.coquille);

            // Un par un : un fichier manquant ne doit pas faire échouer toute
            // l'installation et laisser le kit sans cache du tout.
            await Promise.all(
                RESSOURCES.map((url) => coquille.add(url).catch(() => null)),
            );

            const pages = await caches.open(CACHES.pages);
            await Promise.all(PAGES.map((url) => pages.add(url).catch(() => null)));

            // Le nouveau service worker prend la main sans attendre la
            // fermeture de tous les onglets : une correction doit pouvoir
            // arriver le jour même.
            await self.skipWaiting();
        })(),
    );
});

self.addEventListener('activate', (evenement) => {
    evenement.waitUntil(
        (async () => {
            const gardes = Object.values(CACHES);

            await Promise.all(
                (await caches.keys())
                    .filter((nom) => nom.startsWith('mvoe-') && !gardes.includes(nom))
                    .map((nom) => caches.delete(nom)),
            );

            await self.clients.claim();
        })(),
    );
});

self.addEventListener('fetch', (evenement) => {
    const requete = evenement.request;

    if (requete.method !== 'GET') return;

    const url = new URL(requete.url);

    if (url.origin !== self.location.origin) return;

    // L'API passe toujours par le réseau. Hors ligne, elle échoue — et le kit
    // sait quoi en faire, sans jamais afficher d'erreur au facilitateur.
    if (url.pathname.startsWith('/api/')) return;

    // Les ecrans de la delegation passent par le reseau : le superviseur
    // travaille assis et en ligne, et un rapport servi depuis un cache serait
    // un rapport perime -- exactement ce qu'un document ne doit pas etre.
    if (url.pathname.startsWith('/superviseur')) return;

    if (requete.mode === 'navigate') {
        evenement.respondWith(servirUnePage(requete, url));
        return;
    }

    if (url.pathname.startsWith('/audio/')) {
        evenement.respondWith(servirDepuis(CACHES.audios, requete));
        return;
    }

    evenement.respondWith(servirDepuis(CACHES.coquille, requete));
});

/**
 * Les pages sont servies depuis le cache d'abord : en séance, l'ouverture d'un
 * écran ne doit jamais attendre un réseau qui n'est pas là. La version fraîche
 * est récupérée en arrière-plan pour la prochaine fois.
 *
 * La recherche ignore la chaîne de requête : `/kit/seance?module=8` et
 * `/kit/seance` sont la même coquille.
 */
async function servirUnePage(requete, url) {
    const cache = await caches.open(CACHES.pages);
    const enCache = await cache.match(url.pathname);

    const reseau = fetch(requete)
        .then((reponse) => {
            if (reponse.ok) cache.put(url.pathname, reponse.clone());
            return reponse;
        })
        .catch(() => null);

    if (enCache) {
        // On ne bloque pas sur le rafraîchissement.
        reseau.catch(() => null);
        return enCache;
    }

    // Jamais vue et pas de réseau : on sert l'accueil du kit plutôt qu'une
    // page d'erreur de navigateur.
    return (await reseau) ?? (await cache.match('/kit')) ?? Response.error();
}

async function servirDepuis(nomDuCache, requete) {
    const cache = await caches.open(nomDuCache);
    const enCache = await cache.match(requete);

    if (enCache) return enCache;

    try {
        const reponse = await fetch(requete);

        // Seul un 200 complet est digne d'être gardé. Un 204 vide — ce que
        // renvoient volontiers les portails captifs — remplacerait un fichier
        // par du silence, définitivement.
        if (reponse.status === 200) cache.put(requete, reponse.clone());

        return reponse;
    } catch {
        return Response.error();
    }
}
