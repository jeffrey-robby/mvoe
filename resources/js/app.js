import Alpine from 'alpinejs';
import { accueil, compteurSync, connexion, fidelite, pointage, seance } from './kit.js';
import { ouvrirMagasin } from './magasin.js';
import {
    accueilParent, annuaireParent, assistantParent, ecouterParent,
    entreeParent, feuilletonParent, questionsSemaine,
} from './parent.js';
import { connexionDelegation, parametres, rapport, registre } from './superviseur.js';
import { demarrerSynchronisation } from './synchronisation.js';

/*
| Démarrage du kit.
|
| L'ordre compte : le magasin local doit être chargé AVANT qu'Alpine ne monte
| le moindre écran. Sans cela, les composants liraient un magasin vide et
| afficheraient un kit inexistant, avant de se corriger sous les yeux du
| facilitateur.
*/
window.Alpine = Alpine;

Alpine.data('compteurSync', compteurSync);
Alpine.data('connexion', connexion);
Alpine.data('accueil', accueil);
Alpine.data('seance', seance);
Alpine.data('pointage', pointage);
Alpine.data('fidelite', fidelite);

Alpine.data('connexionDelegation', connexionDelegation);
Alpine.data('registre', registre);
Alpine.data('rapport', rapport);
Alpine.data('parametres', parametres);

Alpine.data('entreeParent', entreeParent);
Alpine.data('accueilParent', accueilParent);
Alpine.data('ecouterParent', ecouterParent);
Alpine.data('feuilletonParent', feuilletonParent);
Alpine.data('annuaireParent', annuaireParent);
Alpine.data('questionsSemaine', questionsSemaine);
Alpine.data('assistantParent', assistantParent);

(async () => {
    await ouvrirMagasin();

    Alpine.start();

    // La remontée part seule dès que le réseau est là. Le facilitateur n'a
    // jamais à la déclencher, ni même à savoir qu'elle existe.
    demarrerSynchronisation();

    // Le service worker rend l'application utilisable sans réseau. Son échec
    // n'empêche rien : on reste simplement dépendant du réseau.
    if ('serviceWorker' in navigator) {
        navigator.serviceWorker.register('/sw.js').catch(() => {});
    }
})();
