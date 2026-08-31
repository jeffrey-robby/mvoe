import {
    bibliotheque,
    campagnes,
    canaux,
    connexionDelegation,
    enregistrerFacilitateur,
    enteteDelegation,
    parametres,
    rapport,
    registre,
    signalements,
    tableauDeBord,
} from './superviseur.js';

/*
| Le template embarque son propre Alpine (`/assets/js/alpine.min.js`) et
| l'initialise lui-même. On ne démarre donc pas un second Alpine : on se
| contente d'enregistrer nos composants au moment où le sien s'initialise.
*/
document.addEventListener('alpine:init', () => {
    Alpine.data('connexionDelegation', connexionDelegation);
    Alpine.data('enteteDelegation', enteteDelegation);
    Alpine.data('tableauDeBord', tableauDeBord);
    Alpine.data('registre', registre);
    Alpine.data('enregistrerFacilitateur', enregistrerFacilitateur);
    Alpine.data('signalements', signalements);
    Alpine.data('bibliotheque', bibliotheque);
    Alpine.data('campagnes', campagnes);
    Alpine.data('canaux', canaux);
    Alpine.data('rapport', rapport);
    Alpine.data('parametres', parametres);
});
