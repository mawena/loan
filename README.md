# Loan

Application web de simulation de crédit en francs CFA (XOF) : tableau d'amortissement, comparaison d'offres, remboursement anticipé et capacité d'emprunt, avec quotas journaliers, historique, exports PDF / Word / Excel et partage par lien.

Le projet est issu du template Maravel XY (`"name": "mawena/maravel-xy"` dans `composer.json`).

## Fonctionnalités

### Simulateur de prêt (`/simulateur`)

- Paramètres : montant (de 1 000 à 100 000 000 000 FCFA), taux annuel (0 à 100 %), durée (1 à 600 mois), méthode d'amortissement, taux d'assurance annuel facultatif (0 à 20 %, appliqué au capital initial), mois de la première échéance et nom de la simulation, tous deux facultatifs.
- Trois méthodes d'amortissement :
  - **annuités constantes** (`annuity`) : mensualité fixe ;
  - **amortissement constant** (`constant_capital`) : part de capital fixe, mensualités décroissantes ;
  - **in fine** (`in_fine`) : intérêts seuls chaque mois, capital remboursé à la dernière échéance.
- Le calcul est fait côté serveur par `App\Services\LoanCalculator`, qui sert de référence pour l'affichage comme pour les exports. Le résultat comprend :
  - un résumé : mensualité, première et dernière échéance, total des intérêts, total de l'assurance, coût total du crédit, total remboursé, date de fin ;
  - un graphique de répartition du coût (capital, intérêts, assurance) et une courbe du solde restant dû ;
  - le tableau d'amortissement mois par mois : capital, intérêts, assurance, mensualité, solde restant.
- Chaque calcul est enregistré dans la table `simulations` et décompté du quota du jour.
- Le formulaire peut être prérempli par la query string (`amount`, `rate`, `months`, `method`), ce que fait le mini-simulateur de la page d'accueil.

### Quotas journaliers

Définis dans `App\Services\QuotaService` :

| Profil | Simulations par jour |
|---|---|
| Visiteur anonyme (suivi par adresse IP) | 2 |
| Utilisateur connecté | 5 |
| Utilisateur premium (`users.is_premium = true`) | illimité |

Une fois le quota atteint, l'API répond `429`. L'endpoint `GET /api/simulations/quota` renvoie le profil, la limite, le nombre de simulations utilisées et le nombre restant.

L'offre Premium (1 500 FCFA/mois) est annoncée « bientôt disponible » sur la page d'accueil. Aucun paiement n'est implémenté, et aucune interface ni route API ne modifie `is_premium` : il faut changer cette colonne directement en base.

### Outils interactifs (calcul dans le navigateur, sans quota ni enregistrement)

Ces pages font leurs calculs avec `resources/js/utils/loan.js` :

- **Page d'accueil** (`/`) : mini-simulateur à résultat instantané, présentation des fonctionnalités, tarifs, FAQ.
- **Comparateur** (`/comparateur`) : de 2 à 3 offres côte à côte (montant, taux, durée, méthode). Il affiche la première mensualité, le total des intérêts et le coût total de chaque offre, signale la moins chère avec l'économie réalisée, et trace un graphique comparatif.
- **Remboursement anticipé** (`/remboursement-anticipe`) : sur un prêt en annuités constantes, simule un remboursement partiel ou total à une échéance donnée, au choix pour réduire la durée ou pour réduire la mensualité. Il affiche les intérêts économisés, les mois gagnés et la courbe du solde avant et après. Les indemnités de remboursement anticipé ne sont pas prises en compte.
- **Capacité d'emprunt** (`/capacite-emprunt`) : à partir des revenus, des charges, du taux et de la durée, donne la mensualité maximale (taux d'endettement plafonné à 40 %), le capital empruntable et le taux d'endettement actuel, avec un graphique de la capacité selon la durée (de 5 à 30 ans).

### Espace connecté

- **Inscription** (`/register`) et **connexion** (`/login`) par jeton Laravel Sanctum. Chaque compte créé reçoit le rôle `client`.
- **Historique** (`/mes-simulations`) : liste des simulations de l'utilisateur avec recherche par nom, filtre par méthode, tri et pagination. Chaque ligne permet l'export rapide ou la suppression. La page de détail (`/mes-simulations/{id}`) affiche le résultat complet.
- **Exports**, réservés au propriétaire connecté :
  - PDF, via `barryvdh/laravel-dompdf` et la vue `resources/views/exports/simulation-pdf.blade.php` ;
  - Word (`.docx`), via `phpoffice/phpword` ;
  - Excel (`.xlsx`), via `phpoffice/phpspreadsheet`.

  Le fichier porte le nom de la simulation (slugifié), ou `simulation-pret-{id}` si elle n'en a pas.
- **Partage par lien** : le propriétaire génère un jeton aléatoire de 40 caractères. La simulation devient consultable en lecture seule à l'adresse `/s/{token}`, sans les données du propriétaire, et le lien peut être révoqué à tout moment.
- **Sécurité du compte** (`/account/security`) : changement de mot de passe.

### Administration (héritée du template)

Les pages `/admin/users`, `/admin/roles` et `/admin/permissions` gèrent les utilisateurs, les rôles et les permissions (librairie `mawena/maravel`). L'accès est contrôlé côté frontend par CASL, à partir des `ability_rules` calculées depuis les rôles de l'utilisateur.

## Stack technique

- **Backend** : PHP 8.2 ou plus, Laravel 12, Laravel Sanctum 4, `mawena/maravel` 4.1 (rôles et permissions, policies, middleware `account.status`), `barryvdh/laravel-dompdf`, `phpoffice/phpword`, `phpoffice/phpspreadsheet`.
- **Frontend** : Vue 3, Vuetify 3 (base du template Vuexy), Vite 7 avec `laravel-vite-plugin`, routage par fichiers (`unplugin-vue-router`, pages dans `resources/js/pages`), Pinia, CASL, ApexCharts (`vue3-apexcharts`), ofetch.
- **Base de données** : MySQL dans `.env.example`, SQLite en mémoire pour les tests (`phpunit.xml`).
- **Gestionnaire de paquets JavaScript** : pnpm (`pnpm-lock.yaml`).

## Prérequis

- PHP 8.2 ou plus, avec les extensions demandées par les dépendances, dont `gd`, `zip`, `dom`, `xml` et `mbstring` (pour PhpSpreadsheet, PhpWord et dompdf), et `pdo_sqlite` pour lancer les tests.
- Composer.
- Node.js (`.nvmrc` : `lts/*`) et pnpm.
- Un serveur MySQL, ou Docker pour utiliser l'environnement Laravel Sail décrit dans `compose.yaml`.

## Installation

```bash
composer install
cp .env.example .env
php artisan key:generate
# Renseigner les variables DB_* dans .env, puis :
php artisan migrate --seed
pnpm install
```

- `--seed` exécute `database/seeders/DatabaseSeeder.php`. Ce seeder appelle `RolePermissionSeeder` (rôles `admin` et `client`, permissions de base, dont celles sur les simulations) et crée un compte administrateur de test (voir le fichier du seeder). Il faut l'exécuter au moins une fois : l'inscription attribue le rôle `client`, qui doit exister.
- `pnpm install` déclenche le script `postinstall` (`build:icons` puis `msw:init`).

`composer.json` contient aussi un script `composer run setup`, qui enchaîne `composer install`, la copie de `.env`, la génération de la clé, `php artisan migrate --force`, `npm install` et `npm run build`. Il passe par npm plutôt que par pnpm et n'exécute pas les seeders.

### Lancer en développement

```bash
composer run dev
```

Ce script lance en parallèle, via `npx concurrently`, le serveur Laravel (`php artisan serve`), l'écoute de la file (`php artisan queue:listen --tries=1`), les logs (`php artisan pail`) et Vite (`npm run dev`).

Autre possibilité, dans deux terminaux :

```bash
php artisan serve
pnpm dev
```

L'application est servie par Laravel ; le serveur Vite (port 5173, voir `vite.config.js`) ne fournit que les assets.

### Avec Docker (Laravel Sail)

`compose.yaml` définit un environnement Sail : application PHP 8.4, MySQL 8.0, Redis, Selenium et Mailpit. Une fois les dépendances Composer installées :

```bash
./vendor/bin/sail up -d
```

## Configuration

Variables d'environnement principales (voir `.env.example`) :

| Variable | Rôle |
|---|---|
| `APP_NAME` | Nom repris dans l'en-tête des exports PDF, Word et Excel |
| `APP_URL` | Base des liens de partage générés (`/s/{token}`) |
| `APP_KEY` | Clé de l'application (`php artisan key:generate`) |
| `APP_LOCALE`, `APP_FALLBACK_LOCALE`, `APP_FAKER_LOCALE` | Langue de l'application |
| `DB_CONNECTION`, `DB_HOST`, `DB_PORT`, `DB_DATABASE`, `DB_USERNAME`, `DB_PASSWORD` | Connexion à la base de données |
| `SESSION_DRIVER`, `CACHE_STORE`, `QUEUE_CONNECTION` | Pilotes de session, de cache et de file (tables créées par les migrations Laravel par défaut) |
| `VITE_API_BASE_URL` | URL de base de l'API côté frontend (`/api` par défaut) |

`.env.example` contient aussi des variables standard de Laravel ou héritées du template, que le code propre au projet n'utilise pas : `MAIL_*`, `REDIS_*`, `AWS_*`, `VITE_APP_NAME`, `VITE_MAPBOX_ACCESS_TOKEN`.

Les limites métier sont écrites dans le code et ne passent pas par l'environnement :

- quotas : constantes `GUEST_DAILY_LIMIT` et `FREE_DAILY_LIMIT` de `QuotaService` ;
- taux d'endettement de 40 % : `LoanCalculator::borrowingCapacity()` et `capacite-emprunt.vue` ;
- nom affiché dans l'interface : `themeConfig.js`.

## Utilisation

### Pages

| Route | Accès | Contenu |
|---|---|---|
| `/` | public | Accueil, mini-simulateur, tarifs, FAQ |
| `/simulateur` | public (quota) | Simulation complète enregistrée |
| `/comparateur` | public | Comparaison de 2 à 3 offres |
| `/remboursement-anticipe` | public | Impact d'un remboursement anticipé |
| `/capacite-emprunt` | public | Capacité d'emprunt |
| `/s/{token}` | public | Simulation partagée, en lecture seule |
| `/login`, `/register` | non connecté | Connexion, inscription |
| `/mes-simulations`, `/mes-simulations/{id}` | connecté | Historique et détail |
| `/account/security` | connecté | Changement de mot de passe |
| `/admin/users`, `/admin/roles`, `/admin/permissions` | selon permissions | Administration |

### API (préfixe `/api`)

Routes publiques. Sur les routes de simulation, le jeton Sanctum est lu s'il est fourni, pour appliquer le quota de l'utilisateur connecté :

| Méthode | Route | Description |
|---|---|---|
| `GET` | `/simulations/quota` | État du quota du visiteur |
| `POST` | `/simulations` | Calcule et enregistre une simulation |
| `GET` | `/shared/{token}` | Lecture publique d'une simulation partagée |
| `POST` | `/auth/login` | Connexion (renvoie un jeton) |
| `POST` | `/auth/register` | Inscription (renvoie un jeton) |

Routes authentifiées (`auth:sanctum` ; le middleware `account.status` s'ajoute sur les routes de simulation et d'administration) :

| Méthode | Route | Description |
|---|---|---|
| `GET` | `/simulations` | Historique de l'utilisateur |
| `GET` | `/simulations/{id}` | Détail d'une simulation |
| `DELETE` | `/simulations/{id}` | Suppression |
| `GET` | `/simulations/{id}/export/{format}` | Export, `format` = `pdf`, `word` ou `excel` |
| `POST` | `/simulations/{id}/share` | Génère (ou renvoie) le lien de partage |
| `DELETE` | `/simulations/{id}/share` | Révoque le lien de partage |
| `GET` | `/auth/data` | Utilisateur connecté |
| `DELETE` | `/auth/logout` | Déconnexion |
| `PUT` | `/users/update-password` | Changement de mot de passe |
| — | `/users`, `/roles`, `/permissions` | CRUD d'administration (`apiResource`) |

Toutes les autres URL sont renvoyées vers l'application Vue (`routes/web.php`). Laravel expose aussi la route de santé `/up`.

## Structure du projet

Seuls les éléments propres au projet sont listés ; le reste suit la structure Laravel et Vuexy du template.

```
app/
├── Http/Controllers/API/
│   ├── SimulationController.php   # Simulations : calcul, quota, historique, exports, partage
│   └── AuthController.php         # Connexion, inscription, déconnexion
├── Models/Simulation.php          # Paramètres et résultats en JSON, share_token, ip_address
├── Policies/SimulationPolicy.php  # Droits sur les simulations
└── Services/
    ├── LoanCalculator.php         # Moteur de calcul (amortissement, capacité d'emprunt)
    ├── QuotaService.php           # Quotas journaliers
    └── SimulationExportService.php # Exports PDF, Word et Excel
database/
├── migrations/                    # Dont create_simulations_table et add_is_premium_to_users_table
└── seeders/                       # DatabaseSeeder, RolePermissionSeeder
resources/
├── js/
│   ├── pages/                     # simulateur, comparateur, capacite-emprunt,
│   │                              # remboursement-anticipe, mes-simulations/, s/[token], register…
│   ├── views/front/               # Barre de navigation et pied de page publics
│   ├── views/simulations/SimulationResult.vue # Affichage d'un résultat (graphiques, tableau, exports, partage)
│   └── utils/loan.js              # Calculs côté navigateur pour les outils interactifs
└── views/exports/simulation-pdf.blade.php # Gabarit de l'export PDF
routes/
├── api.php
└── web.php
tests/
├── Unit/LoanCalculatorTest.php
└── Feature/SimulationApiTest.php
docs/superpowers/specs/2026-07-07-loan-simulator-design.md # Document de conception
```

## Tests

```bash
php artisan test
```

ou, pour vider d'abord le cache de configuration :

```bash
composer test
```

Les tests utilisent une base SQLite en mémoire (`phpunit.xml`) et couvrent :

- `tests/Unit/LoanCalculatorTest.php` : les trois méthodes d'amortissement, le taux nul, l'assurance, la cohérence des totaux, la capacité d'emprunt et le rejet d'une méthode inconnue ;
- `tests/Feature/SimulationApiTest.php` : les quotas (visiteur, connecté, premium), la validation, l'historique, l'interdiction d'accéder aux simulations d'autrui, la suppression, les exports dans les trois formats et le cycle de vie du lien de partage.

Le frontend n'a pas de tests automatisés. Pour le linter :

```bash
pnpm lint
```

Attention : cette commande lance ESLint avec `--fix` et corrige donc les fichiers.

## Build / Déploiement

```bash
pnpm build
```

Les assets compilés sont versionnés dans le dépôt (`public/build`, avec `manifest.json`). Il faut relancer le build après toute modification du frontend. Le dépôt ne contient ni Dockerfile de production ni script de déploiement ; `compose.yaml` décrit l'environnement de développement Sail.

## Licence

Code publié sous licence MIT : voir [LICENSE](LICENSE).
