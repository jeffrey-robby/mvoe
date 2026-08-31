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

const VERSION = '8a6aa67ccc44';

const CACHES = {
    coquille: `mvoe-coquille-${VERSION}`,
    pages: `mvoe-pages-${VERSION}`,
    audios: 'mvoe-audio-v1',
};

/** Injecté au build depuis le manifeste de Vite. */
const RESSOURCES = [
    "/build/assets/admin.3b61ccfe.js",
    "/build/assets/api.bc7d4a2d.js",
    "/build/assets/app.7e928a11.css",
    "/build/assets/app.7eef1047.js",
    "/build/assets/archivo-latin-600-normal.d8536b90.woff",
    "/build/assets/archivo-latin-600-normal.d9e8c29f.woff2",
    "/build/assets/archivo-latin-700-normal.7eb23fce.woff",
    "/build/assets/archivo-latin-700-normal.abada6cd.woff2",
    "/build/assets/archivo-latin-ext-600-normal.cd902f80.woff2",
    "/build/assets/archivo-latin-ext-600-normal.d8831f61.woff",
    "/build/assets/archivo-latin-ext-700-normal.86756247.woff",
    "/build/assets/archivo-latin-ext-700-normal.8affe3b4.woff2",
    "/build/assets/archivo-vietnamese-600-normal.082f80ae.woff2",
    "/build/assets/archivo-vietnamese-600-normal.8f89cdd5.woff",
    "/build/assets/archivo-vietnamese-700-normal.1e20a5b9.woff",
    "/build/assets/archivo-vietnamese-700-normal.bfa65d40.woff2",
    "/build/assets/easymde.min.86d3f618.css",
    "/build/assets/fancybox.404514f5.css",
    "/build/assets/flatpickr.min.7b14c7ca.css",
    "/build/assets/font-awesome.min.78437013.css",
    "/build/assets/fullcalendar.min.97e9cb23.css",
    "/build/assets/highlight.min.46cd66a0.css",
    "/build/assets/ibm-plex-mono-cyrillic-400-normal.42da81a2.woff",
    "/build/assets/ibm-plex-mono-cyrillic-400-normal.7635422b.woff2",
    "/build/assets/ibm-plex-mono-cyrillic-500-normal.1d89462e.woff2",
    "/build/assets/ibm-plex-mono-cyrillic-500-normal.55a3af5e.woff",
    "/build/assets/ibm-plex-mono-cyrillic-ext-400-normal.0e4a2393.woff",
    "/build/assets/ibm-plex-mono-cyrillic-ext-400-normal.f8c22ec1.woff2",
    "/build/assets/ibm-plex-mono-cyrillic-ext-500-normal.3febe5c7.woff2",
    "/build/assets/ibm-plex-mono-cyrillic-ext-500-normal.eadb2c42.woff",
    "/build/assets/ibm-plex-mono-latin-400-normal.08949f72.woff2",
    "/build/assets/ibm-plex-mono-latin-400-normal.328d50b6.woff",
    "/build/assets/ibm-plex-mono-latin-500-normal.01d28544.woff2",
    "/build/assets/ibm-plex-mono-latin-500-normal.09f018c5.woff",
    "/build/assets/ibm-plex-mono-latin-ext-400-normal.6bc0f226.woff2",
    "/build/assets/ibm-plex-mono-latin-ext-400-normal.f8ddaa0a.woff",
    "/build/assets/ibm-plex-mono-latin-ext-500-normal.6bb06407.woff2",
    "/build/assets/ibm-plex-mono-latin-ext-500-normal.ff5da89c.woff",
    "/build/assets/ibm-plex-mono-vietnamese-400-normal.62632b63.woff2",
    "/build/assets/ibm-plex-mono-vietnamese-400-normal.bced561d.woff",
    "/build/assets/ibm-plex-mono-vietnamese-500-normal.755f62b4.woff",
    "/build/assets/ibm-plex-mono-vietnamese-500-normal.781633d9.woff2",
    "/build/assets/ibm-plex-sans-cyrillic-400-normal.69afad88.woff2",
    "/build/assets/ibm-plex-sans-cyrillic-400-normal.89b819d4.woff",
    "/build/assets/ibm-plex-sans-cyrillic-500-normal.200be3a2.woff2",
    "/build/assets/ibm-plex-sans-cyrillic-500-normal.b9d946b2.woff",
    "/build/assets/ibm-plex-sans-cyrillic-600-normal.730becc6.woff2",
    "/build/assets/ibm-plex-sans-cyrillic-600-normal.dc111b42.woff",
    "/build/assets/ibm-plex-sans-cyrillic-ext-400-normal.e2580ad7.woff2",
    "/build/assets/ibm-plex-sans-cyrillic-ext-400-normal.e60c70b3.woff",
    "/build/assets/ibm-plex-sans-cyrillic-ext-500-normal.08578d90.woff2",
    "/build/assets/ibm-plex-sans-cyrillic-ext-500-normal.19928f2f.woff",
    "/build/assets/ibm-plex-sans-cyrillic-ext-600-normal.d3fc6b34.woff",
    "/build/assets/ibm-plex-sans-cyrillic-ext-600-normal.d60d3d0b.woff2",
    "/build/assets/ibm-plex-sans-greek-400-normal.02296067.woff2",
    "/build/assets/ibm-plex-sans-greek-400-normal.8d899d62.woff",
    "/build/assets/ibm-plex-sans-greek-500-normal.38489276.woff",
    "/build/assets/ibm-plex-sans-greek-500-normal.8cb6e20b.woff2",
    "/build/assets/ibm-plex-sans-greek-600-normal.0a9ebd90.woff2",
    "/build/assets/ibm-plex-sans-greek-600-normal.b080df76.woff",
    "/build/assets/ibm-plex-sans-latin-400-normal.3b646991.woff2",
    "/build/assets/ibm-plex-sans-latin-400-normal.828907bf.woff",
    "/build/assets/ibm-plex-sans-latin-500-normal.0717336f.woff2",
    "/build/assets/ibm-plex-sans-latin-500-normal.8d2c7b2e.woff",
    "/build/assets/ibm-plex-sans-latin-600-normal.7861a349.woff",
    "/build/assets/ibm-plex-sans-latin-600-normal.8960851d.woff2",
    "/build/assets/ibm-plex-sans-latin-ext-400-normal.85db75b8.woff",
    "/build/assets/ibm-plex-sans-latin-ext-400-normal.c93d2a12.woff2",
    "/build/assets/ibm-plex-sans-latin-ext-500-normal.2846035d.woff2",
    "/build/assets/ibm-plex-sans-latin-ext-500-normal.6aa470eb.woff",
    "/build/assets/ibm-plex-sans-latin-ext-600-normal.b25dfd4f.woff2",
    "/build/assets/ibm-plex-sans-latin-ext-600-normal.ba265b26.woff",
    "/build/assets/ibm-plex-sans-vietnamese-400-normal.771538f7.woff2",
    "/build/assets/ibm-plex-sans-vietnamese-400-normal.aa3bd4c4.woff",
    "/build/assets/ibm-plex-sans-vietnamese-500-normal.156142ee.woff",
    "/build/assets/ibm-plex-sans-vietnamese-500-normal.85ba3d9c.woff2",
    "/build/assets/ibm-plex-sans-vietnamese-600-normal.5d34b803.woff2",
    "/build/assets/ibm-plex-sans-vietnamese-600-normal.ae433d3b.woff",
    "/build/assets/kit-auth.aa089a75.js",
    "/build/assets/kit.1503311f.js",
    "/build/assets/kit.d5cf3765.css",
    "/build/assets/markdown-editor.854cb2a8.css",
    "/build/assets/nice-select.b1ccd362.css",
    "/build/assets/nice-select2.8faa6a66.css",
    "/build/assets/nouislider.min.3771034d.css",
    "/build/assets/quill.snow.ee0035a9.css",
    "/build/assets/superviseur.92563b3d.js",
    "/build/assets/swiper-bundle.min.3f0abbd1.css",
    "/build/assets/tippy.79ba074f.css",
    "/icones/mvoe-192.png",
    "/icones/mvoe-512.png",
    "/manifest.webmanifest"
];

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
