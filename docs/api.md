# API Mvoé

Cette API est la **seule** porte d'entrée des données. Le client Blade la consomme
exactement comme le fera l'application Flutter : aucun privilège n'est attaché au
navigateur, aucune route web ne double une route d'ici, et aucune session de
navigateur ne donne accès à quoi que ce soit.

- Base : `/api`
- Format : JSON en entrée comme en sortie. Envoyez toujours `Accept: application/json`.
- Authentification : `Authorization: Bearer <jeton>` (Laravel Sanctum).
- Langue des identifiants et des champs : **français**, comme le reste du projet.

## Sommaire

1. [Rôles et permissions](#roles-et-permissions)
2. [Ouvrir et fermer une session](#ouvrir-et-fermer-une-session)
3. [Kit facilitateur](#kit-facilitateur)
4. [Protocole de synchronisation](#protocole-de-synchronisation)
5. [Superviseur](#superviseur)
6. [Espace parent](#espace-parent)
7. [Annuaire public](#annuaire-public)
8. [Erreurs](#erreurs)
9. [Ce que l'API ne fait pas](#ce-que-lapi-ne-fait-pas)

---

## Rôles et permissions

Trois rôles, trois jetons, strictement cloisonnés. Chaque jeton porte une
*ability* Sanctum, et les routes l'exigent. Un jeton parent qui appelle une route
facilitateur reçoit **403**, pas 404 : l'erreur dit que le rôle ne convient pas,
elle ne prétend pas que la route n'existe pas.

| Rôle | Ability | S'authentifie avec | Expiration |
|---|---|---|---|
| Facilitateur | `facilitateur` | téléphone + code d'appareil (6 chiffres) **ou** e-mail + mot de passe | aucune |
| Parent | `parent` | code parent + code d'accès (4 chiffres) | `mvoe.parent.duree_session_minutes` (20 min) |
| Superviseur | `superviseur` | e-mail + mot de passe | aucune |

**Les deux voies d'entrée du facilitateur.** Sur le terrain, il ouvre son *kit*
avec son numéro et un code d'appareil à 6 chiffres remis en main propre à la
formation : court, mémorisable, saisissable d'une main en plein soleil. Depuis
un poste de la délégation, il se connecte avec un e-mail et un mot de passe
classiques. Les deux couples ouvrent **la même session avec exactement les mêmes
droits** — la voie d'entrée ne donne aucun privilège supplémentaire. Le jeton
n'expire pas : le kit doit rester utilisable des jours entiers sans réseau.

**Pourquoi le jeton parent expire.** Le téléphone est souvent partagé au sein du
foyer. Il n'y a pas de « rester connecté », pas de renouvellement silencieux, et
le client doit garder ce jeton en `sessionStorage` — jamais en `localStorage`.

### Limites de débit

| Groupe | Limite |
|---|---|
| `POST */session` | 5 par minute et 30 par jour, par IP |
| `POST /parent/assistant` | 20 par minute |
| reste de l'API | 120 par minute |

Les codes du système sont courts par nécessité — 4 chiffres pour quelqu'un qui
ne retiendra pas un mot de passe. C'est la limite d'essais qui les protège, pas
leur longueur.

---

## Ouvrir et fermer une session

### `POST /api/facilitateur/session`

Deux couples d'identifiants acceptés, au choix. Envoyez l'un **ou** l'autre.

```json
{ "telephone": "699 41 27 08", "code_appareil": "481207" }
```

```json
{ "email": "ndzana.etienne@minproff.cm", "password": "mvoe-demo" }
```

**200**

```json
{
  "jeton": "1|HMEuxT5vQU8srAkf...",
  "expire_a": null,
  "facilitateur": { "id": 1, "nom": "Ndzana Étienne", "arrondissement": "Ebolowa II" }
}
```

**422** si le couple ne correspond pas.

### `POST /api/parent/session`

```json
{ "code_parent": "EB2-04", "code_acces": "4821", "majeur": true }
```

`majeur` est obligatoire. À `false`, la réponse est **403** :

```json
{
  "message": "L'accès est réservé aux personnes majeures. Rapprochez-vous de votre facilitateur.",
  "orientation": "facilitateur"
}
```

L'accès est interdit aux moins de 18 ans : la loi n° 2024/017 exige le
consentement du représentant légal, que ce canal ne permet pas de recueillir.

**200**

```json
{
  "jeton": "2|9pQ...",
  "expire_a": "2026-08-28T09:18:35+00:00",
  "parent": { "code_parent": "EB2-04", "langue_pref": "bulu" }
}
```

### `POST /api/superviseur/session`

```json
{ "email": "superviseur@mvoe.test", "password": "mvoe-demo" }
```

### `DELETE /api/session`

Authentifié, tous rôles. Révoque le jeton courant. **200**

---

## Kit facilitateur

Toutes les routes exigent l'ability `facilitateur`.

### `GET /api/facilitateur/cohortes`

Écran d'accueil : ses cohortes et la prochaine séance.

```json
{
  "cohortes": [{
    "id": 1,
    "libelle": "Ebolowa II — groupe du mardi",
    "arrondissement": "Ebolowa II",
    "effectif": 20,
    "ratio_max": 20,
    "places_restantes": 0,
    "date_debut": "2026-07-07",
    "seances_tenues": 3,
    "prochaine_seance": {
      "module": { "id": 9, "numero": 9, "titre": "Protéger son enfant des violences", "renseigne": false },
      "date_estimee": "2026-08-28"
    }
  }]
}
```

`prochaine_seance` est une **estimation**, pas un calendrier : le prochain module
est celui qui suit le plus avancé de ceux déjà tenus, et la date suit le rythme
hebdomadaire. Il n'existe pas de table de planning. `prochaine_seance` vaut
`null` quand le curriculum est terminé.

### `GET /api/facilitateur/cohortes/{cohorte}/paquet`

**Le paquet de cohorte.** Téléchargé **une fois** ; ensuite tout fonctionne hors
ligne, sans exception. **403** si la cohorte n'est pas attribuée au porteur du
jeton.

Poids mesuré sur le jeu de démonstration : **13 Ko de JSON**, plus **2,4 Mo**
d'audio à récupérer séparément via `audios`. Cible totale : moins de 10 Mo.

```json
{
  "genere_a": "2026-08-28T09:00:00+00:00",
  "cohorte": { "id": 1, "libelle": "…", "arrondissement": "Ebolowa II",
               "ratio_max": 20, "date_debut": "2026-07-07",
               "curriculum_version": { "id": 1, "label": "…" } },
  "modules": [{
    "id": 8, "numero": 8, "titre": "Discipline positive", "ordre": 8,
    "renseigne": true, "duree_totale_minutes": 90,
    "sequences": [{
      "id": 1, "titre": "Accueil et brise-glace", "ordre": 1,
      "duree_minutes": 10, "type": "consigne_animation",
      "consigne": "Tout le monde se lève. On chante et on danse ensemble…",
      "est_brise_glace": true,
      "unites": []
    }, {
      "id": 2, "titre": "Discipliner, est-ce punir ?", "ordre": 2,
      "duree_minutes": 20, "type": "unite_digitale",
      "consigne": null, "est_brise_glace": false,
      "unites": [{
        "id": 1,
        "message_cle": "Discipliner un enfant, c'est lui apprendre à se conduire, pas lui faire mal.",
        "realisations": [{
          "langue": "fr", "modalite": "audio", "titre": "Discipliner, c'est enseigner",
          "contenu_texte": null,
          "fichier_audio": "http://…/audio/unites/m08-u1-fr.wav",
          "pictogrammes": null
        }]
      }]
    }]
  }],
  "parents": [{ "code_parent": "EB2-01", "langue_pref": "bulu",
                "statut_matrimonial": "union", "revenu_regularite": "irregulier",
                "telephone_partage": true }],
  "binomes": [["EB2-01", "EB2-07"]],
  "audios": ["http://…/audio/unites/m08-u1-fr.wav", "…"]
}
```

Points à respecter côté client :

- **Aucun nom** n'est présent, et il ne faut pas en ajouter au serveur. Le
  facilitateur reconnaît ses parents par un **libellé local** qu'il saisit
  lui-même (« Odile, marché »). Ce libellé vit dans IndexedDB, **n'entre jamais
  dans la file d'envoi**, et est purgé en fin de cycle.
- **Aucun `code_acces`** de parent : un appareil perdu ne doit pas ouvrir vingt
  espaces parents.
- `fichier_audio` peut être `null` : l'enregistrement n'existe pas encore.
  L'interface doit rester utilisable et basculer sur la version texte.
- `renseigne: false` marque les modules annoncés mais vides. Affichez-les, sans
  laisser croire qu'ils sont prêts.
- `est_brise_glace` est le drapeau à lire pour le rendu sans chronomètre ni
  contrôle. Ne le devinez pas depuis le titre.
- `audios` est la liste exacte à déposer dans la Cache API.

### `GET /api/facilitateur/seances/{uuid}`

Relit une séance remontée, par son UUID **client**. Sert au kit à confirmer ce
que le serveur a enregistré, et au facilitateur à revoir son propre écart.

```json
{
  "uuid": "21d213a0-…",
  "date": "2026-08-25",
  "module": { "numero": 8, "titre": "Discipline positive" },
  "recue_a": "2026-08-28T09:05:00+00:00",
  "delai_remontee_jours": 3,
  "presences": [{ "code_parent": "EB2-01", "statut": "present" }],
  "ecarts": [{
    "sequence": { "id": 5, "ordre": 5, "titre": "Ce que je fais cette semaine à la maison" },
    "declaree": true, "observee": false, "ecart": "declaree_non_observee"
  }]
}
```

---

## Protocole de synchronisation

### `POST /api/facilitateur/evenements`

La remontée envoie des **événements horodatés et idempotents, jamais des états**.

```json
{
  "evenements": [{
    "uuid": "8f1c…",
    "type": "seance",
    "seance_uuid": null,
    "emis_a": "2026-08-25T15:00:00+01:00",
    "charge": { "cohorte_id": 1, "module_id": 8, "date": "2026-08-25" }
  }]
}
```

| Champ | Règle |
|---|---|
| `uuid` | UUID généré **côté client, hors ligne**. Clé d'idempotence. Requis. |
| `type` | `seance`, `presence`, `sequence_ouverte`, `fiche_fidelite`, `bilan_seance` |
| `seance_uuid` | UUID de la séance de rattachement. `null` pour `type: seance`. |
| `emis_a` | Date ISO 8601 du geste, telle que l'appareil l'a vue. |
| `charge` | Objet, dépend du type (ci-dessous). |

Maximum 500 événements par envoi.

#### Charges par type

`seance` — l'UUID de l'événement **est** celui de la séance ; tous les
événements suivants le portent dans `seance_uuid`.

```json
{ "cohorte_id": 1, "module_id": 8, "date": "2026-08-25" }
```

`presence`

```json
{ "code_parent": "EB2-01", "statut": "present" }
```

`statut` ∈ `present`, `absent`, `rattrape_binome`.

`sequence_ouverte` — **l'observé**, écrit quand le facilitateur ouvre le bloc.
Aucune action délibérée de sa part : c'est une trace d'usage, pas une saisie.

```json
{ "sequence_id": 3, "ouverte_a": "2026-08-25T15:30:00+01:00", "duree_reelle_secondes": 1500 }
```

`fiche_fidelite` — **le déclaré**, rempli après la séance, jamais pendant.

```json
{ "sequence_id": 3, "realisee_bool": true, "note_qualite": 2, "commentaire": null }
```

`bilan_seance` — le champ libre « qu'est-ce qui a le moins bien marché ? ».

```json
{ "commentaire": "Séance écourtée, la pluie sur la tôle couvrait les voix." }
```

#### Réponse — **202**

```json
{
  "recus": 31,
  "acceptes": ["8f1c…", "…"],
  "doublons": [],
  "rejetes": [{ "uuid": "…", "raison": "cohorte_non_attribuee" }]
}
```

**Règle pour le client :** ne supprimez un événement de la file locale que s'il
figure dans `acceptes` **ou** dans `doublons`. Un envoi coupé au milieu peut
alors être rejoué entier sans rien dupliquer ni rien perdre.

#### Garanties du serveur

- **Idempotence.** Un `uuid` déjà reçu est ignoré en silence et compté dans
  `doublons`. Vérifié par test : 31 acceptés, puis 31 doublons.
- **Ordre indifférent.** Les ouvertures de séance sont traitées en premier ; le
  kit peut envoyer sa file à l'envers.
- **Rien n'est perdu.** Chaque événement reçu est conservé tel quel dans
  `evenements_sync`, pour toujours. Les tables métier n'en sont que la
  projection courante : une correction de pointage met à jour l'état, mais
  l'événement d'origine reste au journal.
- **Isolation par transaction.** Un événement rejeté n'empêche pas les autres
  de passer.
- **Contrôles.** Une cohorte qui n'est pas la vôtre → rejet
  `cohorte_non_attribuee`. Une séquence qui n'appartient pas au module de la
  séance → rejet `sequence_hors_module`, parce qu'elle fausserait l'écart.

---

## Superviseur

Ability `superviseur`.

### Périmètre de lecture

Le compte porte un `arrondissement`, et toutes les lectures y sont bornées :

| `users.arrondissement` | Périmètre |
|---|---|
| `null` | Délégation **départementale** : les huit arrondissements de la Mvila |
| une valeur | Délégation **d'arrondissement** : celui-ci seulement |

Le périmètre vient du compte, **jamais de la requête**. Un paramètre
`?arrondissement=` ne peut que restreindre davantage : une délégation
d'Ebolowa II qui demande Mvangan reçoit une liste vide, pas les facilitateurs
de Mvangan. Chaque réponse porte un champ `perimetre` en clair, affiché à
l'écran et dans l'en-tête du rapport imprimé — personne ne doit croire lire
tout le département alors qu'il ne lit qu'un arrondissement.

Une délégation d'arrondissement n'a pas à lire les écarts d'une autre :
l'écart se lit AVEC le facilitateur concerné, et son supérieur direct est le
seul à en avoir l'usage.

### `GET /api/superviseur/facilitateurs`

Le registre. Paramètre optionnel `arrondissement`.

```json
{
  "synthese": { "formes": 14, "actifs": 7, "inactifs": 7,
                "jamais_actifs": 3, "seuil_inactivite_jours": 60 },
  "facilitateurs": [{
    "id": 1, "nom": "Ndzana Étienne", "telephone": "699 41 27 08",
    "arrondissement": "Ebolowa II", "date_formation": "2024-03-12",
    "derniere_activite": "2026-08-21", "jours_depuis_activite": 7,
    "seances_animees": 3, "actif": true
  }]
}
```

`actif` n'est **pas** une colonne : il se recalcule à chaque consultation à
partir de `derniere_activite` et du seuil `mvoe.facilitateur.jours_inactivite`.
Un statut stocké se périmerait en silence.

### `GET /api/superviseur/cohortes`

Les cohortes du département. Paramètre optionnel `arrondissement`. Sert l'écran
de paramètres : sans cette liste, le superviseur n'aurait aucun moyen de
désigner la cohorte dont il veut changer le plafond.

```json
{
  "cohortes": [{
    "id": 1,
    "libelle": "Ebolowa II — groupe du mardi",
    "arrondissement": "Ebolowa II",
    "date_debut": "2026-07-07",
    "facilitateur": "Ndzana Étienne",
    "effectif": 20,
    "seances_tenues": 3,
    "ratio_max": 20,
    "places_restantes": 0,
    "effectif_au_dela_du_plafond": 0
  }]
}
```

### `GET /api/superviseur/rapport?annee=2026&trimestre=3`

Le livrable trimestriel. `annee` et `trimestre` (1–4) sont obligatoires.

```json
{
  "periode": { "annee": 2026, "trimestre": 3, "du": "2026-07-01", "au": "2026-09-30" },
  "synthese": {
    "seances_tenues": 3, "cohortes_actives": 1, "facilitateurs_ayant_anime": 1,
    "facilitateurs_formes": 14, "facilitateurs_actifs": 7,
    "dose_moyenne_par_parent": 2.7, "delai_moyen_remontee_jours": 3.7,
    "ecarts_total": 2
  },
  "cohortes": [{ "libelle": "…", "arrondissement": "Ebolowa II", "ratio_max": 20,
                 "effectif": 20, "places_restantes": 0, "seances_tenues": 3 }],
  "facilitateurs": [{
    "nom": "Ndzana Étienne", "arrondissement": "Ebolowa II", "seances": 3,
    "sequences_declarees_realisees": 13, "ecarts": 2,
    "declarees_jamais_ouvertes": 1, "ouvertes_declarees_non_faites": 1,
    "delai_moyen_remontee_jours": 3.7
  }]
}
```

`dose_moyenne_par_parent` compte le **rattrapage par binôme** comme une séance
reçue : un parent rattrapé a bien reçu la séance, autrement.

### `PATCH /api/superviseur/cohortes/{cohorte}`

```json
{ "ratio_max": 10 }
```

**200**

```json
{
  "cohorte": { "id": 1, "libelle": "…", "ratio_max": 10, "effectif": 20,
               "places_restantes": 0, "effectif_au_dela_du_plafond": 10 },
  "modification": { "ratio_max": { "avant": 20, "apres": 10 } }
}
```

Aucun `20` n'existe dans le code : la migration ne donne même pas de valeur par
défaut à `ratio_max`. Baisser le plafond sous l'effectif inscrit ne supprime
personne — c'est signalé par `effectif_au_dela_du_plafond`, pas corrigé dans le
dos du superviseur.

---

## Espace parent

Ability `parent`. Espace **secondaire et optionnel** : la majorité des parents
du programme n'y accédera jamais et sera servie par la séance, le binôme et la
radio.

Le paramètre `langue` (`fr`, `en`, `bulu`) est accepté partout ; à défaut, la
langue préférée du parent s'applique. Quand la langue demandée n'existe pas, la
réponse **le dit** (`langue_servie`) au lieu de le masquer.

### `GET /api/parent/modules`

```json
{ "modules": [{ "id": 8, "numero": 8, "titre": "Discipline positive",
                "unites": 6, "renseigne": true }] }
```

### `GET /api/parent/modules/{module}/unites`

### `GET /api/parent/unites/{unite}?langue=bulu&modalite=texte_picto`

`modalite` ∈ `audio` (défaut), `texte_picto`. La bascule audio ↔ texte +
pictogrammes se fait en changeant ce seul paramètre.

```json
{
  "id": 3,
  "message_cle": "Frapper un enfant lui apprend que celui qui est le plus fort a raison.",
  "reference": "Module 8 — Discipline positive, séquence 3",
  "langue_demandee": "bulu", "langue_servie": "bulu", "modalite": "texte_picto",
  "realisation": {
    "titre": "…", "contenu_texte": "…",
    "fichier_audio": null, "audio_disponible": false,
    "pictogrammes": ["main-barree", "deux-enfants", "balance"]
  },
  "modalites_disponibles": ["audio", "texte_picto"]
}
```

### `GET /api/parent/feuilletons?langue=bulu`

Épisodes numérotés, avec `duree_secondes` et `duree_lisible`.

**Aucune position de lecture n'est envoyée ni stockée.** La reprise (« il
reprend au bon endroit ») vit dans le navigateur du parent : un historique de
consultation côté serveur serait une trace consultable par un autre membre du
foyer, et ne sert à rien au programme.

### `GET /api/parent/questions`

Les trois questions de la semaine.

```json
{ "questions": [{
  "id": 1,
  "enonce": "Votre enfant de cinq ans renverse un seau d'eau…",
  "enonce_audio": "http://…/audio/questions/q1-fr.wav",
  "options": [{ "id": 1, "libelle": "Je le gronde tout de suite", "pictogramme": "visage-fache" }]
}] }
```

Le champ `est_attendue` des options **ne sort jamais** de cette API. Il existe
pour l'analyse du programme, il est masqué sur le modèle, et un test vérifie
qu'il n'apparaît pas dans la réponse.

### `POST /api/parent/questions/{question}/reponse`

```json
{ "option_id": 2 }
```

**200** — la réponse contient exactement trois champs, et jamais un de plus :

```json
{
  "question_id": 1,
  "explication": "Le programme propose de montrer d'abord le geste attendu…",
  "reference": "Module 8 — Discipline positive, séquence 2"
}
```

Ni score, ni total, ni verdict, ni bonne réponse. `explication` est portée par
la **question**, pas par l'option : le texte lu est le même quel que soit le
choix du parent.

Le compteur agrégé est incrémenté dans `reponses_agregees`. On sait combien de
parents ont choisi une option, **jamais lesquels** : cette table ne comporte
aucun `parent_id`.

### `GET /api/parent/situations`

Les situations fréquentes, pour le parent qui ne sait pas écrire. Ce ne sont pas
des réponses pré-écrites : leurs libellés passent par le même appariement que le
texte libre, et plusieurs ne trouvent rien.

### `POST /api/parent/assistant`

```json
{ "texte": "mon enfant recommence dès que j'ai le dos tourné", "langue": "fr" }
```

ou, depuis la liste guidée :

```json
{ "situation_id": 3 }
```

**Aucun modèle de langage génératif n'intervient.** Le texte est comparé aux
`message_cle` des unités digitales ; au-dessus du seuil, l'unité est restituée
**mot pour mot**, avec sa référence.

**200 — trouvé**

```json
{
  "trouve": true, "score": 0.773, "seuil": 0.3,
  "reponse": "Un enfant qui a peur obéit devant vous, et recommence dès que vous avez le dos tourné.",
  "reference": "Module 8 — Discipline positive, séquence 2",
  "module": { "numero": 8, "titre": "Discipline positive" },
  "texte": "…", "pictogrammes": ["…"],
  "fichier_audio": "http://…/audio/unites/m08-u2-fr.wav"
}
```

**200 — refus.** Ce n'est pas une erreur, c'est un résultat. Statut 200, pas 404.

```json
{
  "trouve": false, "score": 0.092, "seuil": 0.3,
  "message": "Je n'ai pas de réponse validée à cette question. Un facilitateur pourra vous aider.",
  "contacts": [{ "nom": "Ndzana Étienne", "telephone": "699 41 27 08",
                 "arrondissement": "Ebolowa II" }]
}
```

`contacts` n'est **jamais vide** : si l'arrondissement du parent n'a aucun
facilitateur actif, la liste s'élargit au département. Un refus sans contact
serait une impasse.

**Le seuil.** Réglable dans `config/mvoe.php` (`MVOE_SEUIL_ASSISTANT`), à 0.30.
Le score vaut entre 0 et 1 : « quelle part de la question cette unité
couvre-t-elle ». Calibré sur les douze situations fréquentes avec six unités en
base — questions couvertes : 0.333 à 1.000 ; questions hors corpus : 0.000 à
0.119. Vérifiable par `php artisan mvoe:assistant`.

Chaque interrogation est journalisée dans `appariements` **sans `parent_id`,
sans identifiant de session**. Ce journal sert à repérer ce que le corpus ne
couvre pas encore, jamais à profiler quelqu'un.

---

## Annuaire public

**Aucun compte n'est nécessaire.** C'est le seul endroit du système où un
inconnu obtient quelque chose, et c'est voulu : quelqu'un qui a besoin d'un
contact humain ne doit pas d'abord se connecter.

### `GET /api/arrondissements`

### `GET /api/annuaire?arrondissement=Ebolowa+II`

```json
{
  "arrondissement": "Mengong",
  "repli_departement": true,
  "message": "Aucun facilitateur n'est actif dans cet arrondissement. Voici les facilitateurs les plus proches.",
  "contacts": [{ "nom": "…", "telephone": "…", "arrondissement": "…" }]
}
```

L'arrondissement demandé **n'est jamais enregistré** : pas de journal, pas de
compteur, pas de trace. Savoir qu'une personne d'un arrondissement donné a
cherché de l'aide est déjà une information de trop.

Seuls les facilitateurs **actifs** sont proposés : afficher le numéro de
quelqu'un qui n'anime plus serait pire que rien. `contacts` n'est jamais vide.

---

## Erreurs

| Code | Sens |
|---|---|
| 401 | Aucun jeton, jeton inconnu, ou jeton expiré |
| 403 | Mauvais rôle, ou ressource qui n'appartient pas au porteur |
| 404 | Ressource inexistante |
| 422 | Validation ; identifiants qui ne correspondent pas |
| 429 | Limite de débit atteinte |

Les erreurs de validation suivent le format Laravel :

```json
{ "message": "…", "errors": { "code_parent": ["…"] } }
```

Un **refus de l'assistant n'est pas une erreur** et répond 200.

---

## Ce que l'API ne fait pas

Ces absences sont des décisions de conception. Ne les comblez pas.

- **Aucun message sortant vers un parent.** Pas de notification, pas de rappel,
  pas de relance, aucun endpoint qui en émettrait. Le parent vient au système ;
  le système ne va jamais vers lui.
- **Aucun canal SMS, USSD ou vocal.**
- **Aucun appel à un modèle de langage génératif.** L'assistant retrouve, il ne
  rédige pas.
- **Aucune alerte automatique de maltraitance.** L'API affiche un contact
  humain ; elle ne signale rien à personne.
- **Aucun échange entre parents.** Le soutien entre pairs passe par le binôme
  physique.
- **Aucun score, badge ou série** dans l'espace parent.
- **Aucun nom de parent ni d'enfant**, nulle part, dans aucune réponse. Les
  seules personnes nommées sont les facilitateurs, qui sont des agents publics.
- **Aucune date de naissance d'enfant** : une tranche d'âge suffit.
- **Aucun tableau de bord temps réel.** Le livrable du superviseur est un
  document trimestriel.
