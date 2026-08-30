import { readFileSync, writeFileSync, existsSync } from 'node:fs';
import { createHash } from 'node:crypto';

/*
| Génère `public/sw.js` à partir de `resources/sw/modele.js`, en y injectant la
| liste exacte des fichiers produits par Vite.
|
| Écrire cette liste à la main la rendrait périmée au premier build : le kit se
| retrouverait sans style ni script en mode avion, c'est-à-dire précisément là
| où personne ne peut le réparer.
|
| Lancé automatiquement après `vite build` (voir package.json).
*/

const MANIFESTE = 'public/build/manifest.json';
const MANIFESTE_POLICES = 'public/build/fonts-manifest.json';

if (!existsSync(MANIFESTE)) {
    console.error('[mvoe] manifeste Vite introuvable — lancez `vite build` avant.');
    process.exit(1);
}

const ressources = new Set([
    '/manifest.webmanifest',
    '/icones/mvoe-192.png',
    '/icones/mvoe-512.png',
]);

// Les entrées et leurs dépendances : CSS, JS, polices importées.
const manifeste = JSON.parse(readFileSync(MANIFESTE, 'utf8'));

for (const entree of Object.values(manifeste)) {
    if (entree.file) ressources.add(`/build/${entree.file}`);

    for (const css of entree.css ?? []) ressources.add(`/build/${css}`);
    for (const actif of entree.assets ?? []) ressources.add(`/build/${actif}`);
}

// Les fichiers de polices rapatriés par laravel-vite-plugin. Sans eux,
// l'application se rabat sur une police système et l'écran change d'allure
// au moment où l'on passe hors ligne.
if (existsSync(MANIFESTE_POLICES)) {
    const polices = JSON.parse(readFileSync(MANIFESTE_POLICES, 'utf8'));

    for (const valeur of Object.values(polices).flat()) {
        if (typeof valeur === 'string' && valeur.startsWith('/build/')) {
            ressources.add(valeur);
        }
    }
}

const liste = [...ressources].sort();

// La version dérive du contenu : deux builds identiques produisent le même
// service worker, et le cache n'est purgé que lorsque quelque chose a changé.
//
// Le MODÈLE en fait partie, pas seulement la liste des fichiers. Sans lui,
// ajouter une page à précacher ne changeait pas la version : les appareils
// déjà installés gardaient l'ancien cache et n'auraient jamais connu la
// nouvelle page — c'est-à-dire un écran blanc en mode avion, sur le terrain.
const modele = readFileSync('resources/sw/modele.js', 'utf8');

const version = createHash('sha256')
    .update(liste.join('|'))
    .update(modele)
    .digest('hex')
    .slice(0, 12);

const sortie = modele
    .replace('__VERSION__', version)
    .replace('__RESSOURCES__', JSON.stringify(liste, null, 4));

writeFileSync('public/sw.js', sortie);

console.log(`[mvoe] sw.js généré — version ${version}, ${liste.length} ressources précachées.`);
