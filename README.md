# Zandu Sales Manager

Zandu Sales Manager est une plateforme de gestion commerciale multi-tenant conçue pour permettre à une organisation de gérer plusieurs magasins depuis un même système.

La plateforme couvre progressivement :

- l'administration des organisations et magasins ;
- les utilisateurs, memberships, rôles et permissions ;
- le catalogue produit ;
- la tarification ;
- les stocks et leur valorisation ;
- les achats et réceptions fournisseur ;
- les transferts inter-magasins ;
- les inventaires physiques ;
- les caisses ;
- les ventes ;
- les retours et remboursements ;
- l'audit et la traçabilité des opérations.

Le projet suit une architecture en **monolithe modulaire inspirée du Domain-Driven Design (DDD)** avec une forte isolation des bounded contexts et des tenants.

---

## État du projet

| Lot | Périmètre                         | État                              |
| --- | --------------------------------- | --------------------------------- |
| 0   | Architecture exécutable           | Terminé                           |
| 1   | Administration opérationnelle     | Terminé                           |
| 2   | Catalogue et tarification de base | Terminé                           |
| 3   | Fondations Inventory et Cash      | Terminé                           |
| 4   | Sales et `CompleteSale` cash      | Terminé — Gate M2 validé          |
| 5   | Inventory Costing et Returns      | Terminé — Gate CI validé          |
| 6   | Purchasing et Goods Receipts      | Terminé — Gate CI validé          |
| 7   | StockTransfer et StockCount       | Terminé — Gate M3 validé          |
| F0  | Frontend Foundation               | Terminé                           |
| F1  | Admin Stores                      | Terminé — Gate F1 validé          |
| F2  | Admin Users & Access              | Terminé — Gate F2 validé          |
| F3  | Admin Catalog & Pricing           | En cours — F3.1 à F3.41 terminés  |

Les Lots backend 0 à 7, le Frontend Foundation et les Lots Admin F1/F2 sont
clos. L'Admin expose sous `/admin` les parcours Stores, Users & Access, Catalog
et Pricing. F3 couvre le code fonctionnel et les tests unitaires, composants et
intégration ; les scénarios navigateur Catalog/Pricing F3.42/F3.43 restent à
livrer avant de valider le Gate F3. Le POS conserve son shell de fondation.

L'état détaillé de l'implémentation, les Epics terminés, les validations et les preuves de tests sont maintenus dans :

[`IMPLEMENTATION_STATUS.md`](IMPLEMENTATION_STATUS.md)

Les spécifications détaillées des Lots sont disponibles dans :

[`docs/specs/`](docs/specs/)

---

## Stack technique

### Backend

- PHP 8.5
- Symfony 7.4 LTS
- API Platform 4
- Doctrine ORM
- PostgreSQL 18

### Sécurité

- authentification JWT ;
- permissions atomiques ;
- scopes organisation et magasins ;
- isolation multi-tenant ;
- PostgreSQL Row Level Security ;
- audit append-only ;
- outbox transactionnelle.

### Frontend

- workspace pnpm avec lockfile unique ;
- TypeScript strict et React 19 ;
- Admin Web : Next.js 16 ;
- POS : Vite 8 et shell desktop Tauri 2 ;
- TanStack Query pour le cache des données serveur ;
- packages partagés pour l'UI, le formatting, l'authentification et les contextes.

### Qualité

- PHPUnit
- PHPStan
- PHP-CS-Fixer
- Deptrac
- Composer Audit
- fitness tests d'architecture
- ESLint, Prettier et vérification TypeScript côté frontend
- tests Node natifs pour les applications et le socle frontend

### Infrastructure locale

- Docker
- Docker Compose
- GNU Make

---

## Architecture

Zandu utilise un **monolithe modulaire** organisé autour de bounded contexts métier.

Les dépendances suivent principalement la direction :

```text
Presentation
     ↓
Application
     ↓
Domain
```

L'infrastructure fournit les implémentations techniques nécessaires aux couches métier.

Le domaine ne dépend pas directement :

- de Symfony ;
- de Doctrine ;
- d'API Platform ;
- des implémentations Infrastructure.

Les communications entre bounded contexts doivent respecter les contrats et frontières définis par l'architecture.

Les règles complètes sont documentées dans :

- [`docs/specs/architecture/`](docs/specs/architecture/)
- [`docs/ai/ARCHITECTURE_RULES.md`](docs/ai/ARCHITECTURE_RULES.md)

---

## Prérequis

La machine de développement doit disposer de :

- Docker avec le plugin Docker Compose ;
- GNU Make ;
- Git.

PHP, Composer et PostgreSQL n'ont pas besoin d'être installés directement sur la machine hôte.

L'Admin peut être lancé dans Compose sans installation locale de Node.js. Pour
exécuter directement le workspace frontend sur l'hôte, prévoir Node.js 24
(version utilisée en CI) et pnpm 11.25.0, épinglé dans
`frontend/package.json`. Le lancement desktop du POS nécessite également Rust
et les dépendances système de Tauri pour la plateforme hôte.

---

## Installation

Cloner le dépôt puis, depuis sa racine :

```bash
cp .env.example .env

make install
make start
make database-migrate
```

`make install` construit l'image backend, installe les dépendances Composer et génère les clés nécessaires à l'authentification JWT.
`make start` démarre PostgreSQL, l'API Symfony servie par FrankenPHP/Caddy sur
le port `8080` et l'Admin Next.js sur le port `3000` ; aucun serveur PHP ou Node
supplémentaire n'est nécessaire pour ce parcours.

Les données PostgreSQL sont conservées dans le volume Docker prévu à cet effet.

Vérifier ensuite l'environnement :

```bash
make ps

docker compose exec backend php bin/console about

make database-status
```

---

## Démarrer l'API

L'API fait partie de la stack Compose et démarre avec :

```bash
make start
```

Elle est ensuite accessible sur `http://localhost:8080`. Les modifications du
code backend monté dans le conteneur sont prises en compte sans reconstruire
l'image. Utiliser `make logs` pour suivre le serveur et les erreurs applicatives.

---

## Documentation API

Une fois le backend accessible sur le port `8080` :

| Ressource  | URL                                        |
| ---------- | ------------------------------------------ |
| Swagger UI | `http://localhost:8080/api/docs`           |
| ReDoc      | `http://localhost:8080/api/docs?ui=re_doc` |
| Liveness   | `http://localhost:8080/health/live`        |
| Readiness  | `http://localhost:8080/health/ready`       |
| Metrics    | `http://localhost:8080/metrics`            |

Swagger et ReDoc constituent la référence principale pour le contrat HTTP exposé par l'application.

Ces interfaces de documentation sont actives uniquement en environnement de développement.

---

## Démarrer les interfaces frontend

L'Admin fait partie de la stack Compose. Depuis la racine :

```bash
make start
docker compose logs -f admin
```

Il est accessible sur `http://localhost:3000`. Le proxy Next.js relaie `/api/*`
vers `http://backend:8080` sur le réseau Compose. Le port hôte peut être changé
avec `ADMIN_PORT` dans `.env`. Après une modification du frontend, reconstruire
et relancer l'Admin avec `docker compose up -d --build admin`.

Le lancement local reste disponible pour développer l'Admin ou le POS hors
conteneur :

Installer les dépendances depuis le workspace :

```bash
cd frontend
pnpm install --frozen-lockfile
```

Configurer ensuite l'Admin avec `frontend/apps/admin/.env.local` (le fichier
`.env.example` documente les valeurs locales). L'URL publique reste same-origin ;
Next.js relaie `/api/*` vers Symfony via `ZANDU_BACKEND_URL`, sans porter de
logique métier.

Lancer l'Admin ou le POS dans des terminaux séparés, depuis `frontend/` :

```bash
pnpm --filter @zandu/admin dev
pnpm --filter @zandu/pos dev
```

L'Admin est accessible par défaut sur `http://localhost:3000` et le POS navigateur
sur `http://127.0.0.1:1420`. Pour lancer le POS desktop avec ses prérequis installés :

```bash
pnpm --filter @zandu/pos tauri dev
```

L'Admin expose la connexion, la session et les permissions effectives, les
contextes organisation/magasin, ainsi que les parcours Stores, Users & Access,
Catalog et Pricing sous `/admin`. Le POS conserve son shell de fondation.

---

## Premier parcours utilisateur

### 1. Créer un compte et une organisation

```bash
curl --request POST http://localhost:8080/api/auth/register \
  --header 'Content-Type: application/json' \
  --data '{
    "email": "owner@example.com",
    "password": "a-strong-password-for-zandu",
    "organizationName": "Ma société",
    "countryCode": "CG",
    "defaultCurrency": "XAF",
    "defaultTimeZone": "Africa/Brazzaville",
    "defaultLocale": "fr_CG"
  }'
```

La création provisionne atomiquement :

```text
User
  +
Organization
  +
Membership
  +
ORGANIZATION_OWNER
```

---

### 2. Se connecter

```bash
curl --request POST http://localhost:8080/api/auth/login \
  --header 'Content-Type: application/json' \
  --data '{
    "email": "owner@example.com",
    "password": "a-strong-password-for-zandu"
  }'
```

La réponse fournit les jetons nécessaires aux appels authentifiés.

---

### 3. Appeler une ressource protégée

```bash
curl http://localhost:8080/api/organizations/<organization-id> \
  --header 'Authorization: Bearer <access-token>'
```

---

### 4. Renouveler l'access token

```bash
curl --request POST http://localhost:8080/api/auth/refresh \
  --header 'Content-Type: application/json' \
  --data '{
    "refreshToken": "<refresh-token>"
  }'
```

---

## Contrat d'erreurs

L'API utilise un format d'erreur stable contenant notamment :

```json
{
  "code": "NOT_FOUND",
  "message": "The requested resource was not found.",
  "correlationId": "0198e463-147c-72d5-b75a-a936797ff9c8"
}
```

Principales catégories :

| HTTP  | Code                    |
| ----- | ----------------------- |
| `400` | `VALIDATION_ERROR`      |
| `401` | `UNAUTHENTICATED`       |
| `403` | `FORBIDDEN`             |
| `404` | `NOT_FOUND`             |
| `409` | `CONFLICT`              |
| `422` | `DOMAIN_RULE_VIOLATION` |

Une ressource appartenant à un autre tenant est volontairement exposée comme inexistante afin de ne pas révéler sa présence.

Le contrat détaillé est disponible dans la documentation API.

---

## Commandes courantes

| Commande                | Rôle                                   |
| ----------------------- | -------------------------------------- |
| `make start`            | Démarrer les services                  |
| `make stop`             | Arrêter les services                   |
| `make restart`          | Redémarrer les services                |
| `make logs`             | Suivre les logs                        |
| `make shell`            | Ouvrir un shell backend                |
| `make test`             | Exécuter les tests                     |
| `make lint`             | Vérifier Composer et Symfony           |
| `make quality`          | Exécuter PHP-CS-Fixer et PHPStan       |
| `make architecture`     | Vérifier les frontières d'architecture |
| `make security`         | Auditer les dépendances                |
| `make database-migrate` | Appliquer les migrations               |
| `make database-status`  | Vérifier l'état des migrations         |

Pour exécuter les principaux contrôles locaux :

```bash
make lint
make quality
make architecture
make test
make security
```

Pour les contrôles frontend, depuis `frontend/` :

```bash
pnpm format:check
pnpm lint
pnpm typecheck
pnpm test
pnpm build
```

`pnpm test` inclut les tests Admin/POS et Foundation ; `pnpm build` produit les
builds Next.js et Vite. Le workflow GitHub Frontend CI exécute actuellement
l'installation verrouillée et `pnpm test:workspace` (tests Admin/POS).

---

## Organisation du dépôt

```text
.
├── AGENTS.md
│
├── backend/
│   ├── config/
│   ├── migrations/
│   ├── public/
│   │
│   ├── src/
│   │   ├── Modules/
│   │   ├── Platform/
│   │   └── SharedKernel/
│   │
│   └── tests/
│
├── frontend/
│   ├── apps/
│   │   ├── admin/
│   │   └── pos/
│   ├── packages/
│   ├── test/
│   ├── package.json
│   ├── pnpm-workspace.yaml
│   └── pnpm-lock.yaml
│
├── docs/
│   ├── specs/
│   │   ├── architecture/
│   │   ├── planning/
│   │   ├── api/
│   │   └── spikes/
│   │
│   └── ai/
│       ├── README.md
│       ├── PROJECT_CONTEXT.md
│       ├── ARCHITECTURE_RULES.md
│       ├── ADR_INDEX.md
│       ├── SPEC_ROUTING.md
│       ├── LOT_REGISTRY.md
│       └── lots/
│
├── tasks/
│
├── tools/
│
├── scripts/
│
├── compose.yaml
├── Makefile
├── IMPLEMENTATION_STATUS.md
└── README.md
```

### `backend/`

Contient le code applicatif et les tests.

Les bounded contexts métier sont situés sous :

```text
backend/src/Modules/
```

Les concepts transverses stables sont situés sous :

```text
backend/src/SharedKernel/
```

Les composants techniques transverses appartiennent à :

```text
backend/src/Platform/
```

---

## Documentation

La documentation est volontairement séparée selon son usage.

### Documentation complète

```text
docs/specs/
```

Contient les sources de vérité détaillées :

- spécification DDD ;
- ADR ;
- Lots et Epics ;
- contrats ;
- décisions fonctionnelles ;
- spikes ;
- règles métier détaillées.

Ces documents peuvent être longs et exhaustifs.

---

### Contexte optimisé pour les agents de développement

```text
AGENTS.md
docs/ai/
```

`AGENTS.md` définit les règles générales applicables aux agents de développement tels que Codex.

`docs/ai/` fournit un contexte compact permettant à un agent de charger uniquement les informations nécessaires à la tâche en cours.

Le principe est :

```text
Task
  ↓
Lot
  ↓
Epic
  ↓
Supports nécessaires
  ↓
ADR nécessaires
  ↓
Code existant
```

Un agent ne doit pas parcourir l'ensemble de `docs/specs/` lorsqu'une tâche peut être résolue à partir du contexte ciblé.

La documentation complète reste disponible comme fallback lorsqu'une règle nécessaire n'est pas présente dans le contexte compact.

Voir :

[`docs/ai/README.md`](docs/ai/README.md)

---

## Suivi de l'implémentation

L'avancement détaillé ne doit pas être maintenu dans ce README.

La source dédiée est :

[`IMPLEMENTATION_STATUS.md`](IMPLEMENTATION_STATUS.md)

Elle contient notamment :

- les Epics terminés ;
- les travaux en cours ;
- les validations ;
- les Gates ;
- les résultats de tests ;
- les preuves PostgreSQL ;
- les points restant à implémenter.

---

## Licence

Projet propriétaire.

Aucun droit d'utilisation, de modification ou de distribution n'est accordé en dehors des autorisations explicites du propriétaire.
