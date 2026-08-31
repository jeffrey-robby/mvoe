import { connexion } from './kit.js';
import { ouvrirMagasin } from './magasin.js';

/*
| Connexion du facilitateur, sur la coquille du template.
|
| Cet écran partage la mise en page de la connexion administrative, donc
| l'Alpine du template. On n'en démarre pas un second : on enregistre notre
| composant au moment où le sien s'initialise.
|
| Le magasin local s'ouvre en parallèle. Il n'est pas nécessaire pour afficher
| le formulaire, mais il l'est à la seconde où la session s'ouvre : le jeton
| doit être écrit sur l'appareil, sinon le kit redemanderait les identifiants
| au premier écran.
*/
document.addEventListener('alpine:init', () => {
    Alpine.data('connexion', connexion);
});

ouvrirMagasin();

// Le service worker s'installe dès la connexion : c'est le seul moment où l'on
// est sûr d'avoir du réseau, et l'appareil part ensuite en zone sans couverture.
if ('serviceWorker' in navigator) {
    navigator.serviceWorker.register('/sw.js').catch(() => {});
}
