import { session } from './magasin.js';

/*
| La coquille du template, réimplémentée pour le kit.
|
| Les écrans de la délégation s'appuient sur l'Alpine du template et sur son
| `custom.js`. Le kit ne peut pas faire pareil : il doit ouvrir son magasin
| local AVANT qu'Alpine ne monte le moindre écran, sans quoi le facilitateur
| voit un kit vide qui se corrige sous ses yeux. Or l'Alpine livré avec le
| template ne connaît pas `deferLoadingAlpine` et démarre tout seul.
|
| Le kit garde donc son propre Alpine, et l'on redonne ici les trois choses que
| le balisage du template attend : le composant `main`, le composant `dropdown`
| et le magasin `$store.app`. Une quarantaine de lignes, et l'ordre de
| démarrage reste sous contrôle.
*/

export const main = () => ({});

export const dropdown = (ouvertAuDepart = false) => ({
    open: ouvertAuDepart,
    toggle() {
        this.open = !this.open;
    },
});

/**
 * Le magasin d'apparence attendu par la barre latérale et l'en-tête.
 *
 * La préférence de thème est le seul réglage qu'on retient d'une visite à
 * l'autre. Elle passe par `localStorage` en direct plutôt que par le plugin
 * `$persist` du template, que le kit ne charge pas.
 */
export const apparence = {
    sidebar: false,
    theme: lireTheme(),
    isDarkMode: lireTheme() === 'dark',
    menu: 'vertical',
    layout: 'full',
    rtlClass: 'ltr',
    navbar: 'navbar-sticky',
    animation: '',
    semidark: false,

    toggleSidebar() {
        this.sidebar = !this.sidebar;
    },

    toggleTheme(valeur) {
        this.theme = valeur ?? (this.theme === 'dark' ? 'light' : 'dark');
        this.isDarkMode = this.theme === 'dark';

        try {
            localStorage.setItem('mvoe.theme', this.theme);
        } catch {
            // Navigation privée, stockage refusé : le thème vaut pour la visite.
        }
    },
};

function lireTheme() {
    try {
        return localStorage.getItem('mvoe.theme') === 'dark' ? 'dark' : 'light';
    } catch {
        return 'light';
    }
}

/**
 * L'en-tête du kit.
 *
 * Même rôle que `enteteDelegation` côté administration : la coquille est rendue
 * par le serveur, qui ne sait rien de la session. C'est donc ici qu'on inscrit
 * le nom du facilitateur et son arrondissement.
 */
export function enteteKit() {
    const facilitateur = session.facilitateur();

    return {
        nom: facilitateur?.nom ?? null,
        portee: facilitateur?.arrondissement ?? null,

        /*
         * On oublie la session, jamais le paquet ni la file : des séances non
         * remontées survivent à une déconnexion. Le serveur n'est pas prévenu,
         * et c'est voulu — fermer son kit doit marcher hors ligne.
         */
        async fermerSession() {
            await session.fermer();
            window.location.href = '/kit/connexion';
        },
    };
}
