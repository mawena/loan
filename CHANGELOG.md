# Changelog

Toutes les modifications notables de ce projet sont documentées dans ce fichier.

Le format suit [Keep a Changelog](https://keepachangelog.com/fr/1.0.0/) et ce projet
respecte le [Semantic Versioning](https://semver.org/lang/fr/).

## [1.1.3] - 2026-07-03

### Fixed

- Redirection automatique vers la page de connexion (`/login`) lorsqu'une réponse API renvoie
  **401 Non authentifié** : la session locale (cookies `accessToken`, `userData`, `userAbilityRules`)
  est purgée et la page demandée est conservée dans `?to=` pour y revenir après reconnexion. L'ancienne
  implémentation appelait `useRouter()` hors du contexte d'un composant, ce qui empêchait toute
  redirection.
- Redirection automatique vers la page de changement de mot de passe (`/account/security`) lorsqu'une
  réponse API renvoie **403 avec `errors.password_change_required`** (`sub_code` `002` du middleware
  `AccountStatusMiddleware`) ; le cookie `userData` est mis à jour pour refléter l'obligation de
  changement.

### Changed

- La gestion des erreurs 401/403 est centralisée dans `handleApiError` (`resources/js/utils/api.js`),
  partagée par les deux clients HTTP `$api` (ofetch) et `useApi` (VueUse `createFetch`).

## [1.1.2] - 2026-07-02

### Changed

- Mise à jour de la librairie `mawena/maravel` vers la version `4.1.0`.

## [1.1.1] - 2026-06-19

### Added

- Composant générique `FieldRenderer` (`resources/js/components/FieldRenderer.vue`) qui rend un champ
  de formulaire (texte, email, mot de passe, textarea, tiptap, date, liste de valeurs, select, boolean,
  checkbox, fichier) à partir d'une simple définition `{ value_key, type, label, ... }`, avec gestion des
  règles de validation et des erreurs serveur.
- Composable `useClientTable` (`resources/js/composables/useClientTable.js`) qui gère le tri et la
  pagination côté frontend d'une liste déjà filtrée, pour alimenter `VDataTableServer` sans appel réseau
  supplémentaire. Expose également `matchesSearch` pour la recherche texte multi-champs.
- Filtres dédiés sur les pages d'administration : rôle/statut sur **Utilisateurs**, type (super
  administrateur/standard) sur **Rôles**, action/sujet sur **Permissions**.
- Bouton **Recharger** sur les pages `admin/users`, `admin/roles` et `admin/permissions` pour relancer
  manuellement le chargement des données du tableau (icône animée pendant le chargement).
- Animation de disparition (fondu + léger zoom arrière) de la ligne concernée lors d'une suppression dans
  ces 3 tableaux, au lieu d'une disparition instantanée au rechargement de la liste.

### Changed

- Pages `admin/users`, `admin/roles` et `admin/permissions` : la recherche, le tri et la pagination des
  tableaux se font désormais entièrement côté frontend (un seul appel API avec `paginate=false` au
  chargement, plus aucune requête réseau lors d'une saisie de recherche, d'un changement de page ou de
  tri). `VDataTableServer` est conservé comme composant d'affichage.
- Les formulaires d'ajout/édition de ces 3 pages sont désormais pilotés par une liste de champs rendue via
  `FieldRenderer`, au lieu de champs Vuetify codés en dur dans chaque template.

## [1.0.1] - 2026-06-15

### Fixed

- Seeder admin en échec sur MySQL : `User::firstOrCreate()` n'incluait pas le mot de passe (colonne
  `NOT NULL` sans valeur par défaut), ce qui faisait échouer `migrate:fresh --seed` (SQLSTATE 1364).

## [1.0.0] - 2026-06-15

### Added

- Préparation du package pour publication sur Packagist sous le nom `mawena/maravel-xy`.

## [0.1.0] - 2026-05-26

### Added

- Première version du template : authentification Sanctum, gestion des utilisateurs (CRUD), système de
  rôles et permissions (RBAC) avec CASL, frontend Vue 3 + Vuetify, backend Laravel.
