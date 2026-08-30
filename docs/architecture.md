# Architecture du projet

Ce fichier décrit **ce qui existe aujourd'hui**. Le brief de refonte
(`CLAUDE.md`) en remet une partie en cause : les endroits concernés sont
signalés par « ⚠ refonte ».

---

## Commandes

Environnement local : Laravel Herd, PHP 8.3, MariaDB 10.4 (`root`, sans mot de
passe). Bases `mvoe` (développement) et `mvoe_test` (suite de tests).

```bash
php artisan serve --port=8123      # ou l'URL Herd du dossier
php artisan migrate:fresh --seed   # reconstruit toute la base de démonstration

npm run build                      # Vite + génère public/sw.js
npm run dev                        # Vite en veille

php artisan test
php artisan test --filter=EcartDeclareObserveTest
php artisan test tests/Feature/Api      # un dossier
```

**Ne lancez jamais deux suites en parallèle.** Elles partagent `mvoe_test`, et
le `migrate:fresh` de la seconde vide la base sous la première : on obtient une
salve d'erreurs « table not found » qui n'a rien à voir avec le code. C'est
arrivé deux fois.

La suite complète prend environ sept minutes, dont deux à trois de seeding —
payées une seule fois par exécution.

**`npm run build` génère le service worker.** `scripts/generer-sw.mjs` lit le
manifeste de Vite et injecte la liste des fichiers à précacher dans
`resources/sw/modele.js`. Ne modifiez jamais `public/sw.js` directement : il est
écrasé à chaque build, et un test échoue si sa liste de précache ne correspond
plus au manifeste.

### Commandes du projet

```bash
php artisan mvoe:verifier          # inventaire, registre, écarts déclaré/observé
php artisan mvoe:assistant         # rejoue les 12 situations, sert à régler le seuil
php artisan mvoe:assistant "..."   # essaie une question précise
php artisan mvoe:audios-muets      # crée les audios manquants référencés en base
```

`migrate:fresh --seed` affiche les accès de démonstration en fin d'exécution.
Ils sont la seule copie lisible des codes : tout est haché en base.

---

## Les trois espaces

| Espace | Adresses | Authentification |
|---|---|---|
| Kit facilitateur | `/kit`, `/kit/{connexion,seance,pointage,fidelite}` | téléphone + code d'appareil, **ou** e-mail + mot de passe |
| Délégation | `/superviseur`, `/superviseur/{connexion,rapport,parametres}` | e-mail + mot de passe |
| Espace parent | `/parent`, `/parent/{accueil,ecouter,feuilleton,questions,question,facilitateur}` | code parent + code à 4 chiffres |

`/design` affiche le système de design. `/` redirige vers le kit.

---

## Le principe qui gouverne tout le front

**Les routes web ne servent que des coquilles vides.** Aucune donnée métier
n'est rendue côté serveur : chaque écran lit l'API avec un jeton Sanctum,
exactement comme le fera l'application Flutter. Le client Blade n'a donc aucun
privilège que Flutter n'aurait pas — et c'est ce qui rend le hors-ligne
possible.

Trois tests le verrouillent (un par espace) : la base est vide, et si un titre
de module apparaissait quand même dans le HTML, c'est qu'il serait rendu côté
serveur.

Conséquence pratique : pour comprendre un écran, lisez le composant Alpine dans
`resources/js/`, pas la vue Blade.

### Où vit l'état

| Donnée | Stockage | Pourquoi |
|---|---|---|
| Session + paquet + file d'envoi du facilitateur | **IndexedDB** (`resources/js/idb.js`) | doit survivre des jours sans réseau |
| Libellés locaux des parents | IndexedDB, **clé séparée** | données nominatives, jamais synchronisées |
| Session du superviseur | `sessionStorage` | poste partagé entre agents |
| Session du parent + position d'écoute | `sessionStorage` | téléphone partagé au foyer, pas de « rester connecté » |

`resources/js/magasin.js` expose des **lectures synchrones** (miroir en mémoire
chargé une fois par `ouvrirMagasin()`, avant `Alpine.start()`) et des
**écritures asynchrones qu'il faut attendre**. L'ordre est une règle :

```js
await file.ajouter(evenement);   // 1. écrit en local
this.indexCourant = index;       // 2. seulement ensuite affiché à l'écran
```

Trois tests vérifient cet ordre dans les composants qui écrivent.

---

## Le protocole de synchronisation

Le kit envoie des **événements horodatés et idempotents, jamais des états**.

1. Chaque événement porte un UUID généré hors ligne côté client.
2. `App\Services\ReceptionEvenements` est le **seul chemin d'écriture** des
   données de séance. Les seeders passent par lui aussi.
3. Chaque événement reçu est journalisé tel quel dans `evenements_sync`, pour
   toujours. Les tables `seances`, `presences`, `sequences_ouvertes` et
   `fiches_fidelite` n'en sont que la **projection courante**.
4. Un UUID déjà reçu est un doublon, ignoré en silence.
5. Le client ne retire un événement de sa file que s'il figure dans `acceptes`
   **ou** dans `doublons`.

`resources/js/synchronisation.js` part seul au retour du réseau et réessaie
toutes les 30 s. **Aucune erreur réseau n'est jamais affichée** : le mode avion
est le mode de travail prévu, pas une panne. Un test échoue si le mot
« Synchroniser » apparaît dans une vue du kit.

---

## L'écart déclaré / observé

C'est la fonctionnalité centrale. Deux sources indépendantes :

- **l'observé** — `sequences_ouvertes`, écrit passivement quand le facilitateur
  ouvre un bloc pendant la séance. Aucun geste délibéré de sa part ;
- **le déclaré** — `fiches_fidelite`, saisi de mémoire après la séance.

`Seance::ecarts()` les confronte et renvoie `declaree_non_observee` ou
`observee_non_declaree`. Couvert par `tests/Feature/EcartDeclareObserveTest.php`.

**L'écran de fiche de fidélité n'affiche rien de l'observé.** Montrer la trace
au facilitateur rendrait les deux sources dépendantes l'une de l'autre, et
l'écart ne mesurerait plus rien. Un test échoue si `etats`, `observee` ou
`duree_reelle` réapparaissent dans cette vue.

---

## L'assistant à corpus fermé

**Aucun modèle de langage.** `App\Services\AppariementCorpus` compare le texte
du parent aux `message_cle` des unités digitales :

```
score = poids IDF des mots de la question retrouvés dans l'unité
        ÷ poids IDF de tous les mots significatifs de la question
```

Score entre 0 et 1, seuil dans `config/mvoe.php`. Calibré sur les douze
situations : questions couvertes 0,333 → 1,000 ; hors corpus 0,000 → 0,119.
**Seuil à 0,30.** Vérifiable par `php artisan mvoe:assistant`.

En dessous du seuil, aucune unité n'est renvoyée — pas de « meilleure
approximation ». Le refus est un résultat, servi en **200**, avec les contacts
des facilitateurs actifs.

`appariements` journalise chaque question **sans `parent_id`**, pour améliorer
le corpus et rien d'autre.

---

## Conventions

- **Tout est en français** : tables, colonnes, modèles, méthodes, routes,
  commentaires. `Sequence::duree_minutes`, jamais `duration`.
- **`ParentProgramme` est le modèle de la table `parents`** — `Parent` est un
  mot réservé de PHP.
- Les seuils vivent dans **`config/mvoe.php`**, jamais dans le code. Le
  `ratio_max` d'une cohorte est une donnée, et la migration ne lui donne
  volontairement aucune valeur par défaut.
- Les états visuels partagés entre Blade et Alpine sont définis **une seule
  fois en CSS**, pilotés par un attribut : `.bloc-sequence[data-etat]` et
  `.pastille[data-etat]` dans `resources/css/app.css`.
- Les tests tournent sur **MySQL**, pas SQLite : le driver `pdo_sqlite` est
  absent et l'index FULLTEXT sur `unites_digitales.message_cle` n'existe pas en
  SQLite.

### Une famille de tests inhabituelle

Beaucoup de tests lisent le **code source** plutôt que d'exercer du
comportement : absence de `text-jaune`, absence de couleur hors palette, ordre
écriture-puis-affichage, absence de vocabulaire de score dans l'espace parent,
liste de précache du service worker à jour. Ce sont des règles de conformité et
de doctrine qui se cassent par petites touches, sans que rien n'échoue.
Contraste : `ContrastePaletteTest` recalcule les ratios WCAG et vérifie que ceux
affichés sur `/design` sont exacts.

---

## Le jeu de démonstration

| | Volume |
|---|---|
| Régions | 10, dont 1 peuplée (le Sud) |
| Départements / arrondissements | 4 / 29 |
| Comptes administratifs | 1 national, 1 régional, 4 départementaux, 29 d'arrondissement |
| Facilitateurs | 50, dont 6 jamais actifs |
| Cohortes | 64, dont **une seule complète en données** |
| Parents | 1 019, aucun nommé |
| Séances remontées | 27 |
| Activités de terrain | 120, des six types hors séance |
| Foyers / visites | 40 / 73, aucun nom |
| Groupes de soutien | 15, dont **3 actifs seulement** |
| Signalements | 8, dont **2 non traités** |
| Modules de formation | 4, dont **1 non validé** qui ne doit apparaître nulle part |
| Progressions | 1 module terminé, 1 commencé à 33 %, 1 jamais ouvert |
| Campagnes | 2, l'une à mi-cascade, l'autre que personne n'a ouverte |
| Diffusions | 536 sur six mois, dont 8 émissions radio (7 attestées) |

La cohorte complète est « Ebolowa II — groupe du mardi » (`CohorteSeeder`) :
langues, enfants par tranche d'âge, binômes, trois séances dont une portant un
écart dans les deux sens. C'est celle qu'on ouvre en démonstration.

Les soixante-trois autres (`ReseauDuSudSeeder`) existent pour une raison
précise : **sans elles, le tableau de bord ne démontre rien.** Le ministère, la
délégation régionale et le superviseur d'Ebolowa II liraient les mêmes chiffres.

Deux règles pour les seeders :

- **Un seul chemin d'écriture des séances.** `SeanceSeeder` et
  `SeancesDuSudSeeder` n'écrivent rien directement : ils rejouent la file
  d'événements qu'un kit hors ligne aurait remontée et la passent à
  `ReceptionEvenements`. Le jeu de démonstration prouve donc que ce chemin
  marche, au lieu de le simuler.
- **Tout est déterministe.** Deux exécutions produisent la même base, sans quoi
  une capture d'écran serait périmée avant d'être montrée.

Une cohorte est retrouvée **par son libellé**, jamais par sa position : depuis
que la région entière est peuplée, « la première cohorte » ne désigne plus
celle de la démonstration. Plusieurs tests sont tombés là-dessus.

## Points de friction connus

- **L'installation PWA exige un contexte sécurisé.** Sur `http://192.168.x.x`,
  Chrome refuse d'enregistrer le service worker : pas d'installation, pas de
  mode avion. Il faut un tunnel HTTPS ou un vrai domaine pour toute
  démonstration sur téléphone.
- Les fichiers audio sont des **WAV muets** générés par
  `mvoe:audios-muets` (pas d'encodeur MP3 disponible). L'interface doit rester
  utilisable quand un audio manque, et plusieurs tests le vérifient.
- Les textes bulu portent le marqueur `[BU]` : ce sont des **placeholders**, pas
  des traductions.
- `libelles.purger()` existe sans déclencheur.

---

## Les deux voies d'entrée d'un parent

Le brief demande deux voies d'entrée pour un parent et interdit par ailleurs
tout écran d'inscription publique. Les deux tiennent ensemble à une condition,
qui est celle retenue ici :

> **Le facilitateur crée le dossier et remet le code en main propre. Le parent
> l'ACTIVE en se connectant.** Rien n'est jamais créé par un visiteur anonyme.

Il n'existe donc aucune route qui crée un parent sans jeton facilitateur, et
`InscriptionParentTest` le vérifie.

Deux cas, un seul mécanisme :

| | Ce qui existe |
|---|---|
| Parent avec téléphone | un dossier **et** un compte : il active son code |
| Parent sans téléphone | un dossier seulement : il est pointé comme les autres |

### Pourquoi c'est un événement hors ligne

L'inscription se fait en séance ou en visite à domicile, sans réseau. Elle
passe donc par la file, comme le pointage (`type: inscription_parent`). Trois
conséquences :

1. **Le code à quatre chiffres est tiré sur l'appareil.** Attendre le serveur
   reviendrait à n'avoir rien à remettre à la personne assise en face.
2. **Le parent rejoint le paquet local immédiatement** (`paquet.ajouterParent`),
   sinon il serait inscrit mais impointable jusqu'au retour du réseau.
3. **Les inscriptions passent avant tout le reste à la réception**
   (`ReceptionEvenements::PRIORITE`) : quelqu'un qui arrive au début de la
   séance est pointé dans la foulée, et sa présence ne peut pas se rattacher à
   un dossier qui n'existe pas encore.

### Le journal ne garde jamais un secret

`evenements_sync` conserve chaque charge utile **telle quelle et pour toujours** :
c'est ce qui fait sa valeur de preuve, et c'est aussi ce qui en ferait le pire
endroit du système pour un code d'accès. Les champs de `CHAMPS_SECRETS` sont
donc **remplacés** à l'écriture, pas retirés : on voit qu'un code a bien été
transmis, sans pouvoir le relire.

```
"code_acces": "[secret, non conservé]"
```

En base, `parents.code_acces` est haché par le cast `hashed`. Le code en clair
n'existe donc qu'à deux endroits : l'écran qui l'affiche une fois, et le papier
sur lequel le facilitateur l'écrit.

### Le repère du facilitateur

Aucun nom n'est demandé ni accepté. Pour reconnaître la personne au pointage,
le facilitateur saisit un repère (« la voisine du dispensaire ») qui vit sous
sa propre clé dans IndexedDB, hors de la file d'envoi, et est purgé en fin de
cycle. Aucune fonction de `magasin.js` ne le recopie dans un événement, et il ne
faut jamais en écrire une.

## Campagnes, canaux, bibliothèque

### La cascade est un enregistrement, pas une simulation

Déclencher une campagne crée **toutes** les affectations d'un coup, aux quatre
niveaux : région, département, arrondissement, facilitateur. Il n'y a ni file
d'attente, ni propagation asynchrone.

C'est un choix, et le brief l'impose : dans la vraie vie, la cascade
administrative n'est pas un processus, c'est un fait. Le ministère décide, et
tous les échelons sont concernés au même instant. Ce qui avance dans le temps,
c'est la **prise de connaissance** de chaque échelon — un geste manuel, qui le
reste : cocher automatiquement à l'ouverture d'un écran ferait passer une
consultation pour une décision.

L'écran affiche donc `9/29 arrondissements`, avec la phrase qui va avec : *ce
n'est pas un taux d'exécution du programme, c'est le nombre de gens qui savent
qu'elle existe.* Confondre les deux ferait croire qu'une campagne « à 80 % » a
touché 80 % des parents.

### Une contradiction du brief, et comment elle est tranchée

La section 7 interdit « aucun message sortant vers un parent ». La section 2
demande un `SmsDriver` avec un `send()`. Les deux tiennent à une condition, qui
est celle retenue ici :

> **Aucun message ne peut atteindre un parent identifié.** La table `parents` n'a
> pas de numéro de téléphone — seulement `telephone_partage`, un booléen. Il n'y
> a donc nulle part où lire à qui envoyer.

Ce qu'un canal vise est une **cible collective** écrite en toutes lettres
(« Parents inscrits, Sud », « Menu USSD — *880# »), jamais une personne. Un
parent vient au système ; le système ne va jamais vers lui nommément.

C'est aussi pourquoi les pilotes restent factices ici : brancher un opérateur
supposerait d'abord de décider si le programme collecte des numéros, et c'est
une décision qui n'appartient pas à l'équipe technique.

### Les canaux : une abstraction, quatre pilotes

`App\Canaux\PiloteDeCanal` a deux gestes : `envoyer()` et `statistiques()`.
Tout le reste — API de l'opérateur, format des numéros, quotas — appartient au
pilote et ne remonte jamais. **Brancher un opérateur réel consiste à remplacer
une ligne du registre `Canaux::PILOTES`.** Si cette interface devait changer
pour accueillir un opérateur, c'est que l'abstraction serait fausse.

Les quatre pilotes sont **factices**, et l'API le dit (`pilotes_factices: true`,
`factice: true` par canal). Un prototype qui laisserait croire que des SMS
partent vraiment mentirait à son jury.

Chaque canal compte ce qui lui est propre : messages remis pour le SMS,
**abandons en cours de menu** pour l'USSD — une session ouverte ne dit rien, une
session abandonnée au troisième écran dit que le troisième écran est mauvais.

### La radio ne fabrique aucune audience

Une station qui annonce « deux millions d'auditeurs » multiplie une couverture
théorique par une population : elle n'a compté personne. Reprendre ce chiffre
reviendrait à mentir avec les mots de quelqu'un d'autre.

Ce qui est enregistré : les diffusions **attestées** — quelqu'un a signé que
l'émission est passée. Sans attestation, une diffusion déclarée n'est qu'une
intention.

Ce qui est mesuré : le **surcroît d'appels vocaux et de sessions USSD dans les
48 heures qui suivent**, en moyenne horaire.

```
7,88 appels/heure après l'émission   contre   2,41 le reste du temps
                       → +226,5 %, sur 7 diffusions attestées
```

Trois précautions dans le calcul, et elles comptent autant que le résultat :

1. On compare des **moyennes horaires**, pas des totaux : les fenêtres et les
   jours ordinaires ne durent pas le même temps, et comparer des totaux ferait
   passer une différence de durée pour un effet.
2. Le **SMS est exclu** de la mesure : il est poussé, pas appelé. Le mélanger
   aux canaux entrants fausserait tout.
3. Quand il n'y a pas de quoi mesurer, l'API rend `mesurable: false` **avec sa
   raison** — « pas mesurable » et « aucun effet » ne veulent pas dire la même
   chose.

Et la limite est rendue **avec** le chiffre, pas dans une annexe : la mesure ne
capte que ceux qui ont un téléphone et qui rappellent, donc elle sous-estime
l'effet. Ce qui vaut mieux que l'inverse.

### La bibliothèque et la file de validation

Réservées au national. Une délégation qui validerait ses propres contenus
produirait dix curriculums différents, et le programme cesserait d'être
national.

Deux choses s'y voient qu'on ne voit nulle part ailleurs :

- **La couverture par langue.** Une unité chargée en français et pas en bulu
  n'atteint pas les locuteurs bulu, quel que soit le nombre total de
  réalisations. C'est le seul chiffre qui dise où porter l'effort.
- **Une langue enregistrée sans réalisation** apparaît à zéro, en couleur
  d'alerte : elle est une promesse, pas un service.

Retirer une langue la sort de l'interface **sans rien supprimer**, et l'API le
dit en rendant `realisations_conservees`.

## Deux catalogues, pas un

Le programme sert deux publics qui n'ont rien en commun, et leurs contenus
vivent dans des tables distinctes :

| | Pour le parent | Pour le facilitateur |
|---|---|---|
| Tables | `unites_digitales`, `feuilletons`, `questions` | `modules_formation`, `sections_formation` |
| Langues | celles chargées par le ministère | français seulement |
| Accès | espace parent, sur son téléphone | kit, hors ligne |

### Rouvrir un module est une activité

C'est le point du brief, et c'est ce qui justifie tout le reste : *un
facilitateur formé il y a deux ans ne se refait pas former, il rouvre ses
modules.* Ce faisant, il rouvre l'application — donc `derniere_activite` avance
et il reste actif dans le registre.

C'est le seul dispositif de réactivation qui ne coûte ni déplacement, ni per
diem, ni convocation. Ne pas compter cette ouverture reviendrait à déclarer
quelqu'un inactif au moment précis où il se remet à jour.

### Un contenu non validé ne peut pas être diffusé

`ModuleFormation::diffusables()` est **le seul chemin** par lequel un module
atteint un facilitateur : l'API, le paquet hors ligne et la projection de
progression y passent tous. Un module en `soumis` ou en `brouillon` n'existe
donc ni dans la liste, ni à son adresse directe, ni dans le paquet — et aucune
progression ne peut s'inscrire dessus.

Le jeu de démonstration contient un module non validé exprès. Trois tests
vérifient qu'il reste invisible par les trois portes.

### Où en est-on, hors ligne

La progression sort par la file (`progression_formation`) et rentre par le
paquet. Les deux se rejoignent dans le magasin local :

- le paquet apporte ce que le serveur savait (`formation[].sections_vues`) ;
- chaque section lue s'ajoute localement **avant** d'être mise en file ;
- à la réception, les sections **fusionnent** au lieu de se remplacer — une
  remontée tardive n'efface pas une lecture plus récente.

Rouvrir un module reprend donc à la première section non lue, pas au début. Un
facilitateur qui a lu la moitié d'une capsule dans un car la reprend là où il
l'avait laissée, même s'il n'a jamais retrouvé de réseau entre-temps.

**Terminé se constate, il ne se déclare pas.** Quand toutes les sections ont été
vues, `termine_a` se remplit : on ne demande pas au facilitateur de cocher une
case pour confirmer ce qu'il vient de faire.

### Ce que le superviseur en voit

`modules_ouverts`, `modules_termines` et `derniere_formation` figurent dans le
registre, à côté de la dernière activité. Ce n'est pas de la surveillance :
c'est la seule façon de repérer qui décroche **avant** qu'il ne disparaisse du
registre, et de lui proposer quelque chose plutôt que de constater six mois plus
tard qu'il n'anime plus.

## Les langues sont des données

Le Cameroun compte plus de deux cents langues. Un enum PHP en fige trois et
exige un déploiement pour en ajouter une quatrième : ce serait l'équipe
technique qui déciderait alors dans quelle langue un parent peut écouter le
programme. Cette décision appartient au ministère, et elle se prend en chargeant
des réalisations.

L'enum `App\Enums\Langue` a donc été supprimé, et quatre colonnes
`enum('fr','en','bulu')` remplacées par une clé étrangère :

| Table | Avant | Après |
|---|---|---|
| `parents` | `langue_pref` | `langue_id` |
| `realisations` | `langue` | `langue_id` |
| `feuilletons` | `langue` | `langue_id` |
| `situations_frequentes` | `langue` | `langue_id` |

`langues.actif` retire une langue de l'interface **sans supprimer les contenus
déjà chargés** : on cesse de la proposer, on ne perd rien.

`langues.endonyme` est le nom de la langue dans cette langue. C'est lui qu'on
affiche dans le sélecteur : personne ne cherche « Bulu » écrit en français
quand il ne lit pas le français.

### Trois règles qui en découlent

**La langue est un attribut du parent, pas de sa région.** Un locuteur bulu
installé dans l'Océan reçoit du bulu. C'est `parents.langue_id`, et un test le
vérifie sur le jeu de démonstration.

**L'interface ne propose que les langues réellement chargées pour ce contenu.**
`GET /api/parent/modules/{id}/unites` et `GET /api/parent/unites/{id}` rendent
tous deux `langues_disponibles`, et le sélecteur permanent s'y restreint quand
un contenu est ouvert. Proposer une langue non chargée, c'est promettre un
contenu qui n'existe pas.

**Un repli est toujours annoncé, et il nomme la langue servie.** `langue_de_repli`
est un booléen de l'API ; l'écran affiche « Voici la version en français »
plutôt que de laisser croire à une traduction. Afficher du français en laissant
croire que c'est du bulu serait pire que de ne rien afficher.

Le titre montré au-dessus d'une unité est celui de la **réalisation servie**,
jamais `message_cle` — ce dernier n'existe qu'une fois, en français, et n'est là
que pour l'assistant. L'afficher au-dessus d'un audio bulu ferait dire à l'écran
une chose et à la voix une autre.

### `GET /api/langues` est publique

Le parent choisit sa langue **avant** de se connecter : on ne peut pas lui
demander de lire « choisissez votre langue » dans une langue qu'il n'a pas
encore choisie. La liste des langues d'un programme public n'est pas un secret.

### Le piège de l'étalement

`baseParent()` ne doit exposer **aucun accesseur**. `{ ...baseParent() }` évalue
les `get` au moment de l'étalement et n'en copie que la valeur : un accesseur y
serait figé à la construction, et le sélecteur de langue n'aurait éternellement
affiché que la langue de repli — sans la moindre erreur. Les méthodes, elles,
survivent à l'étalement. Un test le vérifie.

## Le travail de terrain

Le programme ne se résume pas aux séances de cohorte. Une causerie sous l'arbre,
un porte-à-porte, une visite à domicile comptent autant — et ne pas les
enregistrer revient à conclure qu'elles n'ont pas eu lieu, puis à écrire dans un
rapport qu'un facilitateur n'était pas actif.

Cinq types d'événements, tous saisis **sans réseau**, tous passant par la file :

| Événement | Ce qu'il crée | Règle qui le gouverne |
|---|---|---|
| `activite` | une activité de terrain | l'arrondissement vient du compte, jamais de la requête |
| `groupe_soutien` | un GSP et ses membres | `derniere_reunion` naît vide, jamais inventée |
| `foyer` | un dossier de foyer | aucun nom, aucune adresse, aucune position |
| `visite` | une visite sur un foyer | des cases cochées, jamais un récit |
| `signalement` | une situation préoccupante | aucune identité, aucune notification |

### Ce qui rend le critère « handicap » mesurable

`activites.nb_participants_handicap` est saisi **activité par activité**, à côté
de la répartition par sexe. C'est toute la différence entre écrire « le
programme est inclusif » dans un rapport et pouvoir le montrer.

La cohérence est vérifiée à la réception : `nb_hommes + nb_femmes` ne peut pas
dépasser `nb_parents_touches`, et un total incohérent est **refusé plutôt que
corrigé** — deviner laquelle des deux saisies est fausse produirait un chiffre
que personne n'a jamais compté. Ce qui n'est pas réparti par sexe est dit
(`sexeNonRenseigne()`), jamais comblé.

Pour les foyers, on ne demande pas « y a-t-il un handicapé ? » mais ce que
quelqu'un a du mal à faire — les domaines du questionnaire court du Washington
Group. La première question produit des zéros, la seconde produit des chiffres.

### Les trois règles absolues des signalements

1. **Aucune identité.** Le modèle n'a pas de colonne où mettre un nom, et il ne
   doit jamais en avoir. Type, gravité, arrondissement, et le facilitateur avec
   qui le superviseur va en parler.
2. **Aucune notification automatique.** `SignalementController` n'a aucun canal
   de sortie, et un test vérifie qu'il n'en apparaît pas. Une alerte automatique
   de maltraitance préviendrait avant que quiconque ait vérifié, et parfois elle
   préviendrait l'agresseur.
3. **La suite donnée est toujours visible** par celui qui a signalé. Elle est
   obligatoire dès qu'on oriente ou qu'on clôt. Un signalement sans retour est un
   signalement qu'on ne refait pas — et le suivant, on le garde pour soi.

### Ce qui se recalcule au lieu d'être stocké

Ni `facilitateurs.actif` ni `groupes_soutien.actif` n'existent en base. Les deux
statuts se déduisent d'une date (`derniere_activite`, `derniere_reunion`) et des
seuils de `config/mvoe.php`. Un booléen `actif` resterait à `true` pendant des
années sans que personne ne s'en aperçoive.

`derniere_reunion` ne bouge que depuis le terrain, à la réception d'une activité
de type `reunion_gsp` — et **jamais vers l'arrière** : une vieille réunion
remontée en retard ne doit pas effacer une récente.

## La portée, et le tableau de bord unique

C'est le point d'ingénierie central du projet. Le brief le dit sans détour :
« ne construis pas cinq tableaux de bord, construis-en un et filtre-le ».

### Une portée est une liste d'arrondissements

Tout le système hiérarchique tient dans une observation : facilitateurs,
cohortes, activités, foyers et signalements portent tous un `arrondissement_id`.
Une portée n'est donc jamais qu'une liste d'arrondissements.

| Niveau | Ce qu'il couvre |
|---|---|
| `national` | tout — aucun filtre |
| `region` | les arrondissements de sa région |
| `departement` | ceux de son département |
| `arrondissement` | le sien |
| `facilitateur` | le sien, plus un filtre sur `facilitateur_id` |

`App\Support\Portee` calcule cette liste une fois. `LimiteParPortee` l'applique.
**Il n'existe aucun autre endroit où filtrer** : une condition de portée écrite
à la main dans un contrôleur est un défaut, parce que c'est celle-là qu'on
oubliera dans la requête suivante.

```php
Facilitateur::dansLaPortee($portee)->get();
Seance::dansLaPortee($portee)->get();
```

Un modèle qui ne porte pas lui-même d'arrondissement déclare un **relais** vers
celui qui le porte — une séance tient l'arrondissement de sa cohorte :

```php
protected static function relaisDePortee(): ?array
{
    return [Cohorte::class, 'cohorte_id'];
}
```

`null` renvoyé par `arrondissements()` signifie **tous**, jamais **aucun**. Une
liste vide reste une liste vide : elle ne montre rien, elle n'ouvre pas tout.

### La descente

`Portee::sousNiveau()` découpe : le national se lit en régions, une région en
départements, un arrondissement en facilitateurs. `Portee::contient()` autorise :
un délégué régional ouvre le tableau de bord d'un de SES départements, jamais
d'un autre. La vérification ne raisonne sur aucune hiérarchie — elle compare
deux listes d'arrondissements, ce qui la rend démontrable.

### Un seul service

`App\Services\TableauDeBord` charge les données de la portée **une fois**, puis
regroupe en mémoire. La même fonction `indicateurs()` calcule le total et chaque
ligne du découpage : c'est ce qui rend l'agrégation vraie par construction, et
non par convention. La somme des quatre départements fait la région parce que
c'est le même code, pas parce qu'on l'a vérifié une fois.

Deux routes, un service : `/api/superviseur/tableau-de-bord` pour les quatre
niveaux de l'administration (avec `?niveau=&entite=` pour descendre), et
`/api/facilitateur/tableau-de-bord` pour le cinquième.

Côté interface, un seul écran : `superviseur/tableau-de-bord.blade.php` ne sait
pas à quel niveau il se trouve et n'a pas à le savoir — le serveur lui rend
toujours la même forme.

## Les deux systèmes de design

Depuis l'étape 3, deux systèmes coexistent, et la frontière entre eux est
délibérée.

| | Administration | Terrain |
|---|---|---|
| Écrans | `views/superviseur`, `views/design` | `views/kit`, `views/parent` |
| Coquille | `layouts/delegation` | `layouts/kit`, `layouts/parent` |
| Palette | template Vristo (`primary`, `success`…) | Mvoé (`noir`, `jaune`, `jaune-sourd`) |
| Corps | 15 px | 17 px, cibles 48 px |
| Marqueur | — | `<body class="terrain">` |

Le terrain n'est pas un écran de bureau : il est tenu d'une main, en plein
soleil, dans une salle sans électricité. `SystemeDeDesignTest` échoue si une
couleur du template apparaît dans une vue de terrain.

**La règle de contraste, dans les deux palettes.** Une couleur pleine est une
surface, jamais une encre. Sur blanc, `jaune` fait 1,6:1, `warning` 2,2:1,
`success` 3,0:1, `danger` 3,6:1 — toutes échouent le seuil du texte courant.
Pour écrire, on utilise `success-texte`, `warning-texte`, `danger-texte`, et un
`white-dark` assombri (celui de Vristo, `#888ea8`, ne faisait que 3,2:1).
`ContrastePaletteTest` **calcule** ces ratios plutôt que de les recopier : un
chiffre écrit de mémoire finit toujours par être faux.

**Ce que la coquille sait de la session.** `layouts/delegation` est rendue par
le serveur, qui ne connaît pas la session : le jeton vit dans `sessionStorage`.
C'est le composant Alpine `enteteDelegation` qui inscrit la portée du compte
dans la barre latérale et masque le lien « Enregistrer » aux niveaux qui n'en
ont pas le droit. Ce masquage est un confort d'interface : l'autorisation réelle
est dans `EnregistrementFacilitateur`, côté serveur.

## Ce qui reste à faire

Les neuf étapes du brief sont faites. Ce qui suit est ce que je n'ai pas fait,
et pourquoi.

**`unites_digitales` n'a pas été renommée en `contenus`.** Le brief décrit une
table unique portant les quatre types de contenu. La refonte coûterait cher —
elle touche le paquet hors ligne, l'assistant, l'espace parent — pour un gain
qui ne se voit nulle part à l'écran. Ce que la table `contenus` apportait
vraiment, `statut_validation`, existe désormais sur `realisations` et sur
`modules_formation`, et la règle « un contenu non validé ne peut pas être
diffusé » est appliquée aux deux.

**La modalité `video` n'existe pas.** Aucune vidéo n'est chargée, et une
modalité vide dans un sélecteur serait une promesse.

**Les questions de la semaine ne portent pas de langue.** C'est le seul contenu
parent dans ce cas. Le refactor entamé sous l'ancien brief (`questions_traduites`,
`options_traduites`) est à reprendre sur la table `langues`.

**L'interface n'est pas traduite en anglais.** Le brief demande de « préparer la
structure de traduction » ; les libellés sont encore écrits en dur dans les
vues. C'est un travail mécanique et volumineux, sans risque, qui ne change rien
à ce que le jury doit voir fonctionner.

**Aucun écran ne crée un module de formation ni une réalisation.** Le ministère
les charge par seeder ; la bibliothèque les VALIDE mais ne les rédige pas.

**Le kit ne crée pas de groupe de soutien.** Les GSP existent par l'API et le
seeder, et le tableau de bord suit leur continuité — mais le facilitateur n'a
pas d'écran pour en constituer un.

**Fait.** La hiérarchie `regions` / `departements` / `arrondissements` et la
portée à cinq niveaux, l'enregistrement d'un facilitateur par son superviseur,
la refonte visuelle sur le template, le tableau de bord unique à cinq portées
avec sa descente, l'inscription d'un parent par son facilitateur hors ligne, et
le travail de terrain — activités, visites à domicile, groupes de soutien,
signalements —, les langues devenues des données, les modules de formation du
facilitateur, et les campagnes, canaux et bibliothèque du ministère.

**Les neuf étapes du brief sont faites.**

Ce qui n'est pas remis en cause et doit survivre : le protocole de
synchronisation, l'écart déclaré/observé, la couche hors ligne, l'assistant à
corpus fermé et son seuil.
