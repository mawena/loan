# Maravel XY

[![Version](https://img.shields.io/badge/version-1.1.3-blue.svg)](https://github.com/mawena/maravel-xy/releases)
![PHP](https://img.shields.io/badge/php-%5E8.2-777BB4.svg)
![Laravel](https://img.shields.io/badge/laravel-%5E12.0-FF2D20.svg)
![Vue](https://img.shields.io/badge/vue-3.x-4FC08D.svg)
![License](https://img.shields.io/badge/license-MIT-green.svg)

Un template minimaliste conçu pour faciliter le démarrage rapide de nouvelles applications web avec une gestion utilisateurs complète et une authentification sécurisée.

## Table des matières

- [Maravel XY](#maravel-xy)
  - [Table des matières](#table-des-matières)
  - [Caractéristiques](#caractéristiques)
  - [Structure du Projet](#structure-du-projet)
  - [Installation](#installation)
    - [Prérequis](#prérequis)
    - [Setup](#setup)
  - [Développement](#développement)
    - [Démarrer le serveur de développement](#démarrer-le-serveur-de-développement)
    - [Identifiants de test](#identifiants-de-test)
  - [Ajouter une nouvelle fonctionnalité](#ajouter-une-nouvelle-fonctionnalité)
    - [1. Backend (API)](#1-backend-api)
    - [2. Frontend (Vue)](#2-frontend-vue)
    - [3. Liste avec filtres + formulaire dynamique](#3-liste-avec-filtres--formulaire-dynamique)
  - [Gestion globale des erreurs API](#gestion-globale-des-erreurs-api)
  - [Build pour la production](#build-pour-la-production)
  - [Tests](#tests)
  - [Publier une nouvelle version](#publier-une-nouvelle-version)
  - [Ressources](#ressources)
  - [Support](#support)
  - [Changelog](#changelog)
  - [License](#license)

## Caractéristiques

- ✅ **Authentification sécurisée** avec Laravel Sanctum
- ✅ **Gestion des utilisateurs** complète (CRUD)
- ✅ **Système de rôles et permissions** avec CASL
- ✅ **Frontend moderne** avec Vue 3 + Vuetify
- ✅ **Tableaux** avec recherche/tri/pagination 100% frontend (`VDataTableServer` + `useClientTable`), bouton
  de rechargement manuel et animation de suppression de ligne
- ✅ **Formulaires d'ajout/édition** générés dynamiquement à partir d'une liste de champs (`FieldRenderer`)
- ✅ **Backend robuste** avec Laravel
- ✅ **Base de données** avec migrations
- ✅ **API RESTful** prête à l'emploi

## Structure du Projet

```
├── app/
│   ├── Http/Controllers/Admin/  # Contrôleurs API
│   ├── Models/                   # Modèles Eloquent
│   └── Providers/                # Service providers
├── database/
│   ├── migrations/               # Migrations de base de données
│   └── seeders/                  # Seeders pour les données initiales
├── resources/
│   └── js/
│       ├── pages/                # Pages Vue (routing automatique)
│       ├── components/           # Composants réutilisables
│       ├── layouts/              # Layouts de l'application
│       └── stores/               # État (Pinia)
├── routes/api.php                # Routes API
└── public/                        # Fichiers publics
```

## Installation

### Prérequis

- PHP 8.2+
- Node.js 18+
- Composer
- npm/pnpm/yarn

### Setup

```bash
# Installer les dépendances PHP
composer install

# Installer les dépendances JavaScript
pnpm install

# Copier le fichier d'environnement
cp .env.example .env

# Générer la clé d'application
php artisan key:generate

# Exécuter les migrations
php artisan migrate

# Seeder les utilisateurs de test
php artisan db:seed
```

## Développement

### Démarrer le serveur de développement

```bash
# Terminal 1: Backend (Laravel)
php artisan serve

# Terminal 2: Frontend (Vite)
pnpm dev
```

L'application sera accessible à `http://localhost:5173`

### Identifiants de test

- **Admin:**
  - Email: `admin@example.com`
  - Password: `password`

- **User:**
  - Email: `user@example.com`
  - Password: `password`

## Ajouter une nouvelle fonctionnalité

### 1. Backend (API)

Créer un nouveau modèle et contrôleur:

```bash
php artisan make:model Produit -m
php artisan make:controller Api/ProduitController --model=Produit --api
```

Ajouter les routes dans `routes/api.php`:

```php
Route::apiResource('produits', ProduitController::class);
```

### 2. Frontend (Vue)

Créer une nouvelle page dans `resources/js/pages/admin/produits.vue`:

```vue
<script setup>
definePage({
  meta: {
    action: 'manage',
    subject: 'all',
  },
})

const produits = ref([])

onMounted(async () => {
  const { data } = await useApi('/admin/produits')
  produits.value = data
})
</script>

<template>
  <!-- Votre template -->
</template>
```

Le routing est automatique basé sur le chemin du fichier!

### 3. Liste avec filtres + formulaire dynamique

Les pages `admin/users`, `admin/roles` et `admin/permissions` montrent le pattern à reproduire pour une
nouvelle ressource :

- **Liste** : un seul appel API avec `paginate: false` au montage, puis un `computed` qui applique la
  recherche/les filtres, et le composable `useClientTable(filteredItems)` pour le tri/la pagination
  (alimente `VDataTableServer` via `page`, `itemsPerPage`, `updateOptions`, `paginatedItems`,
  `totalItems` — aucun appel réseau supplémentaire).
- **Formulaire** : une liste de champs (`value_key`, `type`, `label`, `required`, `cols`, `data`, `show`)
  rendue avec `<FieldRenderer v-model="form[field.value_key]" :field="field" />` dans une boucle
  `v-for` sur `VCol`, plutôt que des champs Vuetify codés en dur. Types supportés : `text`, `number`,
  `email`, `password`, `textarea`, `tiptap`, `date`, `lov` (liste de valeurs/autocomplete), `select`,
  `boolean`, `checkbox`, `file`.
- **Rechargement + animation de suppression** : un bouton "Recharger" (`:loading="loading"` +
  `@click="fetchX"`) relance le chargement de la liste. À la suppression, l'id de la ligne est ajouté à un
  `Set` réactif `deletingIds`, exposé au tableau via `:row-props` (classe CSS `row-removing` définie dans
  le `<style scoped>` du composant) ; le rechargement de la liste est différé de la durée de l'animation
  (`ROW_REMOVE_ANIMATION_DURATION`) pour laisser la ligne disparaître en fondu avant de retirer la donnée.

## Gestion globale des erreurs API

Les deux clients HTTP du frontend — `$api` (`resources/js/utils/api.js`, basé sur ofetch) et `useApi`
(`resources/js/composables/useApi.js`, basé sur `createFetch` de VueUse) — partagent une gestion
centralisée des erreurs via `handleApiError(status, errors)` exportée par `resources/js/utils/api.js` :

- **401 Non authentifié** : la session locale est purgée (cookies `accessToken`, `userData`,
  `userAbilityRules`) et l'utilisateur est redirigé vers la page de connexion (`/login`). La page en
  cours est conservée dans le paramètre `?to=` afin d'y revenir automatiquement après reconnexion.
- **403 avec `errors.password_change_required`** (`sub_code` `002`, renvoyé par le middleware
  `AccountStatusMiddleware` du package Maravel) : l'utilisateur est redirigé vers la page de changement
  de mot de passe (`/account/security`), seule page — avec la déconnexion — autorisée par le backend
  tant que le mot de passe n'a pas été changé. Le cookie `userData` est mis à jour
  (`password_change_required: true`) pour que l'interface reflète cet état.

Les redirections utilisent l'instance `router` importée depuis `@/plugins/1.router` (et non
`useRouter()`, qui n'est pas disponible hors du contexte d'un composant).

Les hooks d'erreur définis localement sur un appel (par exemple `onResponseError` pour afficher les
erreurs de validation d'un formulaire) restent exécutés : ofetch ≥ 1.4 chaîne les hooks globaux et
locaux au lieu de les remplacer.

## Build pour la production

```bash
# Compiler et minifier
pnpm build

# Build du backend
```

## Tests

```bash
# Tests PHP
php artisan test

# Tests JavaScript
pnpm test
```

## Publier une nouvelle version

1. Mettre à jour [CHANGELOG.md](CHANGELOG.md) avec une entrée `## [x.y.z] - AAAA-MM-JJ`.
2. Lancer le script de publication, qui commit/push les changements puis crée et push le tag `vx.y.z` :

```bash
./pusher.sh 1.2.0 "Description du changement"
```

## Ressources

- [Documentation Laravel](https://laravel.com/docs)
- [Documentation Vue 3](https://vuejs.org/)
- [Documentation Vuetify](https://vuetifyjs.com/)
- [Documentation Vite](https://vitejs.dev/)

## Support

Pour des questions ou des problèmes, consultez la page d'accueil de l'application qui contient un guide complet d'implémentation.

## Changelog

Voir [CHANGELOG.md](CHANGELOG.md) pour l'historique des versions.

## License

MIT
