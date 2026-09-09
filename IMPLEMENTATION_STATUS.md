# Zandu Sales Manager — Suivi d’implémentation

Ce document suit l’avancement réel de l’implémentation de **Zandu Sales Manager**
à partir du **Lot 0 — Architecture exécutable**.

Il ne remplace ni la spécification DDD, ni les ADR, ni le backlog du Lot 0.  
Son rôle est de conserver une trace simple de ce qui a effectivement été réalisé dans le repository.

---

# Vue d’ensemble

## État actuel du Lot 0

```text
Epic 0.1   TERMINÉ   Initialisation du repository backend
Epic 0.2   TERMINÉ   Fitness tests d’architecture
Epic 0.3   TERMINÉ   Persistence foundation
Epic 0.4   TERMINÉ   SharedKernel foundation
Epic 0.5   TERMINÉ   API foundation
Epic 0.6   TERMINÉ   Authentication foundation
Epic 0.7   TERMINÉ   Architectural spikes
Epic 0.8   TERMINÉ   Operations & observability
Gate Lot 0 TERMINÉ   Validation finale de l’architecture exécutable
```

Les Lots 0 et 1 sont clos :

```text
Epic 1.1   TERMINÉ   Organization foundation
Epic 1.2   TERMINÉ   Store foundation
Epic 1.3   TERMINÉ   Organization invitations
Epic 1.4   TERMINÉ   Membership lifecycle
Epic 1.5   TERMINÉ   Roles, permissions & scopes
Epic 1.5b  TERMINÉ   User accounts & onboarding
Epic 1.5c  TERMINÉ   Authentication security hardening
Epic 1.6   TERMINÉ   Authorization & operational guards
Epic 1.7   TERMINÉ   Security audit & event integration
Epic 1.8   TERMINÉ   Administration API
Epic 1.9   TERMINÉ   Integration & tenant isolation tests
Gate Lot 1 TERMINÉ   Administration opérationnelle complète
```

Les Lots 2, 3, 4 et 5 sont également clos :

```text
Gate Lot 2 TERMINÉ   Catalogue et tarification de base opérationnels
Gate Lot 3 TERMINÉ   Inventory et Cash foundations opérationnels
Gate Lot 4 TERMINÉ   M2 — première vente cash de bout en bout
Gate Lot 5 TERMINÉ   Inventory Costing & Returns, première partie de M3
```

Le Lot 6 est terminé :

```text
Phase 0     TERMINÉ   Planning Purchasing & Goods Receipts analysé et aligné
Epic 6.1    TERMINÉ   Purchasing foundation, boundaries et schéma PostgreSQL
Epic 6.2    TERMINÉ   Supplier aggregate, RLS, permissions et management
Epic 6.3    TERMINÉ   PurchasingPolicy et ADR réception fournisseur
Epic 6.4    TERMINÉ   PurchaseOrder aggregate, persistence et lifecycle
Epic 6.5    TERMINÉ   PurchaseOrder use cases, permissions et validations
Epic 6.6    TERMINÉ   GoodsReceipt aggregate et persistence
Epic 6.7    TERMINÉ   Création des réceptions directes
Epic 6.8    TERMINÉ   Publication transactionnelle des réceptions
Epic 6.9    TERMINÉ   Réception physique Inventory idempotente
Epic 6.10   TERMINÉ   Valorisation Costing des achats
Epic 6.11   TERMINÉ   Réceptions partielles liées aux commandes
Epic 6.12   TERMINÉ   Sur-réception autorisée et auditée
Epic 6.13   TERMINÉ   Corrections immuables de réception
Epic 6.14   TERMINÉ   Retours fournisseur, preuves PostgreSQL incluses
Epic 6.15   TERMINÉ   Blockers Purchasing composés dans StoreClosure
Gate Lot 6  TERMINÉ   Deuxième partie de M3 — approvisionnements fournisseurs
```

Le Lot 7 est terminé :

```text
Epic 7.1    TERMINÉ   StockTransfer aggregate et invariants
Epic 7.2    TERMINÉ   Brouillon, persistence PostgreSQL, RLS et permissions
Epic 7.3    TERMINÉ   Expédition physique atomique TRANSFER_OUT
Epic 7.4    TERMINÉ   Transit expliqué par le document SHIPPED, sans faux stock
Epic 7.5    TERMINÉ   Réception finale atomique TRANSFER_IN
Epic 7.6    TERMINÉ   Écarts de transit dérivés et conservés par ligne
Epic 7.7    TERMINÉ   Valeur transférée au coût source avec perte de transit
Transverse  TERMINÉ   Idempotence, concurrence et blockers StoreClosure composés
Epic 7.8    TERMINÉ   StockCount aggregate, statuts, modes et scopes
Epic 7.9    TERMINÉ   StockCountLine séparée et snapshot théorique
Epic 7.10   TERMINÉ   Création DRAFT, périmètre, permissions, PostgreSQL et RLS
Epic 7.11   TERMINÉ   Ouverture atomique et snapshot des quantités théoriques
Epic 7.12   TERMINÉ   Scopes produits exclusifs et mouvements bloqués
Epic 7.13   TERMINÉ   Saisie simple/batch, zéro, corrections et versions de ligne
Epic 7.14   TERMINÉ   Gel atomique OPEN vers FINALIZING après contrôle complet
Epic 7.15   TERMINÉ   Réconciliation physique PENDING par batch atomique
Epic 7.16   TERMINÉ   Reprise après crash sans rejeu des lignes réconciliées
Epic 7.17   TERMINÉ   Clôture atomique, outbox et libération des scopes
Epic 7.18   TERMINÉ   Valorisation des corrections et coût manuel protégé
Workflow    TERMINÉ   Annulation DRAFT/OPEN, historique et rollback atomique
API Transfer TERMINÉ  Workflow StockTransfer complet, tenant/scopes et OpenAPI
Gate Lot 7  TERMINÉ   M3 — gestion complète du stock
```

Le Frontend Foundation est démarré :

```text
Epic F0.1   TERMINÉ   Workspace pnpm, lockfile unique et résolution interne
CI F0.1     TERMINÉ   Installation verrouillée et tests workspace automatisés
Epic F0.2   TERMINÉ   Admin Next.js strict et shell racine placeholder
Epic F0.3   TERMINÉ   POS React/Vite/Tauri et shell racine placeholder
Epic F0.4   TERMINÉ   Conventions TypeScript strictes partagées
Epic F0.5   TERMINÉ   Lint, formatage et frontières d’imports partagés
Epic F0.6   TERMINÉ   Design tokens visuels partagés
Epic F0.7   TERMINÉ   Primitives UI génériques et accessibles
Epic F0.8   TERMINÉ   Formatting métier exact et timezone-aware
Epic F0.9   TERMINÉ   Client API typé, transport Symfony et génération OpenAPI
Epic F0.10  TERMINÉ   Contrat d’erreur et ErrorMapper frontend
Epic F0.11  TERMINÉ   Cycle de session frontend en mémoire
Epic F0.12  TERMINÉ   OrganizationContext explicite et projection de session serveur
Epic F0.13  TERMINÉ   StoreContext distinct du tenant actif
Epic F0.14  TERMINÉ   Modèle d’autorisation frontend et guards UX
Epic F0.15  TERMINÉ   Cache serveur tenant/store-scoped
Epic F0.16  TERMINÉ   Fondations formulaires et validation runtime
Epic F0.17  TERMINÉ   Fondations de tables Admin paginées par serveur
Epic F0.18  TERMINÉ   Routing Admin par capacités UX
Epic F0.19  TERMINÉ   Shell Admin desktop-first
Epic F0.20  TERMINÉ   Shell POS opérationnel
Epic F0.21  TERMINÉ   Notifications et confirmations
Epic F0.22  TERMINÉ   Idempotency-Key frontend
Epic F0.23  TERMINÉ   Abstraction réseau centralisée
Epic F0.24  TERMINÉ   Organisation par features frontend
Epic F0.25  TERMINÉ   Pyramide de tests frontend
Epic F0.26  TERMINÉ   Observabilité frontend structurée
Epic F0.27  TERMINÉ   Pipeline CI frontend complet
```

Le détail du Lot 5 clôturé :

```text
Phase 0     TERMINÉ   ADR Costing et Refund, planning aligné
Epic 5.1    TERMINÉ   Inventory Costing foundation
Epic 5.4    TERMINÉ   Moving weighted average calculator
Epic 5.2    TERMINÉ   StockValuation aggregate et persistence
Epic 5.3    TERMINÉ   StockValuationMovement ledger et persistence
Persistence TERMINÉ   PostgreSQL, contraintes, repositories et RLS Costing
Epic 5.5    TERMINÉ   Bootstrap explicite des valorisations
Inventory   TERMINÉ   INITIAL_STOCK et ajustements valorisés atomiquement
Epic 5.6    TERMINÉ   CompleteSale valorise les sorties SALE
Epic 5.7    TERMINÉ   SaleLineCostSnapshot immutable et tenant-scoped
Epic 5.8    TERMINÉ   Atomicité CompleteSale/Costing prouvée par faute injectée
Epic 5.9    TERMINÉ   ReturnSale et ReturnSaleLine foundation
Persistence TERMINÉ   ReturnSale PostgreSQL, snapshots liés et RLS
Returns     TERMINÉ   Vente source terminée et limites cumulatives
Epic 5.10   TERMINÉ   Restock Inventory idempotent via SALE_RETURN
Epic 5.11   TERMINÉ   Coût original restauré et restock activé atomiquement
Epic 5.12   TERMINÉ   Montants de retour alloués depuis les snapshots originaux
Epic 5.13   TERMINÉ   Remboursement cash essentiel, borné et idempotent
API Returns TERMINÉ   Workflow ReturnSale complet exposé et documenté
Rollback Refund TERMINÉ Faute tardive sans aucun ledger partiel
Rollback Return TERMINÉ Inventory, Costing et ledgers sans effet partiel
Idempotence TERMINÉ   Rejeux Return/Refund sans aucun effet dupliqué
Isolation   TERMINÉ   RLS tenant et scopes Store prouvés sur Return/Refund
Gate Lot 5  TERMINÉ   CI distante verte, première partie de M3 validée
```

État consolidé au 3 septembre 2026 :

```text
Branche              main
Migrations           Version20260831170000 appliquée en dernier
Tests                 746 tests, 3 767 assertions
PHPStan               OK
PHP-CS-Fixer          OK
Deptrac layers        0 violation, 10 dépendances non classées
Deptrac modules       0 violation, 10 dépendances non classées
Composer audit        aucune vulnérabilité connue
Documentation dev     Swagger UI et ReDoc actifs uniquement en dev
Worktree              changements API StockCount non commités
```

## Definition of Done globale

```text
[x] repository backend exécutable
[x] runtime PHP reproductible
[x] PostgreSQL local disponible
[x] bootstrap développeur disponible
[x] namespaces et structure DDD matérialisés
[x] fitness tests d’architecture exécutables
[x] frontières cross-context protégées
[x] CI backend opérationnelle
[x] violation architecturale fait échouer la CI
[x] persistence foundation validée
[x] SharedKernel foundation validée
[x] API foundation validée
[x] authentication foundation validée
[x] architectural spikes réalisés
[x] exploitation et observabilité minimales validées
[x] documentation finale du Lot 0 à jour
[x] CI finale entièrement verte
[x] Gate Lot 0 validé
[x] administration opérationnelle validée
[x] démonstration consolidée du Lot 1 validée
[x] Gate Lot 1 validé
[x] catalogue et Pricing de base validés
[x] Gate Lot 2 validé
[x] Inventory et Cash foundations validés
[x] Gate Lot 3 validé
[x] première vente cash M2 validée
[x] Gate Lot 4 validé
```

## Références

- Spécification d’architecture DDD v1.1
- ADR techniques 0001–0022
- `zandu-lot-0-architecture-executable.md`
- `zandu-lot-1-administration-operationnelle.md`
- `zandu-lot-2-catalog-basic-pricing.md`
- `zandu-lot-3-inventory-cash-foundations.md`
- `zandu-lot-4-sales-complete-sale-cash.md`
- `zandu-lot-5-inventory-costing-returns.md`

---

La suite du document conserve l’historique détaillé des étapes, validations et
commits du Lot 0.

# Epic 0.1 — Initialisation du repository backend

**Statut : TERMINÉ**

## 0.1.1 — Initialiser Symfony

**Statut : TERMINÉ**

### Réalisé

- repository Git initialisé à la racine de `zandu-sales-manager/` ;
- backend Symfony créé dans `backend/` ;
- Symfony `7.4.16 LTS` installé ;
- environnement PHP local vérifié avec PHP `8.5.4` ;
- configuration `dev` fonctionnelle ;
- configuration `test` fonctionnelle ;
- PHPUnit opérationnel ;
- test minimal du Kernel ajouté ;
- `composer validate --no-check-publish` retenu pour valider l’application sans imposer les métadonnées d’un package publiable ;
- aucune logique métier introduite ;
- Doctrine, API Platform et PostgreSQL non introduits à cette étape.

### Validations exécutées

```bash
php bin/phpunit
php bin/console about
APP_ENV=test php bin/console about
composer validate --no-check-publish
```

Résultat du test :

```text
OK (1 test, 1 assertion)
```

### Commit atomique

```text
build(backend): initialize Symfony application
```

---

## Documentation d’architecture — état initial

**Statut à cette étape : EN COURS**

Le dossier suivant est présent dans le repository :

```text
docs/
└── architecture/
    └── adr/
```

Il contient les ADR techniques initiaux.

### Commit recommandé / réalisé selon l’état du repository

```text
docs(adr): add initial architecture decisions
```

À cette étape, la spécification DDD v1.1 et le backlog du Lot 0 devaient encore
être intégrés dans `docs/`.

Structure cible :

```text
docs/
├── architecture/
│   ├── ddd/
│   │   └── zandu-sales-manager-ddd-v1.1.docx
│   ├── adr/
│   │   └── ...
│   └── fitness-tests.md
└── planning/
    └── zandu-lot-0-architecture-executable.md
```

Commits recommandés lors de leur ajout :

```text
docs(architecture): add DDD architecture specification
docs(planning): add Lot 0 implementation backlog
```

---

## 0.1.2 — Définir la structure racine DDD

**Statut : TERMINÉ**

### Réalisé

Structure racine créée :

```text
backend/src/
├── Modules/
├── SharedKernel/
├── Platform/
└── Kernel.php
```

Les dossiers encore vides sont conservés avec des placeholders `.gitkeep`.

### Commit atomique

```text
refactor(architecture): introduce modular monolith root structure
```

---

## 0.1.3 — Ajouter les premiers bounded contexts

**Statut : TERMINÉ**

### Réalisé

```text
backend/src/Modules/
├── Sales/
├── Inventory/
└── CashManagement/
```

Chaque module possède le squelette :

```text
Module/
├── Domain/
├── Application/
│   └── Contract/
├── Infrastructure/
└── Presentation/
    └── Api/
```

### Commit atomique

```text
refactor(modules): add initial bounded context skeletons
```

---

## 0.1.4 — Configurer les namespaces

**Statut : TERMINÉ**

### Réalisé

Le namespace racine `App\` a été remplacé par `Zandu\`.

Convention active :

```text
Zandu\Modules\Sales\...
Zandu\Modules\Inventory\...
Zandu\Modules\CashManagement\...
Zandu\SharedKernel\...
Zandu\Platform\...
```

Le Kernel utilise désormais :

```text
Zandu\Kernel
```

Le namespace de tests utilise :

```text
Zandu\Tests\
```

Comme `KERNEL_CLASS` n’était pas présent par défaut dans `phpunit.dist.xml`, il a été ajouté explicitement dans la section `<php>` :

```xml
<server name="KERNEL_CLASS" value="Zandu\Kernel"/>
```

### Validations exécutées

```bash
composer dump-autoload -o --strict-psr
composer validate --no-check-publish
php bin/console about
APP_ENV=test php bin/console about
php bin/phpunit
```

### Commit atomique

```text
build(autoload): configure Zandu namespaces
```

---

## 0.1.5 — Ajouter Docker pour le backend

**Statut : TERMINÉ**

### Réalisé

Une image Docker backend a été ajoutée à partir de :

```text
php:8.5-cli-bookworm
```

L’image est conçue comme runtime commun pour, à terme :

```text
API
Worker
Scheduled Job
Migration
```

Le runtime utilise `php.ini-production`.

Extensions disponibles :

```text
intl
pdo_pgsql
Zend OPcache
```

Composer est copié depuis l’image officielle Composer.

Un `.dockerignore` a été ajouté.

### Correction pendant l’implémentation

L’installation explicite de `opcache` via `docker-php-ext-install` a provoqué :

```text
cp: cannot stat 'modules/*': No such file or directory
```

`Zend OPcache` étant déjà fourni par l’image PHP 8.5 utilisée, son installation explicite a été supprimée.

Le Dockerfile installe explicitement :

```text
intl
pdo_pgsql
```

et utilise l’OPcache déjà fourni par PHP.

### Validations exécutées

```bash
docker build --no-cache -t zandu-backend:dev .
docker run --rm zandu-backend:dev php -m | grep -E 'intl|pdo_pgsql|OPcache'
docker run --rm zandu-backend:dev php -v
docker run --rm zandu-backend:dev php bin/console about
docker run --rm zandu-backend:dev php bin/console lint:container
```

Sortie observée pour les extensions :

```text
intl
pdo_pgsql
Zend OPcache
Zend OPcache
```

La double apparition de `Zend OPcache` est normale dans la sortie filtrée de `php -m`.

### Commit atomique principal

```text
build(docker): add backend container image
```

---

## Alignement de la version PHP

**Statut : TERMINÉ**

Après le commit Docker, la contrainte Composer était encore :

```json
"php": "^8.4"
```

Elle a été alignée sur le runtime local et Docker :

```json
"php": "^8.5"
```

Comme le commit Docker avait déjà été créé, cette modification reste dans un commit atomique complémentaire.

### Validations

```bash
composer update --lock
composer validate --no-check-publish
php bin/phpunit
```

### Commit atomique complémentaire

```text
build(php): require PHP 8.5
```

---

## 0.1.6 — Ajouter PostgreSQL local

**Statut : TERMINÉ**

### Réalisé

Un service PostgreSQL local a été ajouté à Docker Compose.

Image utilisée :

```text
postgres:18-bookworm
```

Le service utilise des variables d’environnement pour :

```text
POSTGRES_DB
POSTGRES_USER
POSTGRES_PASSWORD
POSTGRES_PORT
```

Le hostname interne accessible depuis le backend est :

```text
postgres
```

Le backend et PostgreSQL partagent le réseau Docker Compose.

Un healthcheck PostgreSQL basé sur `pg_isready` est configuré.

Le backend attend l’état `healthy` de PostgreSQL.

Un volume Docker persistant conserve les données PostgreSQL.

Le port PostgreSQL est exposé localement :

```text
5432:5432
```

Aucune installation Doctrine n’a été introduite à cette étape.

Aucune migration ni table métier n’a été créée.

SQLite n’est pas utilisé comme base serveur.

### Validations exécutées

```bash
docker compose up -d
docker compose ps
```

État observé :

```text
backend    Running
postgres   Healthy
```

Connexion directe :

```bash
docker compose exec postgres \
  psql -U zandu -d zandu -c "SELECT version();"
```

Résolution DNS depuis le backend :

```bash
docker compose exec backend php -r \
'echo gethostbyname("postgres"), PHP_EOL;'
```

Connexion réelle via PDO :

```bash
docker compose exec backend php -r '
$pdo = new PDO(
    sprintf(
        "pgsql:host=%s;port=%s;dbname=%s",
        getenv("POSTGRES_HOST"),
        getenv("POSTGRES_PORT"),
        getenv("POSTGRES_DB")
    ),
    getenv("POSTGRES_USER"),
    getenv("POSTGRES_PASSWORD")
);

echo $pdo->query("SELECT current_database()")\->fetchColumn(), PHP_EOL;
'
```

Résultat :

```text
zandu
```

### Commit atomique

```text
chore(database): add local PostgreSQL service
```

---

## 0.1.7 — Ajouter le bootstrap développeur

**Statut : TERMINÉ**

### Réalisé

Un `Makefile` a été ajouté à la racine du repository.

Commandes disponibles :

```text
make install
make start
make stop
make restart
make test
make lint
make shell
make logs
make ps
```

Les commandes Doctrine de création/migration de base n’étaient pas encore exposées à cette étape, Doctrine n’étant pas encore installé.

Elles ont été ajoutées lors de l’Epic de persistence.

### Validations exécutées

```bash
make ps
make test
make lint
make stop
make start
make ps
make shell
make install
```

### Commit atomique

```text
chore(dev): add local development commands
```

---

# État final de l’Epic 0.1

```text
0.1.1  TERMINÉ  Initialisation Symfony
0.1.2  TERMINÉ  Structure racine DDD
0.1.3  TERMINÉ  Premiers bounded contexts
0.1.4  TERMINÉ  Namespaces Zandu
0.1.5  TERMINÉ  Image Docker backend
0.1.6  TERMINÉ  PostgreSQL local
0.1.7  TERMINÉ  Bootstrap développeur
```

---

# Definition of Done — Epic 0.1

```text
[x] repository Git initialisé à la racine
[x] Symfony fonctionne
[x] PostgreSQL fonctionne
[x] structure Modules / SharedKernel / Platform présente
[x] premiers bounded contexts matérialisés
[x] autoload PSR-4 Zandu valide
[x] test minimal passe
[x] image Docker backend construite
[x] runtime PHP aligné sur PHP 8.5
[x] bootstrap développeur disponible
[x] aucune logique métier cross-context introduite
```

**Epic 0.1 : TERMINÉ**

---

# Epic 0.2 — Fitness tests d’architecture

**Statut : TERMINÉ**

Objectif : rendre les frontières DDD exécutables et faire échouer automatiquement l’intégration continue lorsqu’une dépendance architecturale interdite est introduite.

---

## 0.2.1 — Installer Deptrac

**Statut : TERMINÉ**

### Réalisé

Deptrac a été ajouté comme dépendance de développement :

```text
deptrac/deptrac
```

L’outil est disponible via :

```bash
vendor/bin/deptrac
```

### Validations exécutées

```bash
vendor/bin/deptrac --version
composer validate --no-check-publish
php bin/phpunit
```

### Commit atomique

```text
build(architecture): add Deptrac
```

---

## 0.2.2 — Définir les couches techniques

**Statut : TERMINÉ**

### Réalisé

Les couches suivantes ont été matérialisées :

```text
Domain
Application
Infrastructure
Presentation
SharedKernel
Platform
```

Les collectors utilisent les namespaces `Zandu\...`.

Les expressions régulières fonctionnelles utilisent notamment :

```text
.*Zandu\\Modules\\...
```

Les premiers motifs utilisant `^Zandu` et un échappement excessif des antislashs ne collectaient pas correctement les classes.

La configuration a été corrigée à partir du comportement réellement observé avec `debug:layer` et des probes architecturaux.

### Validation volontaire

Une dépendance :

```text
Sales\Domain
→ Sales\Infrastructure
```

a été introduite temporairement.

Deptrac l’a correctement rejetée.

Après suppression des probes, l’analyse est revenue au vert.

### Commit atomique

```text
build(architecture): configure dependency layers
```

La correction des collectors peut être conservée dans un commit correctif atomique dédié si elle a déjà été séparée dans l’historique :

```text
fix(architecture): correct Deptrac collectors
```

---

## 0.2.3 — Séparer les règles de couches et les règles de modules

**Statut : TERMINÉ**

### Problème observé

La configuration initiale utilisait simultanément :

```text
Application
Domain
Infrastructure
Presentation
```

et :

```text
Sales
Inventory
CashManagement
```

dans la même analyse.

Une classe :

```text
Zandu\Modules\Sales\Application\...
```

appartenait alors simultanément à :

```text
Application
Sales
```

Deptrac produisait un warning de chevauchement et plusieurs violations pour une même dépendance physique.

### Correction

Les deux axes ont été séparés :

```text
backend/
├── deptrac.layers.php
└── deptrac.modules.php
```

`deptrac.layers.php` contrôle les couches techniques.

`deptrac.modules.php` contrôle les bounded contexts et leurs APIs publiques.

### Résultat

Les analyses deviennent indépendantes et ne produisent plus de warning de chevauchement pour cette modélisation.

### Commit atomique

```text
build(architecture): separate layer and module rules
```

---

## 0.2.4 — Protéger les frontières inter-bounded-context

**Statut : TERMINÉ**

### Modules matérialisés

```text
Sales
Inventory
CashManagement
```

### APIs applicatives publiques

```text
SalesContract
InventoryContract
CashManagementContract
```

Les dossiers :

```text
Application/Contract/
```

sont exclus du layer interne de leur bounded context.

Exemple conceptuel :

```text
Inventory
=
tout Zandu\Modules\Inventory\...
SAUF
Zandu\Modules\Inventory\Application\Contract\...
```

### Cas interdit validé

Une dépendance temporaire :

```text
Sales\Application
→ Inventory\Domain
```

a été introduite.

Résultat attendu obtenu :

```text
Violations  1
Warnings    0
Errors      0
```

`deptrac.layers.php` autorisait correctement la relation technique :

```text
Application → Domain
```

mais `deptrac.modules.php` rejetait :

```text
Sales → Inventory
```

### Cas autorisé validé

Une dépendance :

```text
Sales\Application
→ Inventory\Application\Contract
```

a ensuite été testée.

Résultat :

```text
Violations  0
Warnings    0
Errors      0
```

La règle suivante est donc exécutable :

```text
Sales\Application
    ├── Inventory\Domain                 ✗
    └── Inventory\Application\Contract  ✓
```

Les probes temporaires ont été supprimés après validation.

### Commit atomique

```text
build(architecture): enforce bounded context boundaries
```

---

## 0.2.5 — Ajouter une commande de validation d’architecture

**Statut : TERMINÉ**

### Réalisé

Le `Makefile` expose :

```bash
make architecture
```

Cette commande exécute :

```bash
vendor/bin/deptrac analyse \
  --config-file=deptrac.layers.php \
  --no-cache
```

puis :

```bash
vendor/bin/deptrac analyse \
  --config-file=deptrac.modules.php \
  --no-cache
```

Les validations restent séparées :

```text
make lint
→ validation technique/configuration

make test
→ tests automatisés

make architecture
→ fitness tests structurels
```

### Validations exécutées

```bash
make architecture
make test
make lint
```

### Commit atomique

```text
chore(dev): add architecture validation command
```

---

## 0.2.6 — Ajouter la CI backend

**Statut : TERMINÉ**

### Réalisé

Le premier workflow GitHub Actions backend a été ajouté :

```text
.github/
└── workflows/
    └── backend-ci.yml
```

Le workflow s’exécute sur :

```text
push
pull_request
```

Il repose sur Docker Compose afin d’utiliser le même runtime qu’en développement local.

Chaîne de validation :

```text
GitHub Actions
    ↓
Docker Compose
    ↓
PHP 8.5
    ↓
make lint
make test
make architecture
```

Le workflow :

- checkout le repository ;
- construit l’image backend ;
- installe les dépendances Composer ;
- démarre les services ;
- vérifie leur état ;
- exécute les validations techniques ;
- exécute PHPUnit ;
- exécute les fitness tests ;
- arrête les services même en cas d’échec.

### Commit atomique

```text
ci(backend): add initial validation workflow
```

---

## Mise à niveau de `actions/checkout`

**Statut : TERMINÉ**

GitHub Actions a signalé la dépréciation du runtime Node.js 20 utilisé par :

```text
actions/checkout@v4
```

L’action a été mise à jour vers :

```text
actions/checkout@v5
```

### Commit atomique

```text
ci(backend): upgrade checkout action to v5
```

---

## 0.2.7 — Valider l’échec réel de la CI

**Statut : TERMINÉ**

### Méthode

Une branche temporaire :

```text
test/architecture-violation
```

a été créée.

Une dépendance volontairement interdite a été introduite :

```text
Sales\Application
→ Inventory\Domain
```

### Résultat

La CI GitHub Actions a échoué comme attendu sur :

```text
Validate architecture
```

La violation a ensuite été supprimée.

Après suppression :

```text
make architecture
→ vert
```

et la pipeline GitHub Actions est redevenue verte.

### Validation de bout en bout

```text
violation architecturale
        ↓
Deptrac
        ↓
make architecture
        ↓
GitHub Actions
        ↓
pipeline rouge
```

puis :

```text
violation supprimée
        ↓
Deptrac
        ↓
make architecture
        ↓
GitHub Actions
        ↓
pipeline verte
```

Les probes de validation n’ont pas été intégrés à `main`.

---

## 0.2.8 — Interdire les frameworks dans Domain

**Statut : TERMINÉ**

### Réalisé

`deptrac.layers.php` matérialise également :

```text
Symfony
Doctrine
ApiPlatform
```

Le ruleset `Domain` n’autorise aucune dépendance vers ces layers.

Règles :

```text
Domain → Symfony       ✗
Domain → Doctrine      ✗
Domain → ApiPlatform   ✗
```

### Symfony

Une violation réelle a été testée avec :

```text
Domain
→ Symfony\Component\HttpFoundation\Request
```

Deptrac a correctement rejeté la dépendance.

Le probe temporaire a ensuite été supprimé et l’analyse est revenue au vert.

### Doctrine et API Platform

Les règles ont été configurées dès cette étape.

Doctrine a ensuite été installé dans l’Epic 0.3 et l’interdiction `Domain → Doctrine` a été revalidée avec une dépendance réelle.

API Platform n’est pas encore installé ; sa validation réelle sera rejouée lors de son introduction.

### Commit atomique

```text
build(architecture): forbid framework dependencies in domain
```

---

## 0.2.9 — Documenter les fitness tests

**Statut : TERMINÉ**

### Réalisé

La documentation suivante a été ajoutée :

```text
docs/architecture/fitness-tests.md
```

Elle documente :

- l’utilisation de Deptrac ;
- la séparation `deptrac.layers.php` / `deptrac.modules.php` ;
- les règles de dépendance entre couches ;
- les règles inter-bounded-context ;
- le rôle de `Application/Contract` ;
- les frameworks interdits dans `Domain` ;
- l’utilisation de `make architecture` ;
- l’intégration GitHub Actions ;
- le principe des probes temporaires ;
- les règles à suivre lors de l’ajout d’un nouveau bounded context ;
- la règle interdisant d’assouplir arbitrairement Deptrac uniquement pour supprimer une violation.

### Commit atomique

```text
docs(architecture): document dependency fitness tests
```

---

# État final de l’Epic 0.2

```text
0.2.1  TERMINÉ  Installation de Deptrac
0.2.2  TERMINÉ  Couches techniques
0.2.3  TERMINÉ  Séparation layers / modules
0.2.4  TERMINÉ  Frontières inter-bounded-context
0.2.5  TERMINÉ  Commande make architecture
0.2.6  TERMINÉ  CI backend GitHub Actions
0.2.7  TERMINÉ  Validation CI rouge → verte
0.2.8  TERMINÉ  Protection Domain contre les frameworks
0.2.9  TERMINÉ  Documentation des fitness tests
```

---

# Garanties architecturales exécutables

## Couches techniques

```text
Domain
→ SharedKernel

Application
→ Domain
→ SharedKernel

Infrastructure
→ Domain
→ Application
→ SharedKernel
→ Platform
→ Symfony
→ Doctrine
→ ApiPlatform

Presentation
→ Application
→ SharedKernel
→ Symfony
→ ApiPlatform

Platform
→ SharedKernel
→ Symfony
```

Les dépendances non explicitement autorisées sont rejetées.

---

## Domain indépendant des frameworks

```text
Domain → Symfony       ✗
Domain → Doctrine      ✗
Domain → ApiPlatform   ✗
```

La protection contre Symfony a été validée par une violation réelle dans l’Epic 0.2.

La protection contre Doctrine a été revalidée avec Doctrine réellement installé dans l’Epic 0.3.

La protection contre API Platform sera rejouée avec une dépendance réelle lors de son installation.

---

## Frontières de bounded contexts

Modules actuellement matérialisés :

```text
Sales
Inventory
CashManagement
```

Contracts publics :

```text
SalesContract
InventoryContract
CashManagementContract
```

Autorisé :

```text
Sales\Application
→ Inventory\Application\Contract
✓
```

Interdit :

```text
Sales\Application
→ Inventory\Domain
✗
```

Une dépendance interne cross-context ne peut donc pas contourner l’API applicative publique du module cible.

---

# Definition of Done — Epic 0.2

```text
[x] Deptrac installé
[x] couches DDD matérialisées
[x] règles inter-couches exécutables
[x] bounded contexts matérialisés dans une vue dédiée
[x] Application Contracts publics matérialisés
[x] dépendance cross-context directe vers Domain interdite
[x] dépendance cross-context vers Application\Contract autorisée
[x] violation volontaire détectée localement
[x] configuration sans warning de chevauchement
[x] commande make architecture disponible
[x] fitness tests exécutés dans GitHub Actions
[x] violation architecturale fait échouer la CI
[x] suppression de la violation remet la CI au vert
[x] Domain explicitement protégé contre Symfony
[x] Domain explicitement protégé contre Doctrine
[x] Domain explicitement protégé contre API Platform
[x] règles d’architecture documentées dans le repository
```

**Epic 0.2 : TERMINÉ**

### Validation différée explicitement enregistrée

La règle API Platform est déjà configurée.

Sa validation à partir d’une dépendance réelle sera rejouée au moment de l’installation d’API Platform.

Aucun package n’est installé prématurément uniquement pour satisfaire un fitness test.

---

# Epic 0.3 — Persistence foundation

**Statut : TERMINÉ**

Objectif : introduire la persistence PostgreSQL applicative avec Doctrine sans violer les frontières DDD déjà rendues exécutables.

---

## 0.3.1 — Installer la stack Doctrine

**Statut : TERMINÉ**

### Réalisé

La stack de persistence Doctrine a été introduite dans le backend Symfony.

Composants disponibles :

```text
Doctrine ORM
Doctrine DBAL
DoctrineBundle
Doctrine Migrations
Doctrine Migrations Bundle
```

Doctrine reste une dépendance d’infrastructure.

Aucune dépendance Doctrine n’a été introduite dans le `Domain`.

### Validation architecturale

Après installation réelle de Doctrine, le fitness test différé de l’Epic 0.2 a été rejoué avec une dépendance temporaire :

```text
Domain
→ Doctrine\ORM\EntityManagerInterface
```

Deptrac a correctement rejeté cette dépendance.

Le probe temporaire a ensuite été supprimé et :

```bash
make architecture
```

est revenu au vert.

### Commit atomique

```text
build(persistence): add Doctrine persistence stack
```

---

## 0.3.2 — Configurer Doctrine sur PostgreSQL

**Statut : TERMINÉ**

### Réalisé

Doctrine DBAL utilise PostgreSQL comme base transactionnelle serveur.

La connexion applicative est configurée via :

```text
DATABASE_URL
```

Le backend Docker utilise le hostname interne :

```text
postgres
```

Le service PostgreSQL reste configuré à partir de :

```text
POSTGRES_DB
POSTGRES_USER
POSTGRES_PASSWORD
POSTGRES_PORT
```

La connexion DBAL a été validée avec :

```bash
docker compose exec backend php bin/console dbal:run-sql \
  "SELECT current_database(), current_user"
```

La base utilisée est :

```text
zandu
```

Aucun SQLite serveur n’est introduit.

### Commit atomique

```text
feat(persistence): configure PostgreSQL connection
```

---

## Correction de l’environnement Symfony local

**Statut : TERMINÉ**

### Problème observé

Le backend Docker local fonctionnait initialement avec la configuration Symfony :

```text
APP_ENV=prod
```

Cela provoquait notamment des difficultés liées au cache pendant le développement.

### Correction

L’environnement Docker local utilise désormais explicitement :

```text
APP_ENV=dev
APP_DEBUG=1
```

Le runtime PHP peut continuer à utiliser :

```text
php.ini-production
```

Cette configuration PHP est indépendante de l’environnement Symfony.

Convention retenue :

```text
Développement local
APP_ENV=dev
APP_DEBUG=1

Tests
APP_ENV=test

Production
APP_ENV=prod
APP_DEBUG=0
```

### Validations exécutées

```bash
docker compose up -d --force-recreate backend
docker compose exec backend php bin/console about
```

Le backend local fonctionne désormais avec Symfony en environnement `dev`.

### Commit atomique

```text
fix(dev): run local backend in Symfony dev environment
```

---

## 0.3.3 — Matérialiser les schemas PostgreSQL des bounded contexts

**Statut : TERMINÉ**

### Réalisé

Une migration Doctrine initiale matérialise les schemas PostgreSQL correspondant aux bounded contexts actuellement présents :

```text
sales
inventory
cash_management
```

Aucune table métier ou fausse entité Doctrine n’a été créée uniquement pour initialiser la persistence.

La migration respecte donc la séparation :

```text
bounded context
→ schema PostgreSQL logique
```

### Migration

Le `up()` crée :

```text
sales
inventory
cash_management
```

Le `down()` supprime ces schemas.

### Validations exécutées

Migration appliquée :

```bash
docker compose exec backend php bin/console doctrine:migrations:migrate \
  --no-interaction
```

Schemas vérifiés via DBAL/PostgreSQL.

Le rollback a également été exécuté et les schemas ont disparu comme attendu.

La migration a ensuite été réappliquée afin de remettre la base dans son état cible.

### Commit atomique

```text
feat(persistence): add bounded context schemas migration
```

---

## 0.3.4 — Ajouter les commandes de persistence au bootstrap développeur

**Statut : TERMINÉ**

### Réalisé

Le `Makefile` expose désormais des commandes pour la persistence.

Commandes disponibles :

```text
make database-create
make database-migrate
make database-status
make database-sql
```

Exemple :

```bash
make database-sql SQL="SELECT current_database(), current_user"
```

Ces commandes complètent le bootstrap développeur introduit dans l’Epic 0.1.

### Commit atomique

```text
chore(dev): add persistence commands
```

---

## 0.3.5 — Valider les frontières Doctrine

**Statut : TERMINÉ**

La règle suivante est désormais validée avec Doctrine réellement installé :

```text
Domain → Doctrine ✗
```

Les mappings, repositories et autres implémentations Doctrine devront rester dans les couches d’infrastructure appropriées.

Aucun mapping Doctrine n’est introduit dans le modèle de domaine.

---

# Décisions de persistence actuellement actives

## PostgreSQL

PostgreSQL est la base transactionnelle serveur.

```text
Backend
   ↓
Doctrine DBAL / ORM
   ↓
PostgreSQL
```

SQLite n’est pas utilisé comme base serveur.

---

## Doctrine

Doctrine suit une stratégie :

```text
ORM first, not ORM only
```

Doctrine ORM est disponible pour les cas adaptés au modèle.

Doctrine DBAL ou du SQL explicite pourront être utilisés sur les chemins critiques lorsque les contraintes de concurrence ou de performance le justifieront.

Cette décision sera notamment affinée par les architectural spikes du Lot 0.

---

## Séparation DDD

Doctrine appartient à l’infrastructure.

```text
Domain
    ↓
Doctrine
    ✗
```

Les aggregates ne doivent pas dépendre directement :

```text
Doctrine\ORM
Doctrine\DBAL
Doctrine attributes
Doctrine repositories
EntityManager
```

Les repositories métier seront définis à partir des besoins du domaine et non à partir d’un CRUD générique.

---

## Schemas PostgreSQL

Les premiers bounded contexts utilisent les schemas :

```text
Sales
→ sales

Inventory
→ inventory

CashManagement
→ cash_management
```

Les futurs bounded contexts devront suivre la stratégie de persistence définie par l’architecture et les ADR.

---

# Definition of Done — Epic 0.3

```text
[x] Doctrine ORM installé
[x] Doctrine DBAL installé
[x] Doctrine Migrations installé
[x] connexion Doctrine → PostgreSQL fonctionnelle
[x] environnement Docker local en Symfony dev
[x] PostgreSQL reste la base transactionnelle serveur
[x] SQLite serveur absent
[x] Domain → Doctrine rejeté par Deptrac
[x] aucun mapping Doctrine dans Domain
[x] schemas PostgreSQL par bounded context matérialisés
[x] migration forward validée
[x] rollback validé
[x] migration réappliquée
[x] commandes persistence disponibles
[x] make lint vert
[x] make test vert
[x] make architecture vert
[x] CI verte
```

**Epic 0.3 : TERMINÉ**

---

# Epic 0.4 — SharedKernel foundation

**Statut : TERMINÉ**

Objectif général : introduire uniquement les primitives réellement transversales nécessaires au Lot 0 et aux premiers bounded contexts, sans transformer `SharedKernel` en module métier global.

Contraintes :

- aucune dépendance de `SharedKernel` vers un bounded context ;
- aucune dépendance vers Doctrine, Symfony ou API Platform ;
- primitives immuables et explicites ;
- identifiants et value objects partagés uniquement lorsqu’ils ont réellement le même sens dans plusieurs contextes ;
- les règles Deptrac existantes restent vertes ;
- les décisions encore ouvertes dans les ADR ou la baseline ne doivent pas être figées prématurément.

## 0.4.1 — Ajouter l’abstraction UUID

**Statut : TERMINÉ**

### Réalisé

Le namespace `Zandu\SharedKernel\Identity` expose désormais les contrats :

```text
Uuid
UuidFactory
IdGenerator
```

`Uuid` définit une représentation textuelle et une égalité par valeur.
`UuidFactory` reconstruit un UUID depuis sa représentation textuelle.
`IdGenerator` fournit un nouvel UUID sans exposer la technologie utilisée pour
le générer.

Ces contrats ne dépendent ni de Symfony, ni de Doctrine, ni d’une autre
bibliothèque externe. La validation des UUID et la génération UUID v7 seront
portées par l’implémentation `Platform` prévue à l’étape suivante.

### Tests ajoutés

Les tests vérifient que les contrats permettent :

- la reconstruction d’un UUID par une factory ;
- la génération d’un identifiant par un générateur ;
- la comparaison des UUID par valeur.

### Validations exécutées

```bash
composer dump-autoload -o --strict-psr
php bin/phpunit
composer validate --no-check-publish
php bin/console lint:container
vendor/bin/deptrac analyse --config-file=deptrac.layers.php --no-cache
vendor/bin/deptrac analyse --config-file=deptrac.modules.php --no-cache
```

Résultats :

```text
PHPUnit : OK (4 tests, 5 assertions)
Composer : valide
Conteneur Symfony : valide
Deptrac layers : 0 violation, 0 warning, 0 erreur
Deptrac modules : 0 violation, 0 warning, 0 erreur
```

### Commit proposé

```text
feat(identity): add UUID abstraction
```

Le commit est volontairement laissé à l’utilisateur.

## 0.4.2 — Implémenter UUID v7 avec Symfony UID

**Statut : TERMINÉ**

### Réalisé

Le composant `symfony/uid` 7.4 est installé comme dépendance directe.

Les implémentations suivantes résident dans `Platform` :

```text
SymfonyUuid
SymfonyUuidFactory
SymfonyUuidV7Generator
```

`SymfonyUuid` adapte `Symfony\Component\Uid\UuidV7` au contrat `Uuid` du
`SharedKernel`. La factory reconstruit uniquement des UUID v7 valides et
traduit les erreurs Symfony en `InvalidArgumentException` standard. Le
générateur produit de nouveaux UUID v7 sans exposer Symfony aux consommateurs.

Les contrats sont reliés à leurs implémentations dans le conteneur Symfony :

```text
UuidFactory → SymfonyUuidFactory
IdGenerator → SymfonyUuidV7Generator
```

### Ajustement du fitness test

L’ADR-0007 impose l’implémentation Symfony UID dans `Platform`. La règle
Deptrac autorise donc explicitement :

```text
Platform → SharedKernel
Platform → Symfony
```

Cette autorisation reste limitée à `Platform`. `SharedKernel` demeure sans
dépendance vers Symfony, Doctrine ou API Platform.

### Tests ajoutés

Les tests couvrent :

- la reconstruction et le round-trip d’un UUID v7 ;
- l’égalité par valeur ;
- le rejet d’une chaîne invalide ;
- le rejet d’une autre version UUID ;
- la génération d’identifiants v7 distincts.

Le câblage des aliases privés est validé par la compilation du conteneur
Symfony. Leur consommation de bout en bout sera testée avec le premier service
applicatif qui dépendra de ces contrats.

### Validations exécutées

```bash
composer dump-autoload -o --strict-psr
composer validate --no-check-publish
php bin/phpunit
php bin/console lint:container
vendor/bin/deptrac analyse --config-file=deptrac.layers.php --no-cache
vendor/bin/deptrac analyse --config-file=deptrac.modules.php --no-cache
docker compose build
make lint
make test
make architecture
```

Résultats locaux et Docker :

```text
PHPUnit : OK (9 tests, 14 assertions)
Composer : valide
Conteneur Symfony : valide
Deptrac layers : 0 violation, 0 warning, 0 erreur
Deptrac modules : 0 violation, 0 warning, 0 erreur
Image Docker de production : construite avec Symfony UID
```

### Commit proposé

```text
feat(identity): implement UUID v7 with Symfony UID
```

Le commit est volontairement laissé à l’utilisateur.

## 0.4.3 — Ajouter les identifiants métier typés initiaux

**Statut : TERMINÉ**

### Réalisé

Les identifiants transversaux nécessaires aux premiers bounded contexts sont
disponibles dans `SharedKernel\Identity` :

```text
OrganizationId
StoreId
ProductId
SaleId
StockId
CashSessionId
```

Ils héritent de la primitive immuable `TypedId`, qui encapsule un `Uuid` et
fournit :

```text
fromString(value, UuidFactory)
generate(IdGenerator)
toString()
equals(other)
```

L’égalité exige à la fois la même valeur UUID et le même type métier. Un
`SaleId` et un `ProductId` restent donc différents même lorsqu’ils encapsulent
le même UUID.

Les IDs ne dépendent ni de Symfony, ni de Doctrine. Les classes value object
sont exclues de la découverte automatique des services Symfony ; seules leurs
factories et générateurs sont des services.

### Tests ajoutés

Les tests couvrent pour chacun des six types :

- la reconstruction depuis une chaîne via `UuidFactory` ;
- la génération via `IdGenerator` ;
- la conservation du type concret ;
- la représentation textuelle ;
- l’égalité par type et par valeur.

### Validations exécutées

```bash
composer dump-autoload -o --strict-psr
composer validate --no-check-publish
php bin/phpunit
php bin/console lint:container
vendor/bin/deptrac analyse --config-file=deptrac.layers.php --no-cache
vendor/bin/deptrac analyse --config-file=deptrac.modules.php --no-cache
```

Résultats :

```text
PHPUnit : OK (22 tests, 41 assertions)
Composer : valide
Conteneur Symfony : valide
Deptrac layers : 0 violation, 0 warning, 0 erreur
Deptrac modules : 0 violation, 0 warning, 0 erreur
```

### Commit proposé

```text
feat(identity): add initial typed domain identifiers
```

Le commit est volontairement laissé à l’utilisateur.

## 0.4.4 — Ajouter l’abstraction Decimal

**Statut : TERMINÉ**

### Réalisé

Le namespace `Zandu\SharedKernel\Decimal` expose les primitives :

```text
Decimal
DecimalFactory
RoundingMode
```

Le contrat `Decimal` définit les opérations exactes d’addition, soustraction
et multiplication, ainsi que la comparaison, l’égalité et la représentation
textuelle.

Les opérations susceptibles de perdre de la précision imposent explicitement :

```text
divide(divisor, scale, roundingMode)
withScale(scale, roundingMode)
```

Les modes d’arrondi disponibles sont :

```text
Unnecessary
Up
Down
Ceiling
Floor
HalfUp
HalfDown
HalfEven
```

`DecimalFactory` accepte uniquement une représentation décimale sous forme de
chaîne. Aucun `float`, type `Brick\Math` ou choix de précision globale n’est
exposé par ces contrats.

### Tests ajoutés

Les tests vérifient :

- la surface explicite du contrat `Decimal` ;
- la création uniquement depuis une chaîne ;
- l’obligation d’un mode d’arrondi pour les opérations avec perte possible ;
- l’absence de `float` dans toutes les signatures ;
- l’absence de type `Brick\Math` dans le `SharedKernel` ;
- la liste exhaustive des modes d’arrondi abstraits.

### Validations exécutées

```bash
composer dump-autoload -o --strict-psr
composer validate --no-check-publish
php bin/phpunit
php bin/console lint:container
vendor/bin/deptrac analyse --config-file=deptrac.layers.php --no-cache
vendor/bin/deptrac analyse --config-file=deptrac.modules.php --no-cache
```

Résultats :

```text
PHPUnit : OK (28 tests, 92 assertions)
Composer : valide
Conteneur Symfony : valide
Deptrac layers : 0 violation, 0 warning, 0 erreur
Deptrac modules : 0 violation, 0 warning, 0 erreur
```

### Commit proposé

```text
feat(decimal): add exact decimal abstraction
```

Le commit est volontairement laissé à l’utilisateur.

## 0.4.5 — Implémenter Decimal avec brick/math

**Statut : TERMINÉ**

### Réalisé

`brick/math` 0.14 est installé comme dépendance de production.

L’implémentation réside dans `Platform\Decimal` :

```text
BrickDecimal
BrickDecimalFactory
```

`BrickDecimal` adapte `Brick\Math\BigDecimal` au contrat `Decimal`. Les huit
modes `RoundingMode` du `SharedKernel` sont traduits exhaustivement vers leurs
équivalents Brick sans exposer ceux-ci aux consommateurs.

La factory convertit les chaînes valides en décimaux exacts et traduit les
erreurs de format Brick en `InvalidArgumentException` standard.

Le conteneur Symfony relie :

```text
DecimalFactory → BrickDecimalFactory
```

### Protection architecturale

Le fitness test possède désormais un layer `BrickMath` explicite.

```text
Platform → BrickMath       ✓
SharedKernel → BrickMath   ✗
Domain → BrickMath         ✗
```

La documentation des fitness tests a été mise à jour conformément à
l’ADR-0008.

### Tests ajoutés

Les tests couvrent :

- le rejet d’une chaîne décimale invalide ;
- l’arithmétique exacte, notamment `0.1 + 0.2 = 0.3` ;
- addition, soustraction et multiplication ;
- division avec scale et arrondi explicites ;
- rejet d’un résultat inexact avec `Unnecessary` ;
- traduction des huit modes d’arrondi ;
- égalité et comparaison numériques indépendantes de la scale ;
- détection de zéro et de valeur négative.

### Validations exécutées

```bash
composer dump-autoload -o --strict-psr
composer validate --no-check-publish
php bin/phpunit
php bin/console lint:container
vendor/bin/deptrac analyse --config-file=deptrac.layers.php --no-cache
vendor/bin/deptrac analyse --config-file=deptrac.modules.php --no-cache
```

Résultats :

```text
PHPUnit : OK (41 tests, 114 assertions)
Composer : valide
Conteneur Symfony : valide
Deptrac layers : 0 violation, 0 warning, 0 erreur
Deptrac modules : 0 violation, 0 warning, 0 erreur
```

### Commit proposé

```text
feat(decimal): implement decimal operations with brick math
```

Le commit est volontairement laissé à l’utilisateur.

## 0.4.6 — Ajouter Money et Currency

**Statut : TERMINÉ**

### Réalisé

Le namespace `Zandu\SharedKernel\Money` contient :

```text
Currency
Money
CurrencyMismatch
```

`Currency` encapsule un code alphabétique ASCII de trois caractères et le
normalise en majuscules. Cette validation garantit la forme du code sans
introduire prématurément un catalogue externe de devises.

`Money` associe obligatoirement :

```text
Decimal amount
Currency currency
```

Le montant est créé depuis une chaîne au travers de `DecimalFactory`. Les
additions, soustractions et comparaisons entre devises différentes lèvent
`CurrencyMismatch`. L’égalité tient compte à la fois du montant numérique et
de la devise.

Les multiplications, divisions et changements d’échelle exigent une scale et
un `RoundingMode` explicites. Aucune scale monétaire globale n’est déduite de
la devise, car cette décision reste conditionnée par le Spike C.

Les montants négatifs restent autorisés au niveau de cette primitive : leur
validité dépend du contexte métier, par exemple un remboursement ou un
mouvement de caisse.

### Tests ajoutés

Les tests couvrent :

- normalisation et validation des codes devise ;
- création exacte depuis une chaîne ;
- addition et soustraction exactes ;
- interdiction des opérations entre devises différentes ;
- multiplication et division avec arrondi explicite ;
- égalité et comparaison sensibles à la devise ;
- absence de `float` dans l’API de `Money`.

### Validations exécutées

```bash
composer dump-autoload -o --strict-psr
composer validate --no-check-publish
php bin/phpunit
php bin/console lint:container
vendor/bin/deptrac analyse --config-file=deptrac.layers.php --no-cache
vendor/bin/deptrac analyse --config-file=deptrac.modules.php --no-cache
```

Résultats :

```text
PHPUnit : OK (55 tests, 163 assertions)
Composer : valide
Conteneur Symfony : valide
Deptrac layers : 0 violation, 0 warning, 0 erreur
Deptrac modules : 0 violation, 0 warning, 0 erreur
```

### Commit proposé

```text
feat(money): add Money and Currency value objects
```

Le commit est volontairement laissé à l’utilisateur.

## 0.4.7 — Ajouter Quantity

**Statut : TERMINÉ**

### Réalisé

La primitive immuable `Zandu\SharedKernel\Quantity\Quantity` encapsule un
`Decimal` exact et peut être créée uniquement depuis une chaîne via
`DecimalFactory`.

Elle expose :

```text
add
subtract
multiply
divide
withScale
compareTo
equals
isZero
isNegative
toString
```

Multiplication, division et changement d’échelle exigent une scale et un
`RoundingMode` explicites.

Aucune précision commune ni unité de mesure n’est figée. Les valeurs négatives
restent autorisées dans cette primitive générique afin de représenter des
deltas signés. Les invariants plus stricts, comme `StockQuantity >= 0` et
`MovementQuantity > 0`, appartiendront aux types métier spécialisés des
bounded contexts.

### Tests ajoutés

Les tests couvrent :

- création exacte depuis une chaîne ;
- addition et soustraction exactes ;
- multiplication et division avec arrondi explicite ;
- absence de scale implicite ;
- comparaison, égalité et prédicats numériques ;
- prise en charge des deltas négatifs ;
- absence de `float` dans l’API.

### Validations exécutées

```bash
composer dump-autoload -o --strict-psr
composer validate --no-check-publish
php bin/phpunit
php bin/console lint:container
vendor/bin/deptrac analyse --config-file=deptrac.layers.php --no-cache
vendor/bin/deptrac analyse --config-file=deptrac.modules.php --no-cache
```

Résultats :

```text
PHPUnit : OK (63 tests, 204 assertions)
Composer : valide
Conteneur Symfony : valide
Deptrac layers : 0 violation, 0 warning, 0 erreur
Deptrac modules : 0 violation, 0 warning, 0 erreur
```

### Commit proposé

```text
feat(quantity): add exact Quantity value object
```

Le commit est volontairement laissé à l’utilisateur.

## 0.4.8 — Ajouter les primitives d’exécution nécessaires

**Statut : TERMINÉ**

Les primitives seront introduites par petits lots atomiques selon leur besoin :

```text
Clock
ActorContext
CorrelationId
CausationId
IdempotencyKey
DomainError
Result
```

La première sous-étape sera l’abstraction `Clock`, sans regrouper artificiellement
toutes les primitives dans un même commit.

#### 0.4.8.1 — Ajouter Clock

**Statut : TERMINÉ**

### Réalisé

Le `SharedKernel` expose le contrat :

```text
Clock::now(): DateTimeImmutable
```

`Platform\Time\SystemClock` fournit l’implémentation système et retourne
explicitement l’heure UTC. Le conteneur Symfony relie :

```text
Clock → SystemClock
```

Une implémentation `FrozenClock` est disponible dans les tests afin de rendre
les comportements temporels déterministes sans modifier l’horloge système.

Le contrat dépend uniquement de `DateTimeImmutable`, jamais de Symfony.

### Tests ajoutés

Les tests vérifient :

- que l’horloge système retourne un instant compris entre les bornes mesurées ;
- que le fuseau retourné est UTC ;
- qu’une horloge figée retourne toujours la même instance immuable.

### Validations exécutées

```bash
composer dump-autoload -o --strict-psr
composer validate --no-check-publish
php bin/phpunit
php bin/console lint:container
vendor/bin/deptrac analyse --config-file=deptrac.layers.php --no-cache
vendor/bin/deptrac analyse --config-file=deptrac.modules.php --no-cache
```

Résultats :

```text
PHPUnit : OK (65 tests, 211 assertions)
Composer : valide
Conteneur Symfony : valide
Deptrac layers : 0 violation, 0 warning, 0 erreur
Deptrac modules : 0 violation, 0 warning, 0 erreur
```

### Commit proposé

```text
feat(time): add Clock abstraction
```

Le commit est volontairement laissé à l’utilisateur.

#### Prochaine sous-étape

##### 0.4.8.2 — Ajouter ActorContext

**Statut : TERMINÉ**

### Réalisé

La structure `ActorContext` suit la baseline DDD :

```text
ActorContext
├── ActorId actorId
├── OrganizationId organizationId
├── ActorType actorType
├── UserId? userId
├── SessionId? sessionId
├── CorrelationId correlationId
└── DateTimeImmutable authenticatedAt
```

`ActorType` distingue :

```text
User
ServiceAccount
System
```

Les types `ActorId`, `UserId` et `SessionId` complètent les identifiants typés
du `SharedKernel`. `CorrelationId` a été introduit comme prérequis direct du
contexte d’exécution conformément à la spécification.

`ActorContext` est immuable. Il fournit à l’Application Layer l’acteur et la
portée tenant de confiance ; les futurs adaptateurs d’authentification devront
le construire depuis l’identité authentifiée et jamais depuis les champs
libres d’un payload métier.

Les acteurs `ServiceAccount` et `System` peuvent exister sans `UserId` ni
`SessionId`. Les permissions, rôles et memberships restent hors de cette
primitive et appartiendront au bounded context Identity & Access.

### Tests ajoutés

Les tests vérifient :

- la conservation de l’acteur, du tenant et de la corrélation ;
- les références optionnelles `UserId` et `SessionId` ;
- la prise en charge des acteurs user, service account et system ;
- l’immutabilité du contexte.

### Validations exécutées

```bash
composer dump-autoload -o --strict-psr
composer validate --no-check-publish
php bin/phpunit
php bin/console lint:container
vendor/bin/deptrac analyse --config-file=deptrac.layers.php --no-cache
vendor/bin/deptrac analyse --config-file=deptrac.modules.php --no-cache
```

Résultats :

```text
PHPUnit : OK (69 tests, 223 assertions)
Composer : valide
Conteneur Symfony : valide
Deptrac layers : 0 violation, 0 warning, 0 erreur
Deptrac modules : 0 violation, 0 warning, 0 erreur
```

### Commit proposé

```text
feat(context): add ActorContext and actor identity types
```

Le commit est volontairement laissé à l’utilisateur.

#### Prochaine sous-étape

##### 0.4.8.3 — Compléter les identifiants de causalité

**Statut : TERMINÉ**

### Réalisé

`CausationId` complète `CorrelationId` dans le namespace `Messaging` du
`SharedKernel`. Ces deux identifiants reposent sur l’abstraction UUID v7 et
restent des types distincts, conformément à la baseline et à l’ADR-0013.

Ils expriment deux responsabilités différentes :

- `CorrelationId` relie l’ensemble des opérations d’un même flux ;
- `CausationId` référence l’opération qui a directement causé un message.

### Tests ajoutés

Les tests vérifient :

- la reconstruction des deux identifiants depuis un UUID v7 ;
- leur immutabilité ;
- l’égalité entre identifiants de même type et de même valeur ;
- leur distinction même lorsque leur valeur UUID est identique.

### Validations exécutées

```bash
composer dump-autoload -o --strict-psr
composer validate --no-check-publish
php bin/phpunit
php bin/console lint:container
vendor/bin/deptrac analyse --config-file=deptrac.layers.php --no-cache
vendor/bin/deptrac analyse --config-file=deptrac.modules.php --no-cache
make lint
make test
make architecture
```

Résultats locaux et Docker :

```text
PHPUnit : OK (72 tests, 231 assertions)
Composer : valide
Conteneur Symfony : valide
Deptrac layers : 0 violation, 0 warning, 0 erreur
Deptrac modules : 0 violation, 0 warning, 0 erreur
```

### Commit proposé

```text
feat(messaging): add CausationId
```

Le commit est volontairement laissé à l’utilisateur.

#### Prochaine sous-étape

##### 0.4.8.4 — Ajouter IdempotencyKey

**Statut : TERMINÉ**

### Réalisé

Le `SharedKernel` expose désormais une primitive immuable `IdempotencyKey`.
Elle reste indépendante de `CorrelationId` et `CausationId` : sa valeur est
opaque et n’impose ni UUID ni format propre à un transport.

Ses invariants minimaux sont :

- une valeur non vide et non composée uniquement d’espaces ;
- une longueur maximale de 255 octets ;
- une conservation exacte de la valeur fournie ;
- une égalité sensible à la casse.

Le futur traitement HTTP de l’Epic 0.5 pourra construire cette primitive à
partir de l’en-tête `Idempotency-Key` sans déplacer les règles métier dans
l’adaptateur Symfony.

### Tests ajoutés

Les tests vérifient :

- la conservation d’une valeur opaque ;
- l’égalité exacte et sensible à la casse ;
- le rejet des valeurs vides, blanches ou supérieures à 255 octets ;
- l’acceptation de la longueur maximale ;
- l’immutabilité de la primitive.

### Validations exécutées

```bash
composer dump-autoload -o --strict-psr
composer validate --no-check-publish
php bin/phpunit
php bin/console lint:container
vendor/bin/deptrac analyse --config-file=deptrac.layers.php --no-cache
vendor/bin/deptrac analyse --config-file=deptrac.modules.php --no-cache
make lint
make test
make architecture
```

Résultats locaux et Docker :

```text
PHPUnit : OK (79 tests, 239 assertions)
Composer : valide
Conteneur Symfony : valide
Deptrac layers : 0 violation, 0 warning, 0 erreur
Deptrac modules : 0 violation, 0 warning, 0 erreur
```

### Commit proposé

```text
feat(idempotency): add IdempotencyKey
```

Le commit est réalisé avec l’identité Git configurée de l’utilisateur.

#### Prochaine sous-étape

##### 0.4.8.5 — Ajouter DomainError et Result

**Statut : TERMINÉ**

### Réalisé

`DomainError` porte un code stable en majuscules snake case et un message
humain non vide. Le code est destiné aux décisions des clients ; le message
reste descriptif et ne constitue pas un contrat de branchement.

`Result<T>` représente explicitement le succès avec une valeur ou l’échec avec
un `DomainError`. L’accès à la mauvaise branche échoue immédiatement par une
`LogicException`, afin de révéler une erreur de programmation sans transformer
les erreurs métier attendues en exceptions.

Ces deux primitives sont immuables, génériques par annotations PHPDoc et sans
dépendance Symfony ou Doctrine.

### Tests ajoutés

Les tests vérifient :

- le format et la stabilité du code d’erreur ;
- le rejet des codes invalides et des messages vides ;
- les branches succès et échec de `Result` ;
- l’impossibilité d’accéder à une valeur d’échec ou à l’erreur d’un succès ;
- l’immutabilité des primitives.

### Validations exécutées

```bash
composer dump-autoload -o --strict-psr
composer validate --no-check-publish
php bin/phpunit
php bin/console lint:container
vendor/bin/deptrac analyse --config-file=deptrac.layers.php --no-cache
vendor/bin/deptrac analyse --config-file=deptrac.modules.php --no-cache
make lint
make test
make architecture
```

Résultats locaux et Docker :

```text
PHPUnit : OK (91 tests, 257 assertions)
Composer : valide
Conteneur Symfony : valide
Deptrac layers : 0 violation, 0 warning, 0 erreur
Deptrac modules : 0 violation, 0 warning, 0 erreur
```

### Commits

```text
feat(error): add DomainError and Result primitives
docs(status): close SharedKernel foundation
```

L’Epic 0.4 satisfait sa Definition of Done : les contrats du `SharedKernel`
n’exposent aucune dépendance Symfony ou Brick, les UUID v7 et les décimaux
exacts sont testés, et aucune primitive numérique n’utilise `float`.

# Epic 0.5 — API foundation

**Statut : TERMINÉ**

## Réalisé

- API Platform 4.3 et son intégration Doctrine ORM sont installés ;
- OpenAPI est générable et expose le titre/version de l’API Zandu ;
- la découverte des ressources est limitée à `src/ApiResource` afin de ne pas
  exposer directement les entités de persistence ;
- `ApplicationCommandProcessor` matérialise le flux entrée HTTP → commande →
  `Result<T>` → DTO de sortie ;
- `ApplicationQueryProvider` matérialise le flux variables HTTP → query → read
  model ;
- les `DomainError` deviennent des réponses JSON HTTP 422 avec `code`,
  `message` et `correlationId` ;
- `X-Correlation-ID` est propagé lorsqu’il contient un UUID v7 valide, sinon
  un nouvel identifiant est généré, puis renvoyé dans la réponse ;
- `Idempotency-Key` est converti en `IdempotencyKey` typé et une valeur invalide
  produit le code stable `INVALID_IDEMPOTENCY_KEY`.

## Tests et validations

Les tests couvrent la génération OpenAPI, les conventions Processor/Provider,
la normalisation des erreurs, la propagation de corrélation et le traitement
des clés d’idempotence.

```text
PHPUnit local et Docker : OK (103 tests, 282 assertions)
Composer : valide
Conteneur Symfony : valide
OpenAPI : export JSON réussi
Deptrac layers : 0 violation, 0 warning, 0 erreur
Deptrac modules : 0 violation, 0 warning, 0 erreur
```

## Commits

```text
build(api): add API Platform and OpenAPI
feat(api): add application command processor pattern
feat(api): add application query provider pattern
feat(api): add correlation id propagation
feat(api): add stable application error responses
feat(api): add idempotency key request handling
docs(status): close API foundation
```

# Epic 0.6 — Authentication foundation

**Statut : TERMINÉ**

## Réalisé

- Symfony SecurityBundle et LexikJWTAuthenticationBundle 3.2 sont installés ;
- `/api/auth/login` authentifie un principal Zandu par email/mot de passe ;
- les mots de passe sont comparés par le hasher Symfony et seul un hash de
  bootstrap est fourni par configuration ;
- les access tokens JWT sont courts, signés par une paire de clés générée hors
  Git et portent `actorId`, `organizationId` et `userId` ;
- les clés JWT sont générables par `make auth-keys`, également exécuté en CI ;
- les refresh sessions sont persistées dans PostgreSQL sous le schema
  `identity_access` ;
- seul le SHA-256 du refresh token opaque est persisté ;
- chaque refresh remplace le token courant sous verrou transactionnel ;
- tous les hashes déjà utilisés sont conservés pour détecter un replay ;
- la réutilisation d’un ancien token révoque toute la session ;
- `/api/auth/logout` révoque explicitement la refresh session ;
- `ActorContextResolver` dérive acteur, tenant, user, session, corrélation et
  instant d’authentification exclusivement du principal et du JWT côté serveur.

## Persistence et validations

La migration `Version20260820223000` a été appliquée sur PostgreSQL réel. La
table `identity_access.refresh_session` et son index utilisateur ont été
vérifiés, puis Doctrine a confirmé que les migrations sont à jour.

```text
PHPUnit local et Docker : OK (109 tests, 311 assertions)
Login valide/invalide : testé
JWT utilisé sur une route protégée : testé
Rotation et rejet du replay : testés
Logout/révocation : testés
ActorContext serveur : testé
Composer : valide
Conteneur Symfony : valide
Deptrac layers/modules : 0 violation, 0 warning, 0 erreur
```

## Commits

```text
feat(auth): add JWT access token authentication
feat(auth): add rotating refresh token sessions
feat(auth): resolve ActorContext from authenticated identity
docs(status): close authentication foundation
```

# Epic 0.7 — Spikes architecturaux

**Statut : TERMINÉ**

## Résultats

- Spike A : les effets Sale, Inventory, Cash et Outbox sont atomiques ; trois
  points d’échec injectés laissent zéro effet partiel ;
- Spike B : `SKIP LOCKED`, reprise après crash, at-least-once, retries,
  dead-letter et consumer idempotent sont prouvés ;
- Spike C : le round-trip Decimal/PostgreSQL est exact sur le corpus produit et
  les calculs de taxe, remise, allocation, costing et refund ;
- Spike E : optimistic locking et UPDATE conditionnel protègent tous deux
  `quantity >= 0`, avec UPDATE conditionnel retenu pour le chemin chaud.

Les tests PostgreSQL sont automatiquement précédés de la création et de la
migration de `zandu_test` dans `make test` et donc dans la CI.

## Décisions

- ADR-0014 : `NUMERIC(30,12)` pour quantités/taux/intermédiaires et
  `NUMERIC(30,6)` pour Money persisté ;
- ADR-0015 : UPDATE conditionnel pour la consommation simple de stock ;
- ADR-0016 : transactional outbox, livraison at-least-once et ledger consumer.

## Validation

```text
PHPUnit Docker : OK (128 tests, 371 assertions)
PostgreSQL de test : créé et migré automatiquement
Composer et conteneur Symfony : valides
Deptrac layers/modules : 0 violation, 0 warning, 0 erreur
```

# Epic 0.8 — Operations & observability

**Statut : TERMINÉ**

## Réalisé

- logs JSON structurés enrichis avec corrélation et trace ;
- instrumentation HTTP OpenTelemetry et export OTLP configurable ;
- endpoints `/health/live`, `/health/ready` et `/metrics` ;
- socle de graceful shutdown par gestion des signaux ;
- métriques `outbox_pending_count`, `outbox_oldest_pending_age`,
  `outbox_publish_failures`, `worker_retry_count` et `dead_letter_count` ;
- image de production immuable compilée pour l'environnement `prod` ;
- migration contrôlée, staging local et restauration PostgreSQL intégrés à la
  CI ;
- PHP-CS-Fixer, PHPStan niveau 6 et audit Composer intégrés à la CI.

## Spike G et validations finales

Le Spike G est documenté dans `docs/spikes/lot-0-infrastructure-spike.md`. Le
staging validé est local et utilise l'image de production contre PostgreSQL.
Les choix Render et Grafana Cloud restent `PROPOSED`, car aucun compte externe
n'était nécessaire pour valider les contrats d'infrastructure et OTLP.

```text
PHPUnit Docker : OK (134 tests, 393 assertions)
PHP-CS-Fixer : 0 fichier à corriger
PHPStan niveau 6 : 0 erreur
Deptrac layers/modules : 0 violation, 0 warning, 0 erreur
Composer audit : aucune vulnérabilité connue
Conteneur Symfony : valide
Image Docker production : build réussi
Staging local et readiness PostgreSQL : réussis
Backup/restore PostgreSQL : réussi (2 migrations restaurées)
```

## Commits

```text
feat(observability): add structured logs and OTLP tracing
feat(operations): add health and metrics endpoints
feat(operations): add graceful process shutdown
fix(docker): compile production environment defaults
ci(operations): validate staging and backup restore
style(backend): apply PER-CS formatting
ci(quality): add coding standards and static analysis
ci(security): audit locked dependencies
fix(ci): increase PHPStan memory limit
docs(spike-g): record infrastructure validation
docs(status): close Lot 0 gate
```

---

# Lot 1 — Administration opérationnelle

## Epic 1.1 — Organization foundation

**Statut : TERMINÉ**

### Réalisé

- bounded context `Organization` ajouté avec ses frontières Deptrac ;
- aggregate `Organization` et statuts `ACTIVE`, `SUSPENDED`,
  `CLOSURE_PENDING`, `CLOSED` ;
- value objects `OrganizationName`, `CountryCode`, `Locale` et `TimeZone` ;
- événements de création, modification, suspension, réactivation, demande de
  fermeture et fermeture définitive ;
- lifecycle explicite sans opération métier de suppression ;
- acteurs, timestamps UTC et version d'aggregate conservés à chaque mutation ;
- contrat `OrganizationRepository` et erreur `OrganizationNotFound` ;
- persistance Doctrine ORM isolée dans Infrastructure et migration PostgreSQL
  `Version20260822090000` ;
- use cases `CreateOrganization`, `UpdateOrganization`, `SuspendOrganization`,
  `ReactivateOrganization` et `RequestOrganizationClosure` ;
- chargement tenant-safe fondé sur l'`OrganizationId` de l'`ActorContext`.
- rôle PostgreSQL `zandu_runtime` sans privilège superuser ni `BYPASSRLS` ;
- RLS `ENABLE` et `FORCE` avec policy `USING`/`WITH CHECK` sur la table racine
  des organizations ;
- contexte `app.organization_id` et rôle runtime limités à chaque transaction ;
- tous les use cases Organization exécutés dans une `TenantTransaction` ;
- comportement fail-closed, rejet cross-tenant et absence de fuite après commit
  ou rollback prouvés sur PostgreSQL réel.

### Validations

```text
PHPUnit Docker : OK (157 tests, 451 assertions)
Round-trip Organization/PostgreSQL : OK (1 test, 6 assertions)
PostgreSQL RLS : OK (6 tests, 9 assertions)
Doctrine mapping : 1 entité valide
PHP-CS-Fixer : 0 fichier à corriger
PHPStan niveau 6 : 0 erreur
Deptrac layers/modules : 0 violation, 0 warning, 0 erreur
Conteneur Symfony et Composer : valides
Composer audit : aucune vulnérabilité connue
```

### Commits

```text
docs(planning): add Lot 1 administration backlog
refactor(organization): add bounded context structure
feat(organization): add organization value objects
feat(organization): add organization aggregate lifecycle
feat(organization): add organization repository contract
feat(organization): persist organization aggregate
feat(organization): add create organization use case
refactor(organization): avoid Doctrine layer name collision
feat(organization): add organization lifecycle use cases
docs(status): close Organization foundation
docs(adr): require PostgreSQL row level security
feat(tenant): enable organization row level security
feat(tenant): add transaction scoped RLS context
feat(organization): enforce tenant transactions in use cases
test(tenant): verify runtime role and rollback isolation
docs(status): record Organization RLS implementation
```

## Epic 1.2 — Store foundation

**Statut : TERMINÉ**

### Réalisé

- aggregate `Store` avec identité et code immuables, profil localisé, devise,
  fuseau métier, lifecycle audité en UTC et versionnement ;
- value objects `StoreCode`, `StoreName`, `StoreAddress` et événements de
  création, modification, suspension, réactivation et fermeture ;
- unicité PostgreSQL `(organization_id, code)` et devise obligatoirement égale
  à la devise par défaut de l'organisation ;
- repository tenant-scoped et persistance Doctrine des stores ;
- RLS fail-closed `ENABLE`/`FORCE` sur `organization.stores` ;
- use cases `CreateStore`, `UpdateStore`, `SuspendStore` et `ReactivateStore`,
  tous exécutés dans une `TenantTransaction` ;
- process manager `StoreClosure` avec statuts `REQUESTED`, `IN_PROGRESS`,
  `READY`, `COMPLETED` et `CANCELLED` ;
- workflows `RequestStoreClosure` et `CancelStoreClosure` ;
- contrat public `StoreClosureBlockerProvider`, sans dépendance envers les
  futurs modules, et fournisseur vide utilisé par défaut ;
- persistance Doctrine des fermetures, unicité d'une fermeture active par
  store et RLS fail-closed sur `organization.store_closures` ;
- tests PostgreSQL du round-trip, de l'unicité par tenant et de l'occultation
  cross-tenant des stores et fermetures.

### Validations

```text
PHPUnit Docker : OK (175 tests, 498 assertions)
Tests Store/StoreClosure ciblés : OK (6 tests, 14 assertions)
Migrations développement et test : à jour (Version20260822150000)
Doctrine mapping : valide
PHP-CS-Fixer : 0 fichier à corriger
PHPStan niveau 6 : 0 erreur
Deptrac layers/modules : 0 violation, 0 warning, 0 erreur
Conteneur Symfony et Composer : valides
Composer audit : aucune vulnérabilité connue
```

### Commits

```text
feat(store): add store aggregate and value objects
feat(store): add tenant scoped store repository contract
feat(store): persist store aggregate with RLS
feat(store): add store lifecycle use cases
feat(store): add store closure process manager
```

## Epic 1.3 — Organization invitations

**Statut : TERMINÉ**

### Réalisé

- aggregate `OrganizationInvitation` avec lifecycle `PENDING`, `ACCEPTED`,
  `EXPIRED`, `CANCELLED`, intentions de rôles et événements métier ;
- tokens URL-safe avec 256 bits aléatoires, routage tenant explicite et
  empreinte HMAC seule persistée ;
- persistance Doctrine et RLS fail-closed des invitations ;
- `InviteOrganizationMember` avec organisation active, contrôle provisoire du
  créateur, validation des rôles/stores et conflit d'invitation explicite ;
- annulation et expiration matérialisée par lots ;
- acceptation atomique fondée sur l'utilisateur authentifié, vérification de
  l'email, création/réactivation du membership et usage unique du token ;
- test PostgreSQL réel couvrant hash-only, acceptation et isolation
  cross-tenant.

### Validations

```text
PHPUnit Docker : OK (190 tests, 548 assertions)
Parcours invitation PostgreSQL : OK (1 test, 5 assertions)
Migrations développement et test : à jour (Version20260822190000)
Doctrine mapping : valide
PHP-CS-Fixer : 0 fichier à corriger
PHPStan niveau 6 : 0 erreur
Deptrac layers/modules : 0 violation, 0 warning, 0 erreur
Conteneur Symfony et Composer : valides
Composer audit : aucune vulnérabilité connue
```

### Commits

```text
feat(identity): add organization invitation aggregate
feat(identity): add secure invitation tokens
refactor(identity): make invitation tokens tenant routable
feat(identity): persist organization invitations with RLS
feat(identity): add member invitation use case
feat(identity): handle invitation cancellation and expiration
feat(identity): add invitation acceptance workflow
test(identity): cover invitation acceptance and tenant isolation
```

## Epic 1.4 — Membership lifecycle

**Statut : TERMINÉ**

### Réalisé

- aggregate `OrganizationMembership` tenant-owned avec unicité
  `(organization_id, user_id)` ;
- statuts `INVITED`, `ACTIVE`, `SUSPENDED`, `REVOKED`, acteurs et timestamps
  de lifecycle conservés en UTC ;
- persistance Doctrine et RLS fail-closed ;
- création et réactivation atomiques depuis une invitation acceptée ;
- use cases `SuspendOrganizationMembership`,
  `ReactivateOrganizationMembership` et `RevokeOrganizationMembership` ;
- révocation terminale préservant les références historiques ;
- incrément conjoint de `authorizationVersion` et de la version d'aggregate à
  chaque changement d'accès ;
- claim JWT `authorizationVersion`, propagation dans `ActorContext` et guard
  serveur relisant le membership actif sous RLS ;
- rejet immédiat des versions obsolètes et des memberships non actifs ;
- test PostgreSQL de persistance du lifecycle et d'occultation cross-tenant.

### Validations

```text
PHPUnit Docker : OK (192 tests, 561 assertions)
Parcours PostgreSQL Invitation/Membership : OK (1 test, 8 assertions)
Migrations développement et test : à jour (Version20260822210000)
Doctrine mapping : valide
PHP-CS-Fixer : 0 fichier à corriger
PHPStan niveau 6 : 0 erreur
Deptrac layers/modules : 0 violation, 0 warning, 0 erreur
Conteneur Symfony et Composer : valides
Composer audit : aucune vulnérabilité connue
```

### Commits

```text
feat(identity): add organization membership aggregate
feat(identity): persist organization memberships with RLS
feat(identity): add membership lifecycle
feat(auth): enforce membership authorization version
test(identity): verify membership persistence and tenant isolation
```

## Epic 1.5 — Roles, permissions & scopes

**Statut : TERMINÉ**

### Réalisé

- catalogue fermé des 16 permissions d'administration nécessaires au Lot 1 ;
- aggregate `Role` distinguant les rôles `SYSTEM` globaux et immuables des
  rôles `CUSTOM` tenant-owned et archivables ;
- rôle archivé ne délivrant aucune permission ;
- catalogue stable des rôles système `ORGANIZATION_OWNER`, `STORE_MANAGER`,
  `CASHIER` et `ACCOUNTANT`, sans permissions anticipées des lots futurs ;
- scopes `ORGANIZATION` et `SELECTED_STORES`, avec déduplication des magasins
  et rejet d'un mélange inter-organisation ;
- `RoleAssignment` auditée, optionnellement expirante, tenant compte du rôle
  archivé et du scope lors du calcul d'un droit ;
- protection transactionnelle du dernier owner lors de la suspension et de la
  révocation d'un membership ;
- verrouillage PostgreSQL `FOR UPDATE` des memberships owner actifs pour éviter
  une violation concurrente de l'invariant.
- conversion des intentions d'invitation en `RoleAssignment` typées lors de
  l'acceptation, avec migration réversible des memberships existants ;
- opérations transactionnelles d'attribution et de retrait de rôles, avec
  incrément de l'`authorizationVersion` ;
- codes des rôles système réservés et impossibles à réutiliser par un rôle
  custom archivable ;
- attribution et retrait d'`ORGANIZATION_OWNER` réservés à un owner actif ;
- création atomique du membership owner initial avec toute nouvelle
  organisation, vérifiée sur PostgreSQL réel ;
- décisions applicatives fondées sur les identités de rôles et le catalogue
  typé, sans comparaison brute du nom du rôle.

### Validations

```text
PHPUnit Docker : OK (213 tests, 616 assertions)
Migrations développement et test : à jour (Version20260822230000)
PHP-CS-Fixer : 0 fichier à corriger
PHPStan niveau 6 : 0 erreur
Deptrac layers/modules : 0 violation, 0 warning, 0 erreur
Conteneur Symfony et Composer : valides
Composer audit : aucune vulnérabilité connue
```

### Commits

```text
feat(access): add administration permission catalog
feat(access): add role aggregate
feat(access): seed system roles
feat(access): add organization and store access scopes
feat(access): add role assignments
feat(access): protect last organization owner
feat(access): integrate membership role assignments
feat(access): secure owner role invitations
feat(access): provision initial organization owner
```

## Epic 1.5 bis — User accounts & onboarding

**Statut : TERMINÉ**

Cette étape corrective comble un manque du planning initial : les parcours
d'invitation supposaient l'existence préalable d'un utilisateur authentifié,
sans définir comment une personne obtenait son compte.

### Réalisé

- compte `User` global persistant, email canonique unique, statut et
  organisation par défaut ;
- hachage des mots de passe avec Argon2id ;
- endpoint public `POST /api/auth/register` créant atomiquement le compte, la
  première organisation, le membership actif et le rôle owner initial ;
- création d'organisation supplémentaire non exposée : un owner possède une
  seule organisation tant que le changement d'organisation active n'est pas
  disponible, sans fermer le modèle au multi-organisation futur ;
- endpoint public `POST /api/auth/invitations/{token}/register` créant le
  compte d'une personne invitée puis acceptant l'invitation dans la même
  transaction tenant ;
- provider Symfony chargeant les utilisateurs persistés et leur membership
  actif, avec maintien du provider bootstrap pour la compatibilité actuelle ;
- droits SQL provisoires du rôle `zandu_runtime` sur les comptes globaux ;
- tests API complets allant de l'inscription à l'obtention d'un JWT pour le
  premier owner et pour un invité sans compte.

La récupération de mot de passe, la vérification d'email et le changement
d'organisation active ne font pas partie de cette correction et restent à
planifier dans un durcissement ultérieur de l'authentification.

L'ADR-0018 formalise désormais que `User` est global : sa table n'est pas
tenant-owned et n'est donc pas protégée par une policy RLS. L'accès SQL actuel
de `zandu_runtime` à cette table reste provisoire ; une identité PostgreSQL
d'authentification minimale et distincte doit le remplacer avant la production.

### Validations

```text
PHPUnit Docker : OK (217 tests, 637 assertions)
Parcours API d'onboarding : OK (2 tests, 19 assertions)
Migrations développement et test : à jour (Version20260823050000)
Doctrine mapping : valide
PHP-CS-Fixer : 0 fichier à corriger
PHPStan niveau 6 : 0 erreur
Deptrac layers/modules : 0 violation, 0 warning, 0 erreur
Conteneur Symfony et Composer : valides
Composer audit : aucune vulnérabilité connue
```

### Commits

```text
feat(identity): add persistent user accounts
feat(auth): add user onboarding workflows
```

## Epic 1.5 ter — Authentication security hardening

**Statut : TERMINÉ**

### Réalisé

- compte bootstrap disponible uniquement en `dev` et `test`, sans fallback en
  production ;
- erreur publique stable pour une invitation inconnue ou invalide, sans erreur
  HTTP 500 ;
- secrets de développement sortis de la configuration commune ;
- démarrage HTTP production refusé si `APP_SECRET` ou `JWT_PASSPHRASE` contient
  moins de 32 caractères ;
- rate limiting des endpoints publics d'authentification et d'onboarding avec
  réponses `429` et header `Retry-After` ;
- environnement Docker lu au runtime au lieu d'être figé dans l'image par
  `composer dump-env prod` ;
- fixtures limitées aux environnements `dev` et `test` ;
- ADR-0018 sur l'identité globale, les memberships tenant-owned et la sélection
  future d'une organisation active.

### Dette explicitement reportée avant production

- séparer physiquement la connexion PostgreSQL de migration de la connexion
  runtime `LOGIN NOBYPASSRLS` ;
- réduire l'accès SQL global aux comptes via une identité d'authentification
  dédiée ;
- durcir l'image finale avec un utilisateur non-root et un build multi-stage ;
- fournir les clés JWT via le secret manager de la plateforme de déploiement.

### Validations

```text
PHPUnit Docker : OK (225 tests, 656 assertions)
PHP-CS-Fixer : 0 fichier à corriger
PHPStan niveau 6 : 0 erreur
Deptrac layers/modules : 0 violation, 0 warning, 0 erreur
Conteneur Symfony et Composer : valides
Composer audit : aucune vulnérabilité connue
Image de production reconstruite : valide
Smoke test staging avec secrets injectés au runtime : valide
```

### Commits

```text
fix(auth): disable bootstrap account in production
fix(auth): normalize invalid invitation response
docs(adr): define global identity tenancy model
fix(security): reject weak production secrets
feat(security): rate limit public authentication
refactor(auth): translate invitation lookup failure
fix(container): preserve runtime security environment
```

## Epic 1.6 — Authorization & operational guards

**Statut : TERMINÉ**

### Réalisé

- contrat public `AuthorizationService::authorize()` fondé sur une permission
  atomique et une `ResourceScope`, sans rôle brut dans les handlers métier ;
- résolution des droits depuis l'utilisateur authentifié, son
  `authorizationVersion`, son membership actif, ses attributions non expirées
  et le catalogue typé des rôles système ;
- contrôle serveur des portées organisation, tous magasins et magasins
  sélectionnés ;
- rejet des contextes cross-tenant et des tokens dont la version
  d'autorisation est périmée ;
- `OrganizationOperationalGuard` et `StoreOperationalGuard` centralisés avec
  modes standard, remédiation et terminaison ;
- protection des commandes sensibles d'organisation, de magasin,
  d'invitation, de cycle de vie des memberships et d'attribution de rôles ;
- suppression des politiques d'autorisation fondées sur le créateur de
  l'organisation ; l'invariant du dernier owner reste protégé séparément ;
- une organisation ou un magasin suspendu refuse les nouvelles opérations,
  tout en autorisant les transitions explicites de remédiation ou terminaison.

### Validations

```text
PHPUnit Docker : OK (238 tests, 671 assertions)
PHP-CS-Fixer : 0 fichier à corriger
PHPStan niveau 6 : 0 erreur
Deptrac layers/modules : 0 violation, 0 warning, 0 erreur
Conteneur Symfony et Composer : valides
Composer audit : aucune vulnérabilité connue
```

### Commits

```text
feat(access): add authorization service
feat(organization): add operational guards
refactor(organization): expose operational guard contract
feat(organization): enforce command authorization
feat(access): enforce administration permissions
style(access): normalize code formatting
```

## Epic 1.7 — Security audit & event integration

**Statut : TERMINÉ**

### Réalisé

- modèle immuable `SecurityAuditEntry` avec acteur, cible, outcome, métadonnées
  sûres, corrélation, causalité, session et contexte HTTP optionnel ;
- catalogue complet des actions d'administration du Lot 1, y compris les
  attributions et retraits d'owner ;
- rejet préventif des clés de métadonnées susceptibles de contenir mots de
  passe, tokens, secrets, cookies ou credentials ;
- table `security.security_audit_entries` append-only pour le rôle runtime,
  tenant-scoped et protégée par RLS ;
- outbox applicative tenant-scoped `messaging.outbox_messages`, distincte du
  prototype du Lot 0 et compatible avec la livraison at-least-once ;
- audit obligatoire des créations, modifications, suspensions et
  réactivations d'organisations et magasins, ainsi que des invitations,
  memberships et attributions de rôles ;
- refus d'autorisation persistés dans une transaction séparée après le rollback
  métier, avec réponse HTTP `403` stable et sans donnée sensible ;
- enveloppes d'intégration versionnées par action et propagation des
  `correlationId` et `causationId` depuis les headers HTTP ;
- atomicité métier + audit + outbox démontrée sur PostgreSQL pour le commit et
  le rollback ;
- `make test` force désormais `APP_ENV=test`, indépendamment de l'environnement
  courant du conteneur développeur.

### Validations

```text
PHPUnit Docker : OK (251 tests, 706 assertions)
Migrations développement et test : à jour (Version20260823090000)
PHP-CS-Fixer : 0 fichier à corriger
PHPStan niveau 6 : 0 erreur
Deptrac layers/modules : 0 violation, 0 warning, 0 erreur
Conteneur Symfony et Composer : valides
Composer audit : aucune vulnérabilité connue
```

### Commits

```text
feat(audit): add administration security audit model
feat(audit): persist append-only security entries
feat(outbox): add transactional audit envelope
feat(audit): record successful administration actions
feat(audit): record sensitive authorization denials
test(audit): verify transactional audit atomicity
feat(outbox): propagate causal administration events
refactor(access): share authorization denial contract
fix(test): enforce Symfony test environment
feat(audit): record initial owner assignment
```

## Epic 1.8 — Administration API

**Statut : TERMINÉ**

### Étape 1.8.1 — Organization API

**Statut : TERMINÉE**

### Réalisé

- ressource de présentation API Platform localisée dans le module
  `Organization`, sans aggregate exposé comme CRUD générique ;
- lecture du profil de l'organisation active via
  `GET /api/organizations/{id}` ;
- modification du profil via `PATCH /api/organizations/{id}`, acceptant
  `application/merge-patch+json` et `application/json` ;
- transitions métier explicites de suspension, réactivation et demande de
  fermeture ;
- création d'une organisation supplémentaire volontairement non exposée : la
  première organisation reste créée par `POST /api/auth/register` tant que le
  multi-organisation complet n'est pas disponible ;
- contrôle du tenant, autorisation applicative, audit de sécurité et outbox
  conservés par les handlers existants ;
- frontière de présentation corrigée avec `CurrentActorProvider` et
  `OrganizationView`, sans dépendance directe vers Platform ou Domain.

### Validations

```text
PHPUnit Docker : OK (256 tests, 726 assertions)
PHP-CS-Fixer : 0 fichier à corriger
PHPStan niveau 6 : 0 erreur
Deptrac layers/modules : 0 violation, 0 warning, 0 erreur
Conteneur Symfony et Composer : valides
Composer audit : aucune vulnérabilité connue
```

### Commits

```text
feat(api): expose organization administration endpoints
fix(api): accept json organization patches
fix(api): limit owners to initial organization
refactor(api): enforce organization presentation boundaries
```

### Étape 1.8.2 — Store API

**Statut : TERMINÉE**

### Réalisé

- collection tenant-scoped via `GET /api/stores`, ordonnée de manière stable ;
- création et consultation via `POST /api/stores` et `GET /api/stores/{id}` ;
- modification via `PATCH /api/stores/{id}`, avec des contrats d'entrée
  distincts pour la création et la modification ;
- transitions explicites de suspension, réactivation et demande de fermeture ;
- réponse dédiée à la demande de fermeture exposant son statut et ses éventuels
  blockers ;
- contrôles `STORE_READ` tenant/store-scoped sur les lectures et réutilisation
  des autorisations, gardes opérationnels et transactions des handlers sur les
  commandes ;
- vues applicatives `StoreView` et `StoreClosureView`, sans dépendance de la
  présentation vers les aggregates Domain ;
- documentation OpenAPI vérifiant les sept routes et les formats JSON du
  `PATCH`.

### Validations

```text
PHPUnit Docker : OK (258 tests, 736 assertions)
Tests Store API ciblés : OK (11 tests, 39 assertions)
Routes Symfony Store : 7 opérations enregistrées
PHP-CS-Fixer : 0 fichier à corriger
PHPStan niveau 6 : 0 erreur
Deptrac layers/modules : 0 violation, 0 warning, 0 erreur
Conteneur Symfony et Composer : valides
Composer audit : aucune vulnérabilité connue
```

### Commit

```text
feat(api): expose store administration endpoints
```

### Étape 1.8.3 — Invitation API

**Statut : TERMINÉE**

### Réalisé

- création via `POST /api/member-invitations`, avec email, expiration
  optionnelle et rôles prévus éventuellement limités à des magasins ;
- annulation explicite via
  `POST /api/member-invitations/{id}/cancel` ;
- acceptation authentifiée d'un compte existant via
  `POST /api/invitations/{token}/accept`, conformément à l'ADR-0018 ;
- maintien du parcours public séparé
  `POST /api/auth/invitations/{token}/register` pour une personne sans compte ;
- token brut retourné uniquement dans la réponse de création et `tokenHash`
  absent de toutes les vues, ressources et du contrat OpenAPI ;
- réutilisation des permissions, scopes, gardes opérationnels, transactions et
  validations d'email des handlers applicatifs existants ;
- vues applicatives dédiées à l'invitation créée, annulée et acceptée, sans
  dépendance Presentation vers Domain.

### Validations

```text
PHPUnit Docker : OK (260 tests, 746 assertions)
Tests Invitation API et vues ciblés : OK (7 tests, 32 assertions)
Routes Symfony Invitation : 3 opérations enregistrées
PHP-CS-Fixer : 0 fichier à corriger
PHPStan niveau 6 : 0 erreur
Deptrac layers/modules : 0 violation, 0 warning, 0 erreur
Conteneur Symfony et Composer : valides
Composer audit : aucune vulnérabilité connue
```

### Commit

```text
feat(api): expose organization invitation endpoints
```

### Étape 1.8.4 — Membership API

**Statut : TERMINÉE**

### Réalisé

- collection tenant-scoped et ordonnée via `GET /api/members` ;
- consultation d'un membership via `GET /api/members/{id}` ;
- transitions explicites de suspension, réactivation et révocation ;
- lecture protégée par `MEMBER_READ` et transitions réutilisant les permissions
  `MEMBER_SUSPEND` et `MEMBER_REVOKE` des handlers existants ;
- protection transactionnelle du dernier owner conservée lors de la suspension
  et de la révocation ;
- exposition de `authorizationVersion`, des rôles et de leurs scopes sans
  exposer l'aggregate ni les données d'authentification du compte global ;
- incrément de `authorizationVersion` par les transitions, rendant les anciens
  tokens du membre immédiatement obsolètes ;
- lecture de collection vérifiée sur PostgreSQL réel dans une transaction
  tenant-scoped sous RLS.

### Validations

```text
PHPUnit Docker : OK (261 tests, 753 assertions)
Tests Membership API ciblés : OK (8 tests, 39 assertions)
Routes Symfony Membership : 5 opérations enregistrées
PHP-CS-Fixer : 0 fichier à corriger
PHPStan niveau 6 : 0 erreur
Deptrac layers/modules : 0 violation, 0 warning, 0 erreur
Conteneur Symfony et Composer : valides
Composer audit : aucune vulnérabilité connue
```

### Commit

```text
feat(api): expose membership administration endpoints
```

### Étape 1.8.5 — Role assignment API

**Statut : TERMINÉE**

### Réalisé

- catalogue protégé par `ROLE_READ` via `GET /api/roles`, exposant les quatre
  rôles système, leurs permissions et leur statut ;
- attribution via `POST /api/members/{id}/role-assignments`, avec scope
  `ORGANIZATION` ou `SELECTED_STORES` et expiration optionnelle ;
- retrait explicite via
  `DELETE /api/members/{id}/role-assignments/{assignmentId}` ;
- convention actuelle `assignmentId = roleId`, cohérente avec l'invariant d'une
  seule attribution par rôle et par membership ;
- validation des magasins sélectionnés dans l'organisation active et rejet des
  payloads ambigus combinant scope organisation et magasins ;
- permissions `ROLE_ASSIGN` et `ROLE_REVOKE`, audit de sécurité et garde
  opérationnelle conservés par le service applicatif existant ;
- protection spécifique des attributions owner et invariant du dernier owner
  lors du retrait ;
- retour du membership mis à jour avec nouvelle `authorizationVersion`, rendant
  les anciens tokens immédiatement obsolètes.

### Validations

```text
PHPUnit Docker : OK (262 tests, 756 assertions)
Tests OpenAPI ciblés : OK (8 tests, 34 assertions)
Routes Symfony Role assignment : 3 opérations enregistrées
PHP-CS-Fixer : 0 fichier à corriger
PHPStan niveau 6 : 0 erreur
Deptrac layers/modules : 0 violation, 0 warning, 0 erreur
Conteneur Symfony et Composer : valides
Composer audit : aucune vulnérabilité connue
```

### Commit

```text
feat(api): expose role assignment endpoints
```

### Étape 1.8.6 — OpenAPI et error contract

**Statut : TERMINÉE**

### Réalisé

- enveloppe JSON uniforme avec `code`, `message` et `correlationId` ;
- codes stables `VALIDATION_ERROR`, `UNAUTHENTICATED`, `FORBIDDEN`,
  `NOT_FOUND`, `CONFLICT` et `DOMAIN_RULE_VIOLATION` ;
- point d'entrée de sécurité JSON pour les requêtes non authentifiées ;
- exceptions not-found et conflict identifiées par des contrats du
  SharedKernel, sans couplage Platform vers les modules métier ;
- messages publics génériques empêchant la fuite des détails internes ;
- stratégie `404 NOT_FOUND` conservée pour toute ressource cross-tenant ;
- six réponses d'erreur documentées sur chaque opération d'administration
  dans le contrat OpenAPI, avec schéma et exemple JSON ;
- test HTTP bout en bout des six couples statut/code, des JWT absent et
  invalide, des violations Validator et des JSON malformés ;
- erreurs JWT Lexik, validation API Platform et désérialisation JSON ramenées
  dans la même enveloppe publique ;
- `application/json` désormais négocié explicitement en entrée et en sortie.

### Validations

```text
PHPUnit Docker : OK (271 tests, 865 assertions)
Test HTTP exhaustif du contrat d'erreurs : OK (1 test, 54 assertions)
Tests contrat d'erreurs et OpenAPI ciblés : OK
PHP-CS-Fixer : 0 fichier à corriger
PHPStan niveau 6 : 0 erreur
Deptrac layers/modules : 0 violation, 0 warning, 0 erreur
Conteneur Symfony et Composer : valides
```

### Commits

```text
feat(api): normalize administration errors
docs(api): document administration API contracts
fix(api): enforce administration HTTP error contract
```

## Durcissement transversal — Refresh sessions tenant-scoped

**Statut : TERMINÉ**

### Réalisé

- chaque refresh session persistée porte désormais l'organisation active et la
  version d'autorisation du membership au moment du login ;
- la rotation conserve cette portée tenant et cette version ;
- le renouvellement compare la session au principal actuellement chargé et
  refuse de délivrer un access token si l'organisation ou la version
  d'autorisation a changé ;
- la session de remplacement est révoquée en cas de principal obsolète ;
- la migration `Version20260824223000` rattache les sessions existantes à leur
  membership actif, supprime celles qui ne peuvent pas être rattachées et rend
  les nouvelles colonnes obligatoires ;
- un test d'intégration valide la persistance DBAL et le parcours HTTP prouve
  qu'une modification de `authorizationVersion` invalide le refresh token.

### Validations

```text
PHPUnit Docker : OK (274 tests, 983 assertions)
Tests ciblés refresh/onboarding/persistance : OK (9 tests, 54 assertions)
PHP-CS-Fixer : 0 fichier à corriger
PHPStan niveau 6 : 0 erreur
Deptrac layers/modules : 0 violation, 0 warning, 0 erreur
Conteneur Symfony et Composer : valides
Composer audit : aucune vulnérabilité connue
```

## Prochaine étape

### Étape 1.9.1 — Parcours Organization complet

**Statut : TERMINÉE**

### Réalisé

- parcours HTTP réel depuis l'inscription publique jusqu'à l'authentification
  JWT de l'owner ;
- vérification en PostgreSQL de l'organisation créée avec le statut `ACTIVE` ;
- vérification du membership owner `ACTIVE`, de son unique rôle système
  `ORGANIZATION_OWNER` et de son scope `ORGANIZATION` ;
- consultation authentifiée de l'organisation active via l'API ;
- preuve qu'une organisation étrangère est masquée par `404 NOT_FOUND`.

### Validations

```text
PHPUnit Docker : OK (270 tests, 811 assertions)
Parcours onboarding API ciblé : OK (3 tests, 26 assertions)
PHP-CS-Fixer : 0 fichier à corriger
PHPStan niveau 6 : 0 erreur
Deptrac layers/modules : 0 violation, 0 warning, 0 erreur
```

### Commit

```text
test(organization): cover organization bootstrap workflow
```

## Prochaine étape

### Étape 1.9.2 — Parcours multi-store

**Statut : TERMINÉE**

### Réalisé

- scénario HTTP/PostgreSQL avec deux organisations et deux magasins dans le
  tenant A ;
- rejet `409 CONFLICT` d'un code magasin dupliqué dans la même organisation ;
- création réussie du même code dans une organisation différente ;
- attribution du rôle `STORE_MANAGER` au scope `SELECTED_STORES` sur A1 et A2 ;
- renouvellement du JWT après incrément de `authorizationVersion` ;
- cycle de vie de A1 vérifié : suspension, réactivation puis demande de
  fermeture `READY` sans blocker ;
- collection du tenant A limitée à ses deux magasins malgré la présence du
  magasin homonyme dans le tenant B.

### Validations

```text
PHPUnit Docker : OK (272 tests, 912 assertions)
Parcours multi-store ciblé : OK (1 test, 47 assertions)
PHP-CS-Fixer : 0 fichier à corriger
PHPStan niveau 6 : 0 erreur
Deptrac layers/modules : 0 violation, 0 warning, 0 erreur
Conteneur Symfony et Composer : valides
Composer audit : aucune vulnérabilité connue
```

### Commit

```text
test(store): cover multi-store administration workflow
```

## Prochaine étape

### Étape 1.9.3 — Parcours invitation

**Statut : TERMINÉE**

### Réalisé

- parcours HTTP complet owner → invitation → acceptation par un compte
  existant authentifié ;
- membership créé `ACTIVE` dans l'organisation invitante et rôle `CASHIER`
  appliqué au scope `ORGANIZATION` ;
- token déjà utilisé, email authentifié incorrect, invitation annulée et
  invitation expirée rejetés en `422 DOMAIN_RULE_VIOLATION` ;
- annulation cross-tenant masquée en `404 NOT_FOUND` ;
- désérialisation des `roleAssignments` imbriqués corrigée pour le payload JSON
  réellement reçu par API Platform ;
- variable URI publique `{token}` reliée explicitement à la metadata de la
  ressource afin que l'endpoint d'acceptation atteigne son processor.

### Validations

```text
PHPUnit Docker : OK (273 tests, 977 assertions)
Parcours invitation ciblé : OK (1 test, 65 assertions)
PHP-CS-Fixer : 0 fichier à corriger
PHPStan niveau 6 : 0 erreur
Deptrac layers/modules : 0 violation, 0 warning, 0 erreur
Conteneur Symfony et Composer : valides
Composer audit : aucune vulnérabilité connue
```

### Commits

```text
fix(api): make invitation endpoints executable
test(identity): cover invitation lifecycle
```

## Prochaine étape

### Étape 1.9.4 — Parcours permissions et scopes

**Statut : TERMINÉE**

### Réalisé

- parcours HTTP complet owner → création de deux magasins → invitation d'un
  nouvel utilisateur comme `STORE_MANAGER` limité au magasin A ;
- inscription de l'invité sans compte puis authentification dans le tenant de
  l'organisation invitante ;
- modification du magasin A autorisée avec le scope `SELECTED_STORES` ;
- modification du magasin B refusée avec `403 FORBIDDEN` ;
- absence de mutation du magasin B vérifiée directement dans PostgreSQL après
  le refus.

### Validations

```text
PHPUnit Docker : OK (275 tests, 1007 assertions)
Parcours permissions/scopes ciblé : OK (1 test, 24 assertions)
PHP-CS-Fixer : 0 fichier à corriger
PHPStan niveau 6 : 0 erreur
Deptrac layers/modules : 0 violation, 0 warning, 0 erreur
Conteneur Symfony et Composer : valides
Composer audit : aucune vulnérabilité connue
```

### Commit

```text
test(access): verify store scoped authorization
```

## Prochaine étape

### Étape 1.9.5 — Invariant du dernier owner

**Statut : TERMINÉE**

### Réalisé

- parcours HTTP avec un unique membership owner actif ;
- suspension et révocation du dernier owner rejetées en
  `422 DOMAIN_RULE_VIOLATION` ;
- maintien du membership en statut `ACTIVE` vérifié dans PostgreSQL après
  chaque tentative refusée ;
- invitation et inscription d'un second `ORGANIZATION_OWNER` actif ;
- révocation de ce second owner autorisée, avec conservation du premier owner
  actif.

### Validations

```text
PHPUnit Docker : OK (276 tests, 1034 assertions)
Parcours invariant du dernier owner ciblé : OK (1 test, 27 assertions)
PHP-CS-Fixer : 0 fichier à corriger
PHPStan niveau 6 : 0 erreur
Deptrac layers/modules : 0 violation, 0 warning, 0 erreur
Conteneur Symfony et Composer : valides
Composer audit : aucune vulnérabilité connue
```

### Commit

```text
test(access): enforce last active owner invariant
```

## Prochaine étape

### Étape 1.9.6 — Isolation tenant

**Statut : TERMINÉE**

### Réalisé

- parcours HTTP avec deux organisations, leurs owners, un magasin et une
  invitation dans le tenant B ;
- organisation B, magasin B et membership B masqués à l'owner A par
  `404 NOT_FOUND` ;
- annulation de l'invitation B par l'owner A également masquée par
  `404 NOT_FOUND` ;
- lookups applicatifs exercés avec l'identifiant du tenant actif et celui de la
  ressource étrangère ;
- vérification SQL directe sous le rôle restreint `zandu_runtime` et le contexte
  du tenant A : aucune ligne B visible dans les tables organisations, magasins,
  memberships et invitations.

### Validations

```text
PHPUnit Docker : OK (277 tests, 1067 assertions)
Parcours isolation tenant ciblé : OK (1 test, 33 assertions)
PHP-CS-Fixer : 0 fichier à corriger
PHPStan niveau 6 : 0 erreur
Deptrac layers/modules : 0 violation, 0 warning, 0 erreur
Conteneur Symfony et Composer : valides
Composer audit : aucune vulnérabilité connue
```

### Commit

```text
test(tenant): verify strict tenant isolation
```

## Prochaine étape

### Étape 1.9.7 — Révocation immédiate

**Statut : TERMINÉE**

### Réalisé

- parcours HTTP avec un `STORE_MANAGER` authentifié et limité à un magasin ;
- modification sensible autorisée avant chaque transition de membership ;
- suspension puis révocation incrémentant chacune `authorizationVersion` ;
- access tokens émis avant ces transitions immédiatement rejetés en
  `401 UNAUTHENTICATED`, avant l'exécution de l'opération métier ;
- réactivation suivie d'une nouvelle authentification prouvant qu'un token
  portant la version courante rétablit l'accès ;
- absence de mutation du magasin vérifiée directement dans PostgreSQL après
  chaque rejet.

### Validations

```text
PHPUnit Docker : OK (278 tests, 1111 assertions)
Parcours révocation immédiate ciblé : OK (1 test, 44 assertions)
PHP-CS-Fixer : 0 fichier à corriger
PHPStan niveau 6 : 0 erreur
Deptrac layers/modules : 0 violation, 0 warning, 0 erreur
Conteneur Symfony et Composer : valides
Composer audit : aucune vulnérabilité connue
```

### Commit

```text
test(auth): verify immediate membership revocation
```

## Prochaine étape

### Étape 1.9.8 — Atomicité audit et outbox

**Statut : TERMINÉE**

### Réalisé

- cas nominal PostgreSQL confirmant le commit conjoint de la mutation métier,
  de l'entrée d'audit et du message outbox ;
- repository d'audit fautif injecté avant toute persistance d'audit ;
- repository outbox fautif injecté après l'écriture de l'audit et avant celle
  du message ;
- exception injectée après les trois écritures et juste avant le commit ;
- dans les trois scénarios d'échec, rollback confirmé sur l'état administratif,
  l'audit et l'outbox, sans effet partiel.

### Validations

```text
PHPUnit Docker : OK (280 tests, 1120 assertions)
Atomicité audit/outbox ciblée : OK (4 tests, 15 assertions)
PHP-CS-Fixer : 0 fichier à corriger
PHPStan niveau 6 : 0 erreur
Deptrac layers/modules : 0 violation, 0 warning, 0 erreur
Conteneur Symfony et Composer : valides
Composer audit : aucune vulnérabilité connue
```

### Commit

```text
test(audit): verify administration transaction atomicity
```

## Epic 1.9 — Résultat

**Statut : TERMINÉ**

Les parcours heureux, erreurs significatives, scopes magasin, invariant du
dernier owner, révocation immédiate, isolation tenant/RLS et atomicité
métier-audit-outbox sont tous couverts sur PostgreSQL réel.

## Prochaine étape

### Gate de sortie du Lot 1 — Administration opérationnelle complète

**Statut : TERMINÉE**

### Réalisé

- les 39 critères de la gate documentaire ont été confrontés au code, aux
  migrations, aux ADR et aux tests puis validés ;
- la suite de démonstration consolidée couvre onboarding, multi-store,
  invitations, scopes, dernier owner, révocation immédiate, isolation tenant et
  atomicité audit/outbox ;
- le RLS activé et forcé est vérifié explicitement sur les sept tables
  tenant-owned du Lot 1 ;
- deux connexions PostgreSQL simultanées prouvent que le contexte tenant reste
  local à la transaction et à la connexion ;
- l'image de staging immuable démarre avec ses secrets runtime ;
- le cycle de sauvegarde/restauration PostgreSQL conserve les 17 schémas
  attendus.

### Validations finales

```text
PHPUnit Docker : OK (282 tests, 1138 assertions)
Démonstration consolidée : OK (13 tests, 283 assertions)
RLS ciblé : OK (8 tests, 27 assertions)
PHP-CS-Fixer : 0 fichier à corriger
PHPStan niveau 6 : 0 erreur
Deptrac layers/modules : 0 violation, 0 warning, 0 erreur
Conteneur Symfony et Composer : valides
Composer audit : aucune vulnérabilité connue
Smoke test staging : OK
Sauvegarde/restauration PostgreSQL : OK (17 schémas)
```

## Prochaine étape recommandée

### Lot 2 — Catalog & basic Pricing

Préparer le catalogue, les produits vendables et le pricing de base sans
anticiper les fondations Inventory, Cash ou Sales des lots suivants.

---

# Lot 2 — Catalog & basic Pricing

```text
Epic 2.1   TERMINÉ   Catalog foundation
Epic 2.2   TERMINÉ   Unit of measure
Epic 2.3   TERMINÉ   Categories
Epic 2.4   TERMINÉ   Product lifecycle
Epic 2.5   EN COURS  Product packaging & barcode
Epic 2.6   À FAIRE   Basic Pricing
Epic 2.7   À FAIRE   Authorization, audit & integration
Epic 2.8   À FAIRE   Catalog & Pricing API
Epic 2.9   À FAIRE   Integration, PostgreSQL & tenant isolation tests
Gate Lot 2 À FAIRE   Catalog & basic Pricing complet
```

## Étape 2.1.1 — Créer le module Catalog

**Statut : TERMINÉE**

### Réalisé

- bounded context `Catalog` matérialisé sous `backend/src/Modules/Catalog` ;
- dossiers `Domain`, `Application/Contract`, `Infrastructure` et
  `Presentation/Api` suivis par Git ;
- aucun composant Inventory, Sales ou Cash Management introduit.

### Validations

```text
Composer : valide
Conteneur Symfony : valide
Deptrac layers/modules : 0 violation, 0 warning, 0 erreur
```

### Commit

```text
refactor(catalog): add bounded context structure
```

## Prochaine étape

### Étape 2.1.2 — Ajouter le schéma PostgreSQL catalog

**Statut : TERMINÉE**

### Réalisé

- migration réversible `Version20260825060000` créant le schéma logique
  `catalog` ;
- aucune table Catalog, Inventory, Cash Management ou Sales créée par
  anticipation ;
- migration appliquée sur les bases de développement et de test.

### Validations

```text
Schéma catalog : présent
Tables dans catalog : 0
PHPUnit Docker : OK (282 tests, 1138 assertions)
```

### Commit

```text
feat(database): add catalog schema
```

## Prochaine étape

### Étape 2.1.3 — Étendre les architecture fitness tests

**Statut : TERMINÉE**

### Réalisé

- layers Deptrac `Catalog` et `CatalogContract` ajoutés ;
- `Application/Contract` exclu des internals Catalog et exposé comme unique
  surface publique ;
- Catalog ne peut accéder qu'à son propre contrat public et n'obtient aucun
  accès direct à Organization, IdentityAccess, Inventory, Sales ou
  CashManagement ;
- règles synchronisées dans `docs/architecture/fitness-tests.md`.

### Validations

```text
PHP-CS-Fixer : 0 fichier à corriger
PHPStan niveau 6 : 0 erreur
Deptrac layers/modules : 0 violation, 0 warning, 0 erreur
```

### Commit

```text
test(architecture): protect catalog boundaries
```

## Epic 2.1 — Résultat

**Statut : TERMINÉ**

Le bounded context Catalog, son schéma PostgreSQL vide et ses frontières
architecturales exécutables sont disponibles sans logique des lots futurs.

## Prochaine étape

### Étape 2.2.1 — Ajouter UnitOfMeasure

**Statut : TERMINÉE**

### Réalisé

- ADR-0019 adoptée : les unités du MVP sont exclusivement tenant-owned, sans
  ligne globale ni modèle hybride implicite ;
- agrégat `UnitOfMeasure` lié obligatoirement à une organisation ;
- identité typée, code et nom normalisés, six dimensions et statuts
  `ACTIVE` / `INACTIVE` ;
- précision bornée de 0 à 12 et mode d'arrondi explicite selon les ADR-0008 et
  ADR-0014 ;
- cycle d'activation et de désactivation, protection contre la sélection d'une
  unité inactive et version optimiste ;
- événements `UnitOfMeasureCreated`, `UnitOfMeasureUpdated`,
  `UnitOfMeasureActivated` et `UnitOfMeasureDeactivated`.

### Validations

```text
PHP-CS-Fixer : 0 fichier à corriger
PHPStan niveau 6 : 0 erreur
Deptrac layers/modules : 0 violation, 0 warning, 0 erreur
PHPUnit ciblé : OK (11 tests, 27 assertions)
PHPUnit complet : OK (293 tests, 1165 assertions)
```

### Commits

```text
docs(architecture): decide tenant-owned units of measure
feat(catalog): add unit of measure model
```

## Prochaine étape

### Étape 2.2.2 — Persistence UnitOfMeasure

**Statut : TERMINÉE**

### Réalisé

- mapping Doctrine isolé dans l'infrastructure Catalog ;
- repository domaine et implémentation Doctrine avec recherche strictement
  tenant-scoped ;
- migration `Version20260825080000` appliquée en développement et en test ;
- table `catalog.units_of_measure` protégée par RLS forcé pour
  `zandu_runtime` ;
- unicité du code par organisation, clés étrangères et contraintes PostgreSQL
  sur dimension, précision, mode d'arrondi, statut et version ;
- verrouillage optimiste Doctrine et rejet explicite des agrégats périmés ;
- test de couverture RLS global étendu à la table Catalog ;
- tests PostgreSQL réels couvrant round-trip, mise à jour, unicité tenant,
  isolation RLS, tri et concurrence optimiste.

### Validations

```text
Migration dev/test : appliquée
Mapping Doctrine : valide
Composer et conteneur Symfony : valides
PHP-CS-Fixer : 0 fichier à corriger
PHPStan niveau 6 : 0 erreur
Deptrac layers/modules : 0 violation, 0 warning, 0 erreur
PHPUnit ciblé Catalog/RLS : OK (13 tests, 39 assertions)
PHPUnit complet : OK (298 tests, 1177 assertions)
Composer audit : aucune vulnérabilité connue
```

### Commit

```text
feat(catalog): persist units of measure
```

## Prochaine étape

### Étape 2.2.3 — Use cases UnitOfMeasure

**Statut : TERMINÉE**

### Réalisé

- commandes et handlers dédiés `CreateUnitOfMeasure`, `UpdateUnitOfMeasure`,
  `ActivateUnitOfMeasure` et `DeactivateUnitOfMeasure` ;
- toutes les opérations exécutées dans une transaction portant le tenant de
  l'acteur ;
- chargement tenant-scoped empêchant l'accès cross-tenant ;
- normalisation des attributs primitifs et conversion explicite des modes
  d'arrondi ;
- détection métier d'un code déjà utilisé dans l'organisation ;
- aucun PATCH générique du statut ni dépendance anticipée vers les permissions
  et l'audit de l'Epic 2.7.

### Validations

```text
PHPUnit ciblé : OK (5 tests, 15 assertions)
PHPUnit complet : OK (303 tests, 1192 assertions)
Composer et conteneur Symfony : valides
PHP-CS-Fixer : 0 fichier à corriger
PHPStan niveau 6 : 0 erreur
Deptrac layers/modules : 0 violation, 0 warning, 0 erreur
Composer audit : aucune vulnérabilité connue
```

### Commit

```text
feat(catalog): add unit of measure management
```

## Epic 2.2 — Résultat

**Statut : TERMINÉ**

Le modèle d'unité de mesure tenant-owned, sa persistence PostgreSQL/RLS et ses
quatre opérations métier dédiées sont disponibles. La précision est bornée,
l'arrondi est explicite et une unité inactive ne peut pas être sélectionnée.

## Prochaine étape

### Étape 2.3.1 — Ajouter l'aggregate Category

**Statut : TERMINÉE**

### Réalisé

- agrégat `Category` tenant-owned avec identité typée et tenant immuable ;
- nom obligatoire et normalisé, parent optionnel et statuts `ACTIVE`,
  `INACTIVE`, `ARCHIVED` ;
- audit de création et de dernière modification avec dates normalisées en UTC ;
- contrôle du tenant du parent, de l'auto-parentage et des cycles à partir de la
  chaîne complète des ancêtres du parent ;
- opérations explicites de mise à jour, déplacement, activation,
  désactivation et archivage ;
- archivage terminal : catégorie non sélectionnable mais toujours résolvable
  pour l'historique ;
- événements `CategoryCreated`, `CategoryUpdated`, `CategoryMoved`,
  `CategoryActivated`, `CategoryDeactivated` et `CategoryArchived`.

### Validations

```text
PHPUnit ciblé : OK (10 tests, 31 assertions)
PHPUnit complet : OK (313 tests, 1223 assertions)
Composer et conteneur Symfony : valides
PHP-CS-Fixer : 0 fichier à corriger
PHPStan niveau 6 : 0 erreur
Deptrac layers/modules : 0 violation, 0 warning, 0 erreur
Composer audit : aucune vulnérabilité connue
```

### Commit

```text
feat(catalog): add category aggregate
```

## Prochaine étape

### Étape 2.3.2 — Persistence Category

**Statut : TERMINÉE**

### Réalisé

- mapping Doctrine et repository Category tenant-scoped ;
- reconstruction de la chaîne d'ancêtres, du parent immédiat jusqu'à la
  racine, avec détection défensive des cycles persistés ;
- migration `Version20260825100000` appliquée en développement et en test ;
- table `catalog.categories` protégée par RLS forcé pour `zandu_runtime` ;
- clé étrangère composite garantissant que le parent appartient au même tenant ;
- contraintes PostgreSQL sur l'auto-parentage, le nom, le statut, la cohérence
  de l'audit et la version ;
- index tenant et tenant/parent ;
- verrouillage optimiste et rejet des agrégats périmés ;
- test de couverture RLS global étendu aux catégories ;
- tests PostgreSQL réels couvrant hiérarchie, ancêtres, cycle injecté,
  isolation tenant, parent cross-tenant, lifecycle et concurrence optimiste.

### Validations

```text
Migration dev/test : appliquée
Mapping Doctrine : valide
PHPUnit ciblé Category/RLS : OK (14 tests, 40 assertions)
PHPUnit complet : OK (319 tests, 1234 assertions)
Composer et conteneur Symfony : valides
PHP-CS-Fixer : 0 fichier à corriger
PHPStan niveau 6 : 0 erreur
Deptrac layers/modules : 0 violation, 0 warning, 0 erreur
Composer audit : aucune vulnérabilité connue
```

### Commit

```text
feat(catalog): persist categories
```

## Prochaine étape

### Étape 2.3.3 — Use cases Category

**Statut : TERMINÉE**

### Réalisé

- commandes et handlers dédiés `CreateCategory`, `UpdateCategory`,
  `MoveCategory`, `ActivateCategory`, `DeactivateCategory` et
  `ArchiveCategory` ;
- toutes les opérations exécutées dans une transaction tenant-scoped ;
- chargement des catégories et parents limité au tenant de l'acteur ;
- création et déplacement protégés par un verrou transactionnel PostgreSQL de
  hiérarchie propre à l'organisation ;
- reconstruction de la chaîne complète des ancêtres sous verrou avant chaque
  déplacement ;
- rejet d'un déplacement sous un descendant, y compris face aux mutations
  concurrentes sérialisées ;
- aucun changement générique de statut ni dépendance anticipée vers l'Epic 2.7.

### Validations

```text
Verrou PostgreSQL : testé dans une transaction tenant réelle
PHPUnit ciblé handlers/repository : OK (12 tests, 26 assertions)
PHPUnit complet : OK (325 tests, 1251 assertions)
Composer et conteneur Symfony : valides
PHP-CS-Fixer : 0 fichier à corriger
PHPStan niveau 6 : 0 erreur
Deptrac layers/modules : 0 violation, 0 warning, 0 erreur
Composer audit : aucune vulnérabilité connue
```

### Commit

```text
feat(catalog): add category management
```

## Epic 2.3 — Résultat

**Statut : TERMINÉ**

Les catégories tenant-owned disposent d'un modèle hiérarchique, d'une
persistence PostgreSQL/RLS et de six opérations métier dédiées. Les cycles sont
interdits dans le domaine, détectés lors du parcours des données persistées et
prévenus lors des déplacements concurrents par sérialisation tenant.

## Prochaine étape

### Étape 2.4.1 — Ajouter l'aggregate Product

**Statut : TERMINÉE**

### Réalisé

- agrégat `Product` tenant-owned représentant un SKU autonome, sans variante ;
- statuts `DRAFT`, `ACTIVE`, `INACTIVE`, `ARCHIVED` et types `PHYSICAL`,
  `SERVICE` ;
- création systématique en brouillon et audit complet de création, première
  activation et dernière modification ;
- règle `SERVICE` incompatible avec `inventoryTracked=true` ;
- première activation conditionnée par la présence explicite d'un packaging de
  base ;
- code produit et unité de base définitivement immuables après la première
  activation, y compris lorsque le produit est inactif ;
- désactivation, réactivation et archivage terminal sans suppression métier ;
- produit inactif ou archivé toujours résolvable pour l'historique mais refusé
  pour une nouvelle opération commerciale ;
- événements `ProductCreated`, `ProductUpdated`, `ProductActivated`,
  `ProductDeactivated`, `ProductReactivated` et `ProductArchived`.

Les primitives code/nom sont temporairement normalisées par l'agrégat ; leur
extraction dans les value objects dédiés appartient à l'étape 2.4.2.

### Validations

```text
PHPUnit ciblé : OK (7 tests, 30 assertions)
PHPUnit complet : OK (332 tests, 1281 assertions)
Composer et conteneur Symfony : valides
PHP-CS-Fixer : 0 fichier à corriger
PHPStan niveau 6 : 0 erreur
Deptrac layers/modules : 0 violation, 0 warning, 0 erreur
Composer audit : aucune vulnérabilité connue
```

### Commit

```text
feat(catalog): add product aggregate lifecycle
```

## Prochaine étape

### Étape 2.4.2 — Ajouter les value objects Product

**Statut : TERMINÉE**

### Réalisé

- `ProductCode` extrait avec trim, normalisation Unicode en majuscules,
  obligation de présence et longueur maximale de 64 caractères ;
- alphabet volontairement permissif pour accepter les conventions SKU métier
  sans imposer une syntaxe absente du cadrage ;
- égalité métier de `ProductCode` utilisée pour garantir son immutabilité après
  activation ;
- `ProductName` extrait avec normalisation des espaces, obligation de présence
  et longueur maximale de 160 caractères ;
- agrégat `Product` entièrement adapté aux deux value objects ;
- aucune duplication résiduelle des règles code/nom dans l'agrégat et aucun
  wrapper ajouté pour les propriétés sans invariant propre.

### Validations

```text
PHPUnit ciblé Product : OK (10 tests, 35 assertions)
PHPUnit complet : OK (335 tests, 1286 assertions)
Composer et conteneur Symfony : valides
PHP-CS-Fixer : 0 fichier à corriger
PHPStan niveau 6 : 0 erreur
Deptrac layers/modules : 0 violation, 0 warning, 0 erreur
Composer audit : aucune vulnérabilité connue
```

### Commit

```text
feat(catalog): add product value objects
```

## Prochaine étape

### Étape 2.4.3 — Persistence Product

**Statut : TERMINÉE**

### Réalisé

- mapping Doctrine dédié `ProductRecord` et repository spécialisé, sans
  repository générique ;
- recherches strictement tenant-scoped par identifiant et par `ProductCode` ;
- migration PostgreSQL avec unicité `(organization_id, product_code)`, index de
  lecture, version optimiste et contraintes d'intégrité métier ;
- clés étrangères composites garantissant que l'unité de base et la catégorie
  appartiennent au même tenant que le produit ;
- `tax_category_id` conservé sans clé étrangère tant que le modèle fiscal n'est
  pas défini dans le cadrage ;
- RLS activée et forcée pour le rôle restreint `zandu_runtime` ;
- tests PostgreSQL couvrant le round-trip, l'unicité tenant, le RLS, les
  références cross-tenant et le rejet des écritures optimistes obsolètes.

### Validations

```text
PHPUnit ciblé Product + RLS : OK (14 tests, 42 assertions)
PHPUnit complet : OK (341 tests, 1297 assertions)
Mapping Doctrine : valide
Composer et conteneur Symfony : valides
PHP-CS-Fixer : 0 fichier à corriger
PHPStan niveau 6 : 0 erreur
Deptrac layers/modules : 0 violation, 0 warning, 0 erreur
Composer audit : aucune vulnérabilité connue
```

### Commit

```text
feat(catalog): persist product aggregate
```

## Prochaine étape

### Étape 2.4.4 — Use case `CreateProduct`

**Statut : TERMINÉE**

### Réalisé

- commande `CreateProduct` ne recevant ni `organizationId` ni `actorId` en
  entrée libre : les deux proviennent de l'`ActorContext` ;
- handler entièrement exécuté dans la transaction tenant ;
- permission atomique `PRODUCT_CREATE` ajoutée au catalogue et contrôlée sur le
  scope organisation ;
- statut opérationnel du tenant contrôlé avant toute création ;
- unicité applicative du `ProductCode` complétant la contrainte PostgreSQL ;
- unité de base et catégorie éventuelle chargées dans le tenant courant puis
  refusées lorsqu'elles ne sont pas sélectionnables ;
- produit systématiquement créé en `DRAFT` avec identifiant généré, audit acteur
  et horodatage serveur ;
- règle `SERVICE`/`inventoryTracked` conservée dans le domaine et couverte au
  niveau du use case ;
- dépendances Catalog vers IdentityAccess et Organization limitées à leurs
  contrats applicatifs et explicitement vérifiées par Deptrac.

### Validations

```text
PHPUnit ciblé CreateProduct/permissions : OK (7 tests, 45 assertions)
PHPUnit complet : OK (346 tests, 1326 assertions)
Composer et conteneur Symfony : valides
PHP-CS-Fixer : 0 fichier à corriger
PHPStan niveau 6 : 0 erreur
Deptrac layers/modules : 0 violation, 0 warning, 0 erreur
Composer audit : aucune vulnérabilité connue
```

### Commit

```text
feat(catalog): add create product use case
```

## Prochaine étape

### Étape 2.4.5 — Update Product

**Statut : TERMINÉE**

### Réalisé

- commande d'intention `UpdateProduct` limitée au profil métier et ne contenant
  aucun champ de mutation générique du statut ;
- `TenantProductLoader` garantissant le chargement du produit dans
  l'organisation de l'`ActorContext` ;
- permission atomique `PRODUCT_UPDATE` contrôlée sur le scope organisation ;
- statut opérationnel du tenant contrôlé dans la transaction ;
- contrôle d'unicité acceptant le code du produit courant mais refusant celui
  détenu par un autre produit ;
- unité de base et catégorie éventuelle rechargées dans le tenant courant et
  obligatoirement sélectionnables ;
- immutabilité du code et de l'unité de base après première activation déléguée
  aux invariants de l'agrégat ;
- audit de modification et version optimiste mis à jour par le domaine.

### Validations

```text
PHPUnit ciblé Product/permissions : OK (10 tests, 60 assertions)
PHPUnit complet : OK (349 tests, 1341 assertions)
Composer et conteneur Symfony : valides
PHP-CS-Fixer : 0 fichier à corriger
PHPStan niveau 6 : 0 erreur
Deptrac layers/modules : 0 violation, 0 warning, 0 erreur
Composer audit : aucune vulnérabilité connue
```

### Commit

```text
feat(catalog): add product profile update
```

## Prochaine étape

### Étape 2.4.6 — Activate Product

**Statut : TERMINÉE**

### Réalisé

- commande d'intention `ActivateProduct` sans état cible fourni par le client ;
- chargement tenant-safe du produit et de son unité de base dans une transaction
  tenant ;
- permission atomique `PRODUCT_ACTIVATE` contrôlée sur le scope organisation ;
- statut opérationnel du tenant et sélectionnabilité de l'unité contrôlés avant
  activation ;
- contrat `BasePackagingPresence` imposant une vérification serveur du packaging
  de base pour le produit et son unité ;
- adaptateur provisoire `UnavailableBasePackagingPresence` strictement
  fail-closed : aucune activation en production n'est possible avant le
  branchement de la persistence `ProductPackaging` prévu en Epic 2.5 ;
- première activation, audit, version et transition d'état appliqués par
  l'agrégat seulement lorsque toutes les validations réussissent.

### Validations

```text
PHPUnit ciblé Product/permissions : OK (12 tests, 77 assertions)
PHPUnit complet : OK (351 tests, 1358 assertions)
Composer et conteneur Symfony : valides
PHP-CS-Fixer : 0 fichier à corriger
PHPStan niveau 6 : 0 erreur
Deptrac layers/modules : 0 violation, 0 warning, 0 erreur
Composer audit : aucune vulnérabilité connue
```

### Commit

```text
feat(catalog): add product activation
```

## Prochaine étape

### Étape 2.4.7 — Deactivate / Reactivate / Archive Product

**Statut : TERMINÉE**

### Réalisé

- commandes et handlers distincts `DeactivateProduct`, `ReactivateProduct` et
  `ArchiveProduct` ;
- permissions `PRODUCT_DEACTIVATE`, `PRODUCT_ACTIVATE` et `PRODUCT_ARCHIVE`
  contrôlées selon l'intention ;
- chargement tenant-safe, contrôle opérationnel et mutation exécutés dans la
  même transaction tenant ;
- transitions `ACTIVE → INACTIVE → ACTIVE → ARCHIVED` couvertes ;
- archivage terminal garanti par l'agrégat, sans suppression métier ni commande
  générique de changement de statut ;
- audit acteur/temps, version optimiste et événements de domaine conservés pour
  chaque transition.

### Validations

```text
PHPUnit ciblé Product/permissions : OK (13 tests, 94 assertions)
PHPUnit complet : OK (352 tests, 1375 assertions)
Composer et conteneur Symfony : valides
PHP-CS-Fixer : 0 fichier à corriger
PHPStan niveau 6 : 0 erreur
Deptrac layers/modules : 0 violation, 0 warning, 0 erreur
Composer audit : aucune vulnérabilité connue
```

### Commit

```text
feat(catalog): add product availability lifecycle
```

## Epic 2.4 — Résultat

**Statut : TERMINÉ**

Le SKU autonome `Product` dispose désormais de ses invariants, value objects,
événements, persistence PostgreSQL/RLS, verrouillage optimiste et use cases de
création, mise à jour et cycle de disponibilité. L'activation est volontairement
fail-closed jusqu'au branchement du packaging de base réel en Epic 2.5.

## Prochaine étape

### Étape 2.5.1 — Ajouter `ProductPackaging`

**Statut : TERMINÉE**

### Réalisé

- agrégat tenant-owned `ProductPackaging` rattaché à un `Product` et identifié
  par le nouveau type fort `ProductPackagingId` ;
- value objects dédiés pour le code, le nom, le facteur de conversion et la
  précision métier ;
- décimaux exacts exclusivement fondés sur `Decimal` et `Quantity`, sans
  `float`, conformément aux ADR-0008 et ADR-0014 ;
- facteur de conversion strictement positif et limité à 12 décimales ;
- quantités minimale et d'incrément strictement positives et compatibles avec
  la précision du packaging ;
- unité, facteur de conversion et précision immuables afin qu'un packaging
  historique ne soit jamais réinterprété ;
- réglages commerciaux modifiables avec audit et version optimiste ;
- disponibilités vente/achat indépendantes et cycle
  `ACTIVE → INACTIVE → ACTIVE → ARCHIVED` terminal ;
- reconstruction complète prévue pour la future persistence Doctrine.

La validation exacte de la quantité convertie appartient à l'étape 2.5.3, qui
introduira le service de conversion pure prévu par le cadrage.

### Validations

```text
PHPUnit ciblé ProductPackaging : OK (9 tests, 33 assertions)
PHPUnit complet : OK (361 tests, 1408 assertions)
Composer et conteneur Symfony : valides
PHP-CS-Fixer : 0 fichier à corriger
PHPStan niveau 6 : 0 erreur
Deptrac layers/modules : 0 violation, 0 warning, 0 erreur
Composer audit : aucune vulnérabilité connue
```

### Commit

```text
feat(catalog): add product packaging
```

## Prochaine étape

### Étape 2.5.2 — Base packaging

**Statut : TERMINÉE**

### Réalisé

- rôle de packaging de base rendu explicite par `isBase`, sans inférence depuis
  le code, le nom ou le facteur ;
- factories distinctes `createBase` et `createAdditional` empêchant les appels
  ambigus ;
- organisation, produit et unité d'un packaging de base directement dérivés du
  `Product`, rendant impossible une référence incohérente à la construction ;
- facteur du packaging de base obligatoirement égal numériquement à `1`, quelle
  que soit sa représentation décimale exacte (`1`, `1.0`, `1.000`, etc.) ;
- rôle base, unité et facteur immuables après création ;
- modèle de reconstruction étendu pour la future persistence.

L'unicité globale « exactement un packaging de base par produit » nécessite une
vue de l'ensemble des packagings : elle sera protégée par repository et index
PostgreSQL partiel à l'étape 2.5.4. L'activation reste fail-closed tant que ce
branchement persistant n'existe pas.

### Validations

```text
PHPUnit ciblé ProductPackaging : OK (11 tests, 41 assertions)
PHPUnit complet : OK (363 tests, 1416 assertions)
Composer et conteneur Symfony : valides
PHP-CS-Fixer : 0 fichier à corriger
PHPStan niveau 6 : 0 erreur
Deptrac layers/modules : 0 violation, 0 warning, 0 erreur
Composer audit : aucune vulnérabilité connue
```

### Commit

```text
feat(catalog): enforce base product packaging
```

## Prochaine étape

### Étape 2.5.3 — Conversion de quantité

**Statut : TERMINÉE**

### Réalisé

- service de domaine pur `ProductPackagingQuantityConverter` calculant
  `enteredQuantity × conversionFactor` uniquement avec les abstractions
  décimales exactes ;
- aucun arrondi appliqué, y compris pour la vérification de l'incrément ;
- quantité entrée obligatoirement positive, supérieure ou égale au minimum et
  multiple exact de `quantityIncrement` ;
- précision de saisie vérifiée contre celle du packaging ;
- précision du résultat vérifiée contre celle de l'unité de base, limitée à la
  décision ADR de 12 décimales ;
- erreur métier `IncompatiblePackagingQuantity` distinguant les principaux
  motifs de rejet ;
- couverture des entiers, décimaux, facteurs non entiers, précision maximale et
  résidus incompatibles.

### Validations

```text
PHPUnit ciblé ProductPackaging : OK (18 tests, 54 assertions)
PHPUnit complet : OK (370 tests, 1429 assertions)
Composer et conteneur Symfony : valides
PHP-CS-Fixer : 0 fichier à corriger
PHPStan niveau 6 : 0 erreur
Deptrac layers/modules : 0 violation, 0 warning, 0 erreur
Composer audit : aucune vulnérabilité connue
```

### Commit

```text
feat(catalog): add exact packaging quantity conversion
```

## Prochaine étape

### Étape 2.5.4 — Persistence ProductPackaging

**Statut : TERMINÉE**

### Réalisé

- mapping Doctrine et repository spécialisé tenant-scoped avec verrouillage
  optimiste ;
- décimaux persistés en `NUMERIC(30,12)` conformément à l'ADR-0014 ;
- clés étrangères composites protégeant les références produit et unité contre
  les associations cross-tenant ;
- unicité du code par produit et index PostgreSQL partiel garantissant au plus un
  packaging de base par produit ;
- contraintes SQL sur facteur, précision, quantités, statut et audit ;
- RLS activée et forcée pour `zandu_runtime` ;
- repository branché comme implémentation réelle de `BasePackagingPresence`, ce
  qui remplace l'adaptateur provisoire fail-closed et permet l'activation d'un
  produit possédant un packaging de base valide ;
- tests PostgreSQL du round-trip, du contrat de présence, de l'unicité base et
  extension du fitness test RLS.

### Validations

```text
PHPUnit ciblé persistence/RLS/activation : OK (20 tests, 117 assertions)
PHPUnit complet : OK (371 tests, 1435 assertions)
Migrations développement et test : appliquées
Composer et conteneur Symfony : valides
PHP-CS-Fixer : 0 fichier à corriger
PHPStan niveau 6 : 0 erreur
Deptrac layers/modules : 0 violation, 0 warning, 0 erreur
Composer audit : aucune vulnérabilité connue
```

### Commit

```text
feat(catalog): persist product packaging
```

## Prochaine étape

### Étape 2.5.5 — Ajouter les barcodes

**Statut : TERMINÉE**

### Réalisé

- agrégat tenant-owned `ProductBarcode` rattaché à son produit et à son
  packaging, avec ownership dérivé du packaging à la création ;
- value object `Barcode` conservant la valeur textuelle et ses zéros initiaux,
  sans aucune interprétation numérique ;
- normalisation dédiée à la comparaison et unicité PostgreSQL de
  `(organization_id, normalized_barcode)` ;
- retrait historique terminal via le statut `REMOVED`, avec acteur, date,
  version optimiste et événements `ProductBarcodeAdded` /
  `ProductBarcodeRemoved` ;
- mapping Doctrine et repository tenant-scoped permettant la recherche par
  barcode normalisé ;
- clés étrangères composites garantissant que produit, packaging et barcode
  appartiennent au même tenant et que le packaging appartient au produit ;
- migration appliquée en développement et en test, contraintes SQL, droits du
  rôle runtime et RLS activée et forcée ;
- tests du comportement domaine, de la normalisation et extension du fitness
  test PostgreSQL RLS.

### Validations

```text
PHPUnit complet : OK (373 tests, 1445 assertions)
Migrations développement et test : appliquées
Composer et conteneur Symfony : valides
PHP-CS-Fixer : 0 fichier à corriger
PHPStan niveau 6 : 0 erreur
Deptrac layers/modules : 0 violation, 0 warning, 0 erreur
Composer audit : aucune vulnérabilité connue
```

### Commit

```text
feat(catalog): add product barcodes
```

## Prochaine étape

### Étape 2.5.6 — Barcode resolver

**Statut : TERMINÉE**

### Réalisé

- contrat applicatif `BarcodeResolver` acceptant explicitement
  `OrganizationId` et `Barcode` ;
- résultat immutable `BarcodeResolution` exposant uniquement `ProductId` et
  `ProductPackagingId` ;
- implémentation repository-backed utilisant la recherche normalisée et
  tenant-scoped ;
- seuls les barcodes actifs sont résolus, un barcode retiré étant traité comme
  introuvable ;
- aucune distinction observable entre barcode absent et barcode appartenant à
  un autre tenant : les deux produisent `NOT_FOUND` (`null`) ;
- tests couvrant la normalisation, la résolution active, le retrait et
  l'isolation cross-tenant.

### Validations

```text
PHPUnit ciblé BarcodeResolver : OK (3 tests, 5 assertions)
PHPUnit complet : OK (376 tests, 1450 assertions)
Composer et conteneur Symfony : valides
PHP-CS-Fixer : 0 fichier à corriger
PHPStan niveau 6 : 0 erreur
Deptrac layers/modules : 0 violation, 0 warning, 0 erreur
Composer audit : aucune vulnérabilité connue
```

### Commit

```text
feat(catalog): add barcode resolution
```

## Epic 2.5 — Product Packaging & Barcode

**Statut : TERMINÉ**

La Definition of Done de l'Epic est satisfaite : packaging de base explicite,
conversion exacte sans arrondi silencieux, barcodes textuels tenant-uniques et
résolution tenant-safe persistée sous PostgreSQL.

## Prochaine étape

### Étape 2.6.1 — Créer le sous-domaine / module logique Pricing

**Statut : TERMINÉE**

### Réalisé

- bounded context autonome `Pricing` matérialisé sous `Modules/Pricing`, avec
  les couches `Application`, `Domain`, `Infrastructure` et `Presentation/Api` ;
- surface publique `Application/Contract` préparée sans contrat artificiel ni
  dépendance cross-context prématurée ;
- layers Deptrac `Pricing` et `PricingContract` ajoutés ;
- seule la dépendance interne `Pricing → PricingContract` est autorisée ;
- aucun accès direct de Pricing aux aggregates, repositories ou tables internes
  de Catalog ; les futurs liens utiliseront les identifiants partagés ;
- documentation des fitness tests alignée sur la frontière exécutable.

### Validations

```text
PHPUnit complet : OK (376 tests, 1450 assertions)
Composer et conteneur Symfony : valides
PHP-CS-Fixer : 0 fichier à corriger
PHPStan niveau 6 : 0 erreur
Deptrac layers/modules : 0 violation, 0 warning, 0 erreur
Composer audit : aucune vulnérabilité connue
```

### Commit

```text
refactor(pricing): add basic pricing structure
```

## Prochaine étape

### Étape 2.6.2 — Ajouter PriceList

**Statut : TERMINÉE**

### Réalisé

- agrégat tenant-owned `PriceList` avec tenant immutable, identifiant typé,
  code, nom, devise, statut, scope, période, priorité et version ;
- scope minimal strictement limité à `ORGANIZATION`, sans moteur multi-store
  anticipé ;
- value objects normalisés pour le code, le nom et la priorité entière
  non négative ;
- période inclusive normalisée en UTC avec invariant `validTo >= validFrom` ;
- cycle de vie explicite `DRAFT → ACTIVE ↔ INACTIVE → ARCHIVED`, archivage
  terminal et listes archivées non sélectionnables ;
- sélection temporelle déterministe limitée aux listes actives dans leur fenêtre
  de validité ;
- événements `PriceListCreated`, `PriceListUpdated`, `PriceListActivated`,
  `PriceListDeactivated` et `PriceListArchived` ;
- tests du modèle, des invariants, des événements et du cycle de vie.

L'unicité du code par organisation sera rendue transactionnellement sûre par
la contrainte PostgreSQL de l'étape de persistence 2.6.3.

### Validations

```text
PHPUnit ciblé PriceList : OK (6 tests, 34 assertions)
PHPUnit complet : OK (382 tests, 1484 assertions)
Composer et conteneur Symfony : valides
PHP-CS-Fixer : 0 fichier à corriger
PHPStan niveau 6 : 0 erreur
Deptrac layers/modules : 0 violation, 0 warning, 0 erreur
Composer audit : aucune vulnérabilité connue
```

### Commit

```text
feat(pricing): add price list aggregate
```

## Prochaine étape

### Étape 2.6.3 — Persistence PriceList

**Statut : TERMINÉE**

### Réalisé

- schéma PostgreSQL dédié `pricing`, cohérent avec la frontière du bounded
  context ;
- mapping Doctrine `PriceListRecord` et enregistrement explicite du namespace
  Pricing dans la configuration ORM ;
- repository `PriceListRepository` tenant-scoped avec `get`, `find` et
  `findByCode`, implémenté par Doctrine ;
- verrouillage optimiste fondé sur la version de l'agrégat et `#[ORM\Version]` ;
- unicité transactionnelle `(organization_id, code)` et index préparant la
  résolution par statut et priorité ;
- contraintes SQL sur la devise, le statut, le scope organisationnel, la
  période, la priorité et la version ;
- clé étrangère vers l'organisation, droits minimaux du rôle runtime et RLS
  activée et forcée ;
- migrations développement et test appliquées ;
- tests PostgreSQL du round-trip et de la version, de l'unicité intra-tenant,
  de la réutilisation du code inter-tenant et de l'absence de fuite ;
- fitness test RLS étendu à `pricing.price_lists`.

### Validations

```text
PHPUnit ciblé persistence/RLS : OK (11 tests, 48 assertions)
PHPUnit complet : OK (385 tests, 1495 assertions)
Migrations développement et test : appliquées
Mapping Doctrine : valide
Composer et conteneur Symfony : valides
PHP-CS-Fixer : 0 fichier à corriger
PHPStan niveau 6 : 0 erreur
Deptrac layers/modules : 0 violation, 0 warning, 0 erreur
Composer audit : aucune vulnérabilité connue
```

### Commit

```text
feat(pricing): persist price lists
```

## Prochaine étape

### Étape 2.6.4 — Ajouter ProductPrice

**Statut : TERMINÉE**

### Réalisé

- agrégat tenant-owned `ProductPrice` avec identifiant typé, `PriceListId`,
  `ProductId`, `ProductPackagingId`, montant exact, période, statut et version ;
- cible `ProductPriceTarget` fondée uniquement sur les identifiants partagés,
  sans dépendance de Pricing vers les aggregates internes de Catalog ;
- organisation du prix dérivée de la `PriceList` et validation explicite du
  tenant de la cible ;
- devise du montant obligatoirement identique à celle de la liste ;
- devise de `PriceList` rendue immutable afin de préserver cet invariant pour
  tous les prix existants ;
- politique de montant décidée et appliquée : montant positif ou nul, jamais
  négatif, sans calcul flottant ;
- période inclusive normalisée en UTC avec invariant `validTo >= validFrom` ;
- cycle de vie `ACTIVE ↔ INACTIVE → ARCHIVED`, archivage terminal et sélection
  temporelle explicite ;
- identité de la liste, du produit et du packaging immutable lors des mises à
  jour ;
- événements `ProductPriceCreated`, `ProductPriceUpdated`,
  `ProductPriceActivated`, `ProductPriceDeactivated` et
  `ProductPriceArchived` ;
- tests du tenant, de la devise, du montant, de la période, de l'immutabilité,
  des événements et du cycle de vie.

La validation applicative/persistante que le packaging appartient réellement au
produit sera branchée à l'étape suivante sans ouvrir Pricing sur l'interne de
Catalog. La gestion transactionnelle des chevauchements sera également portée
par la persistence et le futur resolver.

### Validations

```text
PHPUnit ciblé Pricing domain : OK (14 tests, 63 assertions)
PHPUnit complet : OK (393 tests, 1524 assertions)
Composer et conteneur Symfony : valides
PHP-CS-Fixer : 0 fichier à corriger
PHPStan niveau 6 : 0 erreur
Deptrac layers/modules : 0 violation, 0 warning, 0 erreur
Composer audit : aucune vulnérabilité connue
```

### Commit

```text
feat(pricing): add product price aggregate
```

## Prochaine étape

### Étape 2.6.5 — Persistence ProductPrice

**Statut : TERMINÉE**

### Réalisé

- mapping Doctrine `ProductPriceRecord` avec montant exact
  `NUMERIC(30,12)` conformément à l'ADR-0014 ;
- repository tenant-scoped `ProductPriceRepository` avec `get`, `find`,
  reconstitution des objets `Money` et verrouillage optimiste ;
- clé composite `(organization_id, price_list_id, currency)` garantissant que
  le prix, sa liste, son tenant et sa devise restent cohérents ;
- clé composite `(organization_id, product_id, packaging_id)` garantissant que
  le packaging appartient réellement au produit et au tenant ciblés ;
- contraintes SQL sur montant non négatif, devise, statut, période et version ;
- extension `btree_gist` et contrainte d'exclusion empêchant deux périodes
  `ACTIVE` de se chevaucher pour une même liste et un même packaging, bornes
  inclusives comprises ;
- index tenant-scoped adapté à la future résolution par produit, packaging,
  statut et liste ;
- RLS activée et forcée, droits minimaux accordés au rôle runtime ;
- migration appliquée en développement et en test ;
- tests PostgreSQL du round-trip exact, de la version, du non-chevauchement et
  de la cohérence produit/packaging ;
- fitness test RLS étendu à `pricing.product_prices`.

### Validations

```text
PHPUnit ciblé persistence/RLS : OK (11 tests, 49 assertions)
PHPUnit complet : OK (396 tests, 1534 assertions)
Migrations développement et test : appliquées
Mapping Doctrine : valide
Composer et conteneur Symfony : valides
PHP-CS-Fixer : 0 fichier à corriger
PHPStan niveau 6 : 0 erreur
Deptrac layers/modules : 0 violation, 0 warning, 0 erreur
Composer audit : aucune vulnérabilité connue
```

### Commit

```text
feat(pricing): persist product prices
```

## Prochaine étape

### Étape 2.6.6 — Price resolver de base

**Statut : TERMINÉE**

### Réalisé

- contrat public `ProductPriceResolver` exposé par `PricingContract`, sans
  dépendance du futur module Sales vers le domaine interne Pricing ;
- entrée explicitement tenant-scoped : organisation, produit, packaging et
  instant métier ;
- résultat immutable `ResolvedProductPrice` contenant `PriceListId`,
  `ProductPriceId`, montant décimal exact, devise et version source ;
- résolution limitée aux prix et listes `ACTIVE` dont les périodes inclusives
  couvrent l'instant métier ;
- sélection déterministe par priorité de liste décroissante, puis
  `PriceListId` et `ProductPriceId` croissants comme départage stable ;
- recherche Doctrine tenant-scoped exploitant l'index préparé à l'étape 2.6.5 ;
- absence de prix représentée par `ProductPriceNotFound`, compatible avec le
  contrat d'erreur `NOT_FOUND`, sans valeur zéro ni prix deviné ;
- tests du résultat exact et versionné, de l'absence explicite et de la
  sélection PostgreSQL par priorité.

### Validations

```text
PHPUnit ciblé resolver/persistence : OK (6 tests, 16 assertions)
PHPUnit complet : OK (399 tests, 1543 assertions)
Composer et conteneur Symfony : valides
PHP-CS-Fixer : 0 fichier à corriger
PHPStan niveau 6 : 0 erreur
Deptrac layers/modules : 0 violation, 0 warning, 0 erreur
Composer audit : aucune vulnérabilité connue
```

### Commit

```text
feat(pricing): add basic product price resolution
```

## Prochaine étape

### Étape 2.6.7 — Préparer les snapshots futurs

**Statut : TERMINÉE**

### Réalisé

- contrat public Catalog `SaleablePackagingSnapshotProvider` exposant seulement
  produit, packaging, facteur de conversion exact et version source ;
- implémentation Catalog tenant-scoped vérifiant l'appartenance au produit et
  le caractère vendable du packaging avant de produire le snapshot ;
- contrat public Pricing `PricingSnapshotResolver` consommable par le futur
  module Sales sans dépendance vers les domaines Catalog ou Pricing ;
- `PricingSnapshot` immutable regroupant `ProductId`, `ProductPackagingId`,
  facteur de packaging, `PriceListId`, `ProductPriceId`, montant exact, devise
  et versions des trois sources ;
- composition applicative du snapshot à partir des contrats Catalog/Pricing et
  du repository interne de listes de prix ;
- dépendance `Pricing → CatalogContract` explicitement limitée et documentée
  dans les fitness tests, sans accès direct au domaine Catalog ;
- aucune classe `Sale`, `SaleLine` ou autre anticipation du Domain Sales ;
- test vérifiant l'intégralité et l'exactitude du snapshot transactionnel futur.

### Validations

```text
PHPUnit ciblé PricingSnapshot : OK (1 test, 10 assertions)
PHPUnit complet : OK (400 tests, 1553 assertions)
Composer et conteneur Symfony : valides
PHP-CS-Fixer : 0 fichier à corriger
PHPStan niveau 6 : 0 erreur
Deptrac layers/modules : 0 violation, 0 warning, 0 erreur
Composer audit : aucune vulnérabilité connue
```

### Commit

```text
feat(pricing): expose pricing snapshot contract
```

## Epic 2.6 — Basic Pricing

**Statut : TERMINÉ**

La Definition of Done est satisfaite : listes et prix tenant-owned persistés,
montants exacts, devise cohérente, périodes non ambiguës, résolution
déterministe, absence explicite et contrats de snapshot publics.

La fiscalité reste dans le cas B du planning : aucune règle nationale n'est
documentée à ce stade. `taxCategoryId?` demeure une référence optionnelle du
produit et aucune résolution fiscale n'est inventée avant décision explicite.

## Prochaine étape

### Étape 2.7.1 — Étendre les permissions Catalog/Pricing

**Statut : TERMINÉE**

### Réalisé

- catalogue typé `PermissionCode` étendu avec `CATALOG_READ` ;
- permissions de création, mise à jour et archivage des catégories ;
- permissions produit complétées avec `PRODUCT_READ`, les permissions de
  mutation déjà utilisées restant inchangées ;
- permissions de création, lecture, mise à jour, activation et archivage des
  listes de prix ;
- permissions de création, lecture, mise à jour et archivage des prix produit ;
- aucun code `STOCK_*`, `SALE_*`, `CASH_*` ou `PURCHASE_*` introduit avant les
  lots correspondants ;
- test exhaustif de l'ordre et du contenu du catalogue, complété par une
  interdiction explicite des préfixes futurs ;
- aucune attribution de rôle modifiée prématurément : cette politique appartient
  à l'étape 2.7.2.

### Validations

```text
PHPUnit ciblé permissions/rôles : OK (2 tests, 51 assertions)
PHPUnit complet : OK (400 tests, 1588 assertions)
Composer et conteneur Symfony : valides
PHP-CS-Fixer : 0 fichier à corriger
PHPStan niveau 6 : 0 erreur
Deptrac layers/modules : 0 violation, 0 warning, 0 erreur
Composer audit : aucune vulnérabilité connue
```

### Commit

```text
feat(access): add catalog and pricing permissions
```

### Étape 2.7.2 — Mettre à jour les rôles système

**Statut : TERMINÉE**

Les rôles système ont été étendus dans le bounded context IdentityAccess selon
le principe du moindre privilège :

- `ORGANIZATION_OWNER` conserve toutes les permissions, dont l'intégralité des
  permissions Catalog/Pricing du Lot 2 ;
- `STORE_MANAGER` reçoit la lecture du catalogue, des produits, des listes de
  prix et des prix produit, sans mutation Catalog/Pricing ;
- `CASHIER` reçoit uniquement les lectures store, catalogue, produit et prix
  produit nécessaires au futur POS ;
- `ACCOUNTANT` reçoit la lecture organization/store/audit ainsi que la lecture
  des listes de prix et des prix produit ;
- aucun rôle non-owner ne reçoit de permission d'administration Catalog/Pricing.

La politique reste centralisée dans `SystemRoleCatalog` : aucun contrôle de rôle
n'a été introduit dans Catalog ou Pricing. Les tests vérifient les attributions
exactes et interdisent explicitement les mutations Catalog/Pricing aux rôles
non-owner.

### Validations

```text
PHPUnit ciblé rôles/autorisations : OK (6 tests, 32 assertions)
PHPUnit complet : OK (400 tests, 1599 assertions)
Composer et conteneur Symfony : valides
PHP-CS-Fixer : 0 fichier à corriger
PHPStan niveau 6 : 0 erreur
Deptrac layers/modules : 0 violation, 0 warning, 0 erreur
Composer audit : aucune vulnérabilité connue
```

### Commit

```text
feat(access): grant catalog permissions to system roles
```

### Étape 2.7.3 — Appliquer `AuthorizationService`

**Statut : TERMINÉE**

Tous les handlers de mutation Catalog actuellement disponibles appellent
désormais `AuthorizationService.authorize(...)` dans leur transaction tenant,
avec une portée organization et une permission correspondant au cas d'usage :

- Category : create, update/move/activate/deactivate et archive ;
- Product : create, update, activate/reactivate, deactivate et archive ;
- UnitOfMeasure : create, update, activate et deactivate.

L'inventaire a identifié que les mutations UnitOfMeasure ne disposaient d'aucune
permission dédiée. Quatre permissions explicites ont donc été ajoutées afin de
ne jamais détourner `CATALOG_READ` pour autoriser une écriture. Elles restent
réservées à `ORGANIZATION_OWNER` via le catalogue des rôles système.

Pricing n'expose pas encore de command handler : ses resolvers sont des contrats
internes de lecture appelés par un cas d'usage consommateur, auquel appartiendra
l'autorisation. Aucun contrôle direct de rôle n'a été introduit.

Les tests vérifient les permissions exactes, la portée organization et le
positionnement de l'autorisation dans la transaction tenant.

### Validations

```text
PHPUnit ciblé Catalog : OK (21 tests, 156 assertions)
PHPUnit complet : OK (400 tests, 1662 assertions)
Composer et conteneur Symfony : valides
PHP-CS-Fixer : 0 fichier à corriger
PHPStan niveau 6 : 0 erreur
Deptrac layers/modules : 0 violation, 0 warning, 0 erreur
Composer audit : aucune vulnérabilité connue
```

### Commits

```text
feat(access): add unit of measure permissions
feat(catalog): enforce catalog authorization
```

### Étape 2.7.4 — Operational guard

**Statut : TERMINÉE**

Toutes les mutations Catalog exigent désormais une organization opérationnelle
via `OperationalGuard.assertTenant(...)`, dans la même transaction tenant que
l'autorisation et la mutation :

- les handlers Product étaient déjà protégés ;
- les handlers Category et UnitOfMeasure ont été complétés ;
- aucune mutation actuelle ne cible un store, donc aucun guard store artificiel
  n'a été introduit.

Les tests vérifient un contrôle tenant en mode `Standard` pour chaque mutation
et échouent si un handler Category/UnitOfMeasure tente d'utiliser une portée
store.

### Validations

```text
PHPUnit ciblé Catalog : OK (21 tests, 182 assertions)
PHPUnit complet : OK (400 tests, 1688 assertions)
Composer et conteneur Symfony : valides
PHP-CS-Fixer : 0 fichier à corriger
PHPStan niveau 6 : 0 erreur
Deptrac layers/modules : 0 violation, 0 warning, 0 erreur
Composer audit : aucune vulnérabilité connue
```

### Commit

```text
feat(catalog): enforce organization operational guard
```

### Étape 2.7.5 — Security audit

**Statut : TERMINÉE**

Les cinq opérations sensibles exigées sont enregistrées via le
`SecurityAuditTrail` transactionnel du Lot 1 :

- `ProductActivated` et `ProductArchived` ;
- `CategoryArchived` ;
- `PriceListActivated` ;
- `ProductPriceUpdated`.

Les handlers Pricing manquants pour l'activation d'une liste et la mise à jour
d'un prix ont été ajoutés. Ils appliquent autorisation, guard opérationnel,
chargement tenant-scoped, mutation, persistence et audit dans une seule
transaction tenant.

Chaque audit transporte l'`ActorContext` complet — tenant, acteur,
correlationId, causationId et session éventuels — ainsi qu'une référence stable
de ressource et des métadonnées sûres vides. Les types d'événements d'intégration
Catalog/Pricing correspondants sont explicitement mappés par l'audit
transactionnel.

Les frontières Deptrac autorisent Pricing à consommer uniquement les contrats
publics d'IdentityAccess et Organization nécessaires à ces contrôles ; la
documentation des fitness tests a été réalignée.

### Validations

```text
PHPUnit ciblé audit Catalog/Pricing : OK (18 tests, 187 assertions)
PHPUnit complet : OK (402 tests, 1742 assertions)
Composer et conteneur Symfony : valides
PHP-CS-Fixer : 0 fichier à corriger
PHPStan niveau 6 : 0 erreur
Deptrac layers/modules : 0 violation, 0 warning, 0 erreur
Composer audit : aucune vulnérabilité connue
```

### Commits

```text
feat(audit): record catalog and pricing operations
docs(architecture): align pricing module dependencies
```

### Étape 2.7.6 — Outbox

**Statut : TERMINÉE**

Les cinq opérations Catalog/Pricing retenues comme événements d'intégration sont
publiées par le `TransactionalSecurityAuditTrail` sous forme d'enveloppes
versionnées :

```text
catalog.product_activated.v1
catalog.product_archived.v1
catalog.category_archived.v1
pricing.price_list_activated.v1
pricing.product_price_updated.v1
```

La mutation métier et l'appel d'audit vivent dans le même `TenantTransaction` ;
l'audit et son message outbox utilisent la même connexion DBAL. Une erreur avant
l'audit, pendant l'écriture outbox ou avant le commit annule donc mutation,
audit et outbox ensemble.

Les enveloppes propagent organizationId, correlationId et causationId. Elles
référencent l'audit et la ressource sans sérialiser directement les agrégats ou
leurs domain events internes, ce qui évite de dupliquer un second message pour
la même opération sensible.

### Validations

```text
PHPUnit ciblé outbox/atomicité/handlers : OK (24 tests, 224 assertions)
PHPUnit complet : OK (403 tests, 1759 assertions)
Composer et conteneur Symfony : valides
PHP-CS-Fixer : 0 fichier à corriger
PHPStan niveau 6 : 0 erreur
Deptrac layers/modules : 0 violation, 0 warning, 0 erreur
Composer audit : aucune vulnérabilité connue
```

### Commit

```text
test(outbox): verify catalog pricing event envelopes
```

## Epic 2.7 — Authorization, audit & integration

**Statut : TERMINÉ**

La Definition of Done est satisfaite : permissions et rôles sont explicites,
les mutations utilisent autorisation et guard opérationnel, les actions
sensibles sont auditées, leur publication outbox est transactionnelle et les
rollbacks sont testés.

### Étape 2.8.1 — API Categories

**Statut : TERMINÉE**

Les huit endpoints Categories sont exposés : collection et item en lecture,
création, modification, déplacement, activation, désactivation et archivage.

La présentation est entièrement contenue dans
`Catalog/Presentation/Api` avec des DTO input/resource, un provider, un
processor et une factory dédiés. Aucun agrégat ni record Doctrine n'est exposé.
Les lectures passent par `CategoryQueryService`, `CATALOG_READ` et une
transaction tenant ; les mutations réutilisent les handlers sécurisés des
étapes 2.7.

Le workflow HTTP couvre la hiérarchie parent/enfant, la remise à la racine et le
cycle de vie complet. Une tentative de lecture inter-tenant retourne le contrat
opaque `404 NOT_FOUND`. `CategoryNotFound` a été alignée sur le marqueur partagé
`ResourceNotFound` afin d'éviter une erreur 500.

Les huit opérations et les formats `application/json` /
`application/merge-patch+json` du PATCH sont présents dans OpenAPI.

### Validations

```text
PHPUnit ciblé HTTP/OpenAPI : OK (11 tests, 103 assertions)
PHPUnit complet : OK (405 tests, 1809 assertions)
Composer et conteneur Symfony : valides
PHP-CS-Fixer : 0 fichier à corriger
PHPStan niveau 6 : 0 erreur
Deptrac layers/modules : 0 violation, 0 warning, 0 erreur
Composer audit : aucune vulnérabilité connue
```

### Commit

```text
feat(api): expose category management
```

### Étape 2.8.2 — API Products

**Statut : TERMINÉE**

Les huit endpoints Products sont exposés dans
`Catalog/Presentation/Api` : lectures collection/item, création, modification,
activation, désactivation, réactivation et archivage. Les agrégats et records
Doctrine restent derrière des vues applicatives et des factories de ressources.

La collection accepte les filtres planifiés `status`, `type`, `categoryId`,
`productCode` et `search`. Le repository applique le scope organization et un
tri déterministe par code ; la recherche porte uniquement sur code et nom.
Les lectures exigent `PRODUCT_READ`, tandis que les mutations réutilisent les
handlers tenant-safe des étapes précédentes.

`ProductNotFound` est alignée sur `ResourceNotFound` pour préserver le contrat
HTTP `404 NOT_FOUND` lors d'une lecture hors tenant.

### Validations

```text
PHPUnit ciblé OpenAPI Products : OK (11 tests, 72 assertions)
PHPUnit complet : OK (406 tests, 1817 assertions)
Composer et conteneur Symfony : valides
PHP-CS-Fixer : 0 fichier à corriger
PHPStan niveau 6 : 0 erreur
Deptrac layers/modules : 0 violation, 0 warning, 0 erreur
Composer audit : aucune vulnérabilité connue
```

### Commit

```text
feat(api): expose product management
```

## Prochaine étape

### Étape 2.8.3 — API ProductPackaging

**Statut : TERMINÉE**

L'API ProductPackaging est complète sous `/api/products/{productId}/packagings` :
lecture collection/item, création, mise à jour commerciale, désactivation et
archivage. Les DTO et processeurs restent dans `Catalog/Presentation/Api`,
avec autorisation tenant-scoped, transaction tenant et garde opérationnelle.
Le facteur de conversion est volontairement immuable lors d'une mise à jour :
un changement de facteur doit créer un nouveau packaging afin de préserver
l'historique.

### Validation

```text
PHPUnit OpenAPI : OK (13 tests, 81 assertions)
PHPUnit complet : OK (408 tests, 1826 assertions)
PHPStan niveau 6 : 0 erreur
Deptrac layers/modules : 0 violation
PHP-CS-Fixer : 0 fichier à corriger
Composer audit : aucune vulnérabilité connue
```

### Commit

```text
feat(api): expose product packaging management
```

### Étape 2.8.4 — API Barcode

**Statut : TERMINÉE**

L'API expose l'ajout et la suppression intentionnelle des codes-barres d'un
conditionnement, ainsi que leur résolution tenant-safe vers le produit et le
packaging. Les zéros initiaux sont conservés et l'unicité est garantie par
tenant sur le barcode normalisé ; la suppression est une inactivation métier.

### Validation

```text
PHPUnit OpenAPI : OK (13 tests, 81 assertions)
PHPUnit complet : OK (408 tests, 1826 assertions)
PHPStan niveau 6 : 0 erreur
Deptrac layers/modules : 0 violation
PHP-CS-Fixer : 0 fichier à corriger
```

### Commit

```text
feat(api): expose barcode management and resolution
```

## Prochaine étape

### Étape 2.8.5 — API PriceList

**Statut : TERMINÉE**

La lecture tenant-scoped des listes de prix est exposée via `GET /api/price-lists`
et `GET /api/price-lists/{id}`, avec tri par code et contrôle `PRICE_LIST_READ`.
La création, la mise à jour, l'activation, la désactivation et l'archivage sont
exposés avec les permissions Pricing dédiées et les invariants du domaine.

### Validation intermédiaire

```text
PHPUnit OpenAPI : OK (14 tests, 88 assertions)
PHPStan niveau 6 : 0 erreur
Deptrac layers/modules : 0 violation
PHP-CS-Fixer : 0 fichier à corriger
```

### Commit

```text
feat(api): complete price list management

## Prochaine étape

### Étape 2.8.6 — API ProductPrice

**Statut : TERMINÉE**

Les lectures tenant-scoped sont exposées via `GET /api/product-prices` et
`GET /api/product-prices/{id}`, avec contrôle `PRODUCT_PRICE_READ` et projection
des montants/devise, périodes et cibles. La création et la mise à jour sont
maintenant exposées via `POST /api/product-prices` et `PATCH /api/product-prices/{id}`.
Les transitions d'activation, désactivation et archivage sont également
disponibles.

### Validation intermédiaire

```text
PHPUnit OpenAPI : OK (15 tests, 95 assertions)
PHPStan niveau 6 : 0 erreur
Deptrac layers/modules : 0 violation
PHP-CS-Fixer : 0 fichier à corriger
```

### Commit

```text
feat(api): complete product price management

## Prochaine étape

### Étape 2.8.7 — API ProductPrice effective resolution

**Statut : TERMINÉE**

La résolution est exposée via `GET /api/products/{productId}/packagings/{packagingId}/effective-price`.
Elle utilise la date métier optionnelle `at`, reste tenant-scoped et applique la
priorité des PriceList actives.

### Validation

```text
PHPUnit OpenAPI : OK (15 tests, 96 assertions)
PHP-CS-Fixer : 0 fichier à corriger
```

### Commit

```text
feat(api): expose effective product price resolution
```
```

## Prochaine étape

### Étape 2.8.8 — Contrat d'erreurs et OpenAPI Catalog/Pricing

**Statut : TERMINÉE**

Le contrat d'erreurs standardisé est maintenant appliqué aux endpoints
Catalog/Pricing, incluant les routes produits, conditionnements, barcodes,
PriceList et ProductPrice. Les tests vérifient les six statuts et codes
documentés (`400`, `401`, `403`, `404`, `409`, `422`).

### Validation

```text
PHPUnit OpenAPI : OK (16 tests, 122 assertions)
PHP-CS-Fixer : 0 fichier à corriger
```

### Commit

```text
test(api): cover catalog pricing error contract
```
```

---

## Prochaine étape

### Étape 2.9.1 — Tests Domain Product

**Statut : TERMINÉE**

Les invariants Product sont couverts sans infrastructure : création en draft,
activation conditionnée par le packaging de base, interdiction des services
stockés, immutabilité du code et de l'unité après activation, cycle de vie
complet et archivage terminal.

### Validation

```text
ProductTest : OK
```

### Commit

```text
test(catalog): cover product invariants
```

## Prochaine étape

### Étape 2.9.2 — Tests UnitOfMeasure & conversion

**Statut : TERMINÉE**

Les tests couvrent les précisions, facteurs exacts (entiers et décimaux),
quantités minimales et incréments, conversions sans arrondi silencieux et les
cas incompatibles avec les précisions packaging/base.

### Validation

```text
UnitOfMeasureTest + ProductPackagingTest : OK (29 tests, 81 assertions)
```

### Commit

```text
test(catalog): cover packaging quantity conversion
```

## Prochaine étape

### Étape 2.9.3 — Tests Category hierarchy

**Statut : TERMINÉE**

La hiérarchie est couverte par des tests de création avec parent tenant-safe,
déplacement, prévention des cycles et cycle de vie (activation, désactivation,
archivage).

### Validation

```text
CategoryTest + CategoryHandlerTest : OK (15 tests, 87 assertions)
```

### Commit

```text
test(catalog): cover category hierarchy
```

## Prochaine étape

### Étape 2.9.4 — Tests PostgreSQL ProductCode

**Statut : TERMINÉE**

Les tests d'intégration PostgreSQL couvrent le round-trip Doctrine, la mise à
jour avec version optimiste, l'unicité du code dans un tenant et sa réutilisation
dans un autre tenant, ainsi que l'isolation RLS et les références cross-tenant.

### Validation

```text
DoctrineProductRepositoryTest : OK (6 tests, 9 assertions)
```

### Commit

```text
test(catalog): verify product code PostgreSQL constraints
```

## Prochaine étape

### Étape 2.9.5 — Tests Barcode

**Statut : TERMINÉE**

Les tests couvrent la conservation du barcode comme chaîne, la normalisation
pour comparaison, l'appartenance au packaging, la suppression terminale et la
résolution tenant-safe (barcode actif, autre tenant et barcode supprimé).

### Validation

```text
ProductBarcodeTest + BarcodeResolverTest : OK (5 tests, 13 assertions)
```

### Commit

```text
test(catalog): cover barcode invariants and resolution
```

## Prochaine étape

### Étape 2.9.6 — Tests Pricing

**Statut : TERMINÉE**

Les tests couvrent les invariants PriceList et ProductPrice, devise et montant,
périodes de validité, ownership tenant, transitions de statut, version
optimiste, sélection par priorité et résolution effective déterministe.

### Validation

```text
Tests Pricing domaine/application/intégration : OK (26 tests, 140 assertions)
```

### Commit

```text
test(pricing): cover price list and product price invariants
```

## Prochaine étape

### Étape 2.9.7 — Tenant isolation applicative

**Statut : TERMINÉE**

Les tests d'intégration démontrent l'absence de fuite entre tenants pour les
repositories Catalog/Pricing, les références cross-tenant et les transactions
tenant. Les cas Product, Category, Packaging, PriceList et ProductPrice sont
couverts, ainsi que les protections RLS existantes.

### Validation

```text
Tests Integration Catalog/Pricing/Tenancy : OK (34 tests, 94 assertions)
```

### Commit

```text
test(tenant): enforce catalog tenant isolation
```

## Prochaine étape

### Étape 2.9.8 — PostgreSQL RLS

**Statut : TERMINÉE**

Les tables tenant-owned du Lot 2 activent et forcent RLS. Un test vérifie
désormais qu'une politique explicite basée sur `app.organization_id` existe
pour chacune des 14 tables, en plus des contrôles de rôle runtime, contexte
absent, isolation de connexions et nettoyage après commit/rollback.

### Validation

```text
PostgresRowLevelSecurityTest : OK (9 tests, 42 assertions)
```

### Commit

```text
test(tenant): verify PostgreSQL RLS policies
```

## Prochaine étape

### Étape 2.9.9 — Authorization tests

**Statut : TERMINÉE**

Les tests couvrent les permissions d'administration Catalog/Pricing pour le
propriétaire, le refus des mutations pour un Store Manager, les scopes magasin,
l'expiration d'affectation, les versions d'autorisation obsolètes et les
scopes cross-tenant.

### Validation

```text
EffectiveAuthorizationServiceTest : OK (7 tests, 9 assertions)
```

### Commit

```text
test(auth): cover catalog pricing authorization matrix
```

## Prochaine étape

### Étape 2.9.10 — API contract tests

**Statut : TERMINÉE**

La suite HTTP/API couvre la génération OpenAPI, les routes Catalog/Pricing,
les payloads et le contrat d'erreurs standardisé, ainsi que les workflows
d'isolation et d'autorisation exposés par l'API.

### Validation

```text
Suite tests/Api : OK (29 tests, 485 assertions)
```

### Commit

```text
test(api): cover catalog pricing contract workflows
```

## Prochaine étape

### Étape 2.9.11 — Atomicité métier, audit et outbox

**Statut : TERMINÉE**

Les scénarios d'échec avant audit, avant outbox et avant commit vérifient le
rollback complet de la mutation métier, de l'audit et de l'outbox. Les tests
couvrent également les retries, le claim concurrent `SKIP LOCKED`, le dead
lettering et l'idempotence consommateur.

### Validation

```text
Tests atomicité/audit/outbox : OK (17 tests, 118 assertions)
```

### Commit

```text
test(catalog): verify transaction atomicity and side effects
```

## Prochaine étape

### Démonstration métier consolidée du Lot 2

**Statut : TERMINÉE**

La gate consolidée est validée par la suite complète : organisation → catalogue
→ packaging → barcode → PriceList → ProductPrice → résolution de prix effectif,
avec isolation tenant, autorisation, RLS et atomicité audit/outbox.

### Validation

```text
Suite complète : OK (414 tests, 1871 assertions)
Migrations PostgreSQL test : OK
```

### Commit

```text
test(lot-2): validate consolidated business flow
```

## Prochaine étape

### Lot 3 — Inventory & Cash foundations

**Statut : EN COURS**

### Étape 3.1.1 — Structure Inventory

**Statut : TERMINÉE**

Le bounded context Inventory est préparé avec une frontière explicite et sans
dépendance de son futur domaine vers Catalog, Organization ou les frameworks.

### Validation

```text
Deptrac layers/modules : 0 violation
```

### Commits

```text
docs(planning): add lot 3 inventory cash foundations
refactor(inventory): prepare bounded context structure
```

## Prochaine étape

### Étape 3.1.2 — Schéma PostgreSQL inventory

**Statut : TERMINÉE**

Les tables tenant-scoped `inventory.stock` et `inventory.stock_movement` sont
créées avec intégrité référentielle, unicité par organisation/store/produit,
quantités décimales, index et politiques RLS fail-closed. Les tables des
transferts, inventaires et réservations restent volontairement hors périmètre.

### Validation

```text
Migration PostgreSQL test : OK
PostgresRowLevelSecurityTest : OK (9 tests, 46 assertions)
Deptrac layers/modules : 0 violation
```

### Commit

```text
feat(database): add inventory stock tables
```

## Prochaine étape

### Étape 3.1.3 — Contrat Catalog pour Inventory

**Statut : TERMINÉE**

Catalog expose `InventoryProductProvider` et `InventoryProductDescriptor`.
Le contrat fournit l’identité produit, le type, le suivi d’inventaire, l’unité
de base et la précision de quantité, sans exposer l’aggregate Product.

### Validation

```text
Conteneur Symfony : OK
Deptrac layers/modules : 0 violation
```

### Commit

```text
feat(catalog): expose inventory product contract
```

## Prochaine étape

### Étape 3.2.1 — Aggregate Stock

**Statut : TERMINÉE**

L’aggregate `Stock` garantit l’identité tenant/store/produit, l’initialisation
explicite, la quantité non négative, les opérations d’augmentation/diminution
et la version optimiste locale. Les identifiants d’appartenance sont immuables.

### Validation

```text
StockTest : OK (2 tests, 3 assertions)
Deptrac layers/modules : 0 violation
```

### Commit

```text
feat(inventory): add stock aggregate
```

## Prochaine étape

### Étape 3.2.2 — Value objects de quantité

**Statut : TERMINÉE**

`StockQuantity` garantit une quantité courante non négative et
`MovementQuantity` garantit une variation strictement positive. L’aggregate
`Stock` utilise désormais ces types dédiés pour ses opérations.

### Validation

```text
StockTest : OK (2 tests, 3 assertions)
Deptrac layers/modules : 0 violation
```

### Commit

```text
feat(inventory): add stock quantity value objects
```

## Prochaine étape

### Étape 3.2.3 — StockRepository

**Statut : TERMINÉE**

Le domaine expose `StockRepository` avec `save`, recherche par position tenant
(organisation/store/produit) et lecture par identifiant. L’exception
`StockNotFound` fournit les deux formes de lookup sans repository générique.

### Validation

```text
StockRepositoryContractTest : OK (1 test, 1 assertion)
Deptrac layers/modules : 0 violation
```

### Commit

```text
feat(inventory): add stock repository contract
```

## Prochaine étape

### Étape 3.2.4 — Persistence Stock

**Statut : TERMINÉE**

Le mapping ORM et `DoctrineStockRepository` persistent l’aggregate avec
précision décimale, unicité tenant/store/produit et version optimiste. Le
contrat est enregistré dans le conteneur Symfony.

### Validation

```text
Doctrine mapping : OK
Conteneur Symfony : OK
Deptrac layers/modules : 0 violation
```

### Commit

```text
feat(inventory): persist stock aggregate
```

## Prochaine étape

### Étape 3.3.1 — Aggregate StockMovement

**Statut : TERMINÉE**

`StockMovement` est immuable, limite les types du Lot 3, dérive la direction
depuis le type et calcule le résultat à partir de la quantité précédente. La
source distingue initialisation et ajustement manuel.

### Validation

```text
StockMovementTest : OK (1 test, 1 assertion)
Deptrac layers/modules : 0 violation
```

### Commit

```text
feat(inventory): add stock movement aggregate
```

## Prochaine étape

### Étape 3.3.2 — StockMovementSource

**Statut : TERMINÉE**

`StockMovementSource` fournit les sources `INITIALIZATION` et
`MANUAL_ADJUSTMENT`, avec une référence optionnelle pour les ajustements.

### Commit

Inclus dans :

```text
feat(inventory): add stock movement aggregate
```

## Prochaine étape

### Étape 3.3.3 — Persistence append-only

**Statut : TERMINÉE**

Les mouvements sont persistés par `DoctrineStockMovementRepository` via une
opération `append` uniquement. La lecture est dédiée par position de stock ;
aucune méthode métier d’update ou de suppression n’est exposée.

### Validation

```text
Mapping Doctrine : OK
Conteneur Symfony : OK
Deptrac layers/modules : 0 violation
```

### Commit

```text
feat(inventory): persist stock movements append-only
```

## Prochaine étape

### Étape 3.4.1 — Stock initialization

**Statut : TERMINÉE**

Le cas d’usage `InitializeStock` vérifie l’éligibilité Catalog, empêche la
seconde initialisation et persiste atomiquement la position ainsi que le
mouvement `INITIAL_STOCK`.

### Validation

```text
Conteneur Symfony : OK
Deptrac layers/modules : 0 violation
```

### Commit

```text
feat(inventory): add initialize stock use case
```

## Prochaine étape

### Étape 3.4.2 — Initialisation à zéro

**Statut : TERMINÉE**

La quantité initiale zéro est explicitement valide : elle représente un stock
suivi mais actuellement épuisé, distinct d’une position non initialisée.

### Validation

```text
StockTest : OK (3 tests, 5 assertions)
```

### Commit

```text
test(inventory): allow zero stock initialization
```

## Prochaine étape

### Étape 3.4.3 — AdjustStock

**Statut : TERMINÉE**

Le cas d’usage `AdjustStock` impose une raison, interdit le delta nul,
détermine le type depuis le signe, refuse un stock non initialisé ou négatif
et append le mouvement correspondant dans la même transaction.

### Validation

```text
Conteneur Symfony : OK
Deptrac layers/modules : 0 violation
```

### Commit

```text
feat(inventory): add stock adjustment use case
```

## Prochaine étape

### Étape 3.4.4 — Contrat de lecture de disponibilité

**Statut : TERMINÉE**

Le contrat `StockAvailabilityProvider` expose uniquement le produit, la
quantité disponible, la version du stock et l’état d’initialisation. Une
position absente est représentée par une quantité zéro non initialisée.

### Validation

```text
Deptrac layers/modules : 0 violation
```

### Commit

```text
feat(inventory): expose stock availability contract
```

## Prochaine étape

### Étape 3.5.1 — Concurrence Stock

**Statut : TERMINÉE**

La décision ADR-0015 est appliquée : les diminutions simples disposent d’un
`UPDATE` DBAL conditionnel vérifiant simultanément la version et la quantité
disponible ; les workflows d’aggregate conservent l’optimistic locking.

### Validation

```text
Conteneur Symfony : OK
Deptrac layers/modules : 0 violation
```

### Commit

```text
feat(inventory): enforce stock concurrency strategy
```

## Prochaine étape

### Étape 3.5.2 — Tests PostgreSQL concurrents

**Statut : TERMINÉE**

Les tests du Spike E utilisent deux connexions PostgreSQL réelles et vérifient
qu’une consommation concurrente incompatible est rejetée sans jamais produire
de quantité négative.

### Validation

```text
StockConcurrencyTest : OK (2 tests, 8 assertions)
```

### Commit

Test déjà présent et validé dans le dépôt.

## Prochaine étape

### Étape 3.5.3 — Idempotence

**Statut : TERMINÉE**

Un index unique PostgreSQL empêche la duplication d’un mouvement portant la
même source, référence, organisation et produit. Cette contrainte constitue
la garantie de base pour les retries des commandes d’inventaire.

### Validation

```text
Migration PostgreSQL test : OK
```

### Commit

```text
feat(inventory): enforce stock operation idempotence
```

## Prochaine étape

### Epic 3.6 — Cash Management foundation

**Statut : EN COURS**

### Étape 3.6.1 — Structure Cash Management

**Statut : TERMINÉE**

Le bounded context Cash Management dispose de sa frontière documentée et de
son répertoire ORM enregistré dans Doctrine, sans logique Payment ou Sales.

### Validation

```text
Conteneur Symfony : OK
Deptrac layers/modules : 0 violation
```

### Commit

```text
refactor(cash): prepare cash management bounded context
```

## Prochaine étape

### Étape 3.6.2 — Schéma PostgreSQL Cash Management

**Statut : TERMINÉE**

Les tables `cash_register`, `cash_session` et `cash_movement` sont créées avec
clés tenant/store, unicités métier, contrainte d’une seule session ouverte,
idempotence des sources, index et RLS fail-closed. Les concepts Payment et
Settlement restent hors périmètre.

### Validation

```text
Migration PostgreSQL test : OK
PostgresRowLevelSecurityTest : OK (9 tests, 52 assertions)
```

### Commit

```text
feat(database): add cash management tables
```

## Prochaine étape

### Epic 3.7 — CashRegister

**Statut : EN COURS**

### Étape 3.7.1 — Aggregate CashRegister

**Statut : TERMINÉE**

L’aggregate `CashRegister` garantit l’appartenance immutable à l’organisation
et au store, valide code/nom, gère les statuts actifs/inactifs/archivés et
interdit la modification d’une caisse archivée.

### Validation

```text
CashRegisterTest : OK (1 test, 2 assertions)
Deptrac layers/modules : 0 violation
```

### Commit

```text
feat(cash): add cash register aggregate
```

## Prochaine étape

### Étape 3.7.2 — Persistence CashRegister

**Statut : TERMINÉE**

Le mapping `CashRegisterRecord` et `DoctrineCashRegisterRepository` assurent
la persistence tenant/store-safe, la recherche par caisse/store et la
concurrence optimiste.

### Validation

```text
Mapping Doctrine : OK
Conteneur Symfony : OK
Deptrac layers/modules : 0 violation
```

### Commit

```text
feat(cash): persist cash registers
```

## Prochaine étape

### Étape 3.7.3 — Use cases CashRegister

**Statut : TERMINÉE**

Les opérations de création, mise à jour et changement de statut sont
regroupées dans `CashRegisterHandler`, avec transactions tenant-scoped et
interdiction métier des modifications d’une caisse archivée.

### Validation

```text
Conteneur Symfony : OK
Deptrac layers/modules : 0 violation
```

### Commit

```text
feat(cash): add cash register management
```

## Prochaine étape

### Epic 3.8 — CashSession lifecycle

**Statut : EN COURS**

### Étape 3.8.1 — Aggregate CashSession

**Statut : TERMINÉE**

`CashSession` couvre l’ouverture et la fermeture terminale, le solde initial
non négatif, les soldes attendu/compté et le calcul de l’écart dans la devise
de la session.

### Validation

```text
CashSessionTest : OK (1 test, 2 assertions)
Deptrac layers/modules : 0 violation
```

### Commit

```text
feat(cash): add cash session aggregate
```

## Prochaine étape

### Étape 3.8.2 — Persistence CashSession

**Statut : TERMINÉE**

Le mapping et le repository Doctrine persistent les soldes monétaires exacts,
la devise, le statut de session, les métadonnées de fermeture et la version
optimiste. La recherche d’une session ouverte est tenant/register-safe.

### Validation

```text
Mapping Doctrine : OK
Conteneur Symfony : OK
Deptrac layers/modules : 0 violation
```

### Commit

```text
feat(cash): persist cash sessions
```

## Prochaine étape

### Étape 3.8.3 — Ouverture et fermeture de session

**Statut : TERMINÉE**

Les use cases d’ouverture et de fermeture contrôlent la caisse active,
l’unicité d’une session ouverte et la fermeture terminale dans des
transactions tenant-scoped.

### Validation

```text
Conteneur Symfony : OK
Deptrac layers/modules : 0 violation
```

### Commit

```text
feat(cash): add cash session lifecycle
```

## Prochaine étape

### Étape 3.8.4 — Calcul du montant attendu

**Statut : TERMINÉE**

`CashSession::calculateExpectedBalance()` calcule le montant attendu comme la
somme du solde d’ouverture et du net des mouvements validés, sans stocker de
second solde mutable.

### Validation

```text
CashSessionTest : OK (2 tests, 3 assertions)
```

### Commit

```text
feat(cash): calculate expected cash balance
```

## Prochaine étape

### Étape 3.8.5 — CloseCashSession

**Statut : TERMINÉE**

`CloseCashSession` accepte uniquement le montant compté ; le montant attendu
est calculé côté application avant la fermeture, puis l’écart est enregistré
par l’aggregate.

### Validation

```text
Conteneur Symfony : OK
```

### Commit

```text
feat(cash): finalize close cash session use case
```

## Prochaine étape

### Étape 3.8.6 — Gestion des écarts

**Statut : TERMINÉE**

La fermeture calcule et conserve `discrepancy` dans la devise de la session.
Aucun mouvement correctif automatique n’est créé ; l’écart reste visible et
auditable.

### Validation

```text
CashSessionTest : OK (2 tests, 3 assertions)
```

## Prochaine étape

### Epic 3.9 — CashMovement ledger

**Statut : EN COURS**

### Étape 3.9.1 — Aggregate CashMovement

**Statut : TERMINÉE**

`CashMovement` est immuable, limite les types du Lot 3 (`CASH_IN`, `CASH_OUT`,
`CASH_WITHDRAWAL`) et impose un montant strictement positif, une devise portée
par `Money` et une source optionnelle.

### Validation

```text
CashMovementTest : OK (1 test, 2 assertions)
Deptrac layers/modules : 0 violation
```

### Commit

```text
feat(cash): add immutable cash movement ledger
```

## Prochaine étape

### Étape 3.9.2 — RecordCashIn

**Statut : EN COURS**

La commande et le handler `RecordCashIn` sont en place avec les contrôles de
session ouverte, de raison obligatoire et de transaction tenant-scoped. Le
repository Doctrine est encore un adaptateur temporaire ; la persistence
réelle du ledger sera finalisée à l’étape suivante.

### Validation

```text
Conteneur Symfony : OK
Deptrac layers/modules : 0 violation
```

### Commit

```text
feat(cash): add record cash in use case
```

## Prochaine étape

### Étape 3.9.3 — Persistence CashMovement

**Statut : TERMINÉE**

Le ledger `CashMovement` est désormais persisté par Doctrine en append-only,
avec lecture par session, montants décimaux exacts et devise conservée.

### Validation

```text
Mapping Doctrine : OK
Conteneur Symfony : OK
```

### Commit

```text
feat(cash): persist cash movements
```

### Étape 3.9.3 — RecordCashOut

**Statut : TERMINÉE**

Le cas d’usage `RecordCashOut` applique les mêmes contrôles que l’entrée
manuelle et produit un mouvement typé `CASH_OUT` uniquement pour une session
ouverte.

### Commit

```text
feat(cash): add manual cash out
```

## Prochaine étape

### Étape 3.9.4 — CashWithdrawal

**Statut : TERMINÉE**

### Étape 3.9.6 — Idempotence CashMovement

**Statut : EN COURS**

Les commandes de mouvements acceptent désormais une référence de source
optionnelle, alignée avec l’index unique PostgreSQL. Le branchement complet de
la référence est propagée par les trois handlers (`RecordCashIn`,
`RecordCashOut`, `WithdrawCash`).

### Commit

```text
feat(cash): prepare cash movement idempotency
fix(cash): propagate movement source reference
fix(cash): propagate source references on cash out
```

`WithdrawCash` produit un mouvement `CASH_WITHDRAWAL` distinct de `CASH_OUT`,
avec session ouverte et raison obligatoire.

### Validation

```text
Conteneur Symfony : OK
Deptrac layers/modules : 0 violation
```

### Commit

```text
feat(cash): add cash withdrawal
```

## Prochaine étape

### Étape 3.9.5 — Persistence append-only CashMovement

**Statut : DÉJÀ TERMINÉE**

---

# Discipline des commits

Le Lot 0 suit une logique stricte de commits atomiques.

Chaque commit doit représenter une seule intention cohérente.

Format :

```text
<type>(<scope>): <description>
```

Exemples :

```text
build(backend): initialize Symfony application
build(architecture): add Deptrac
build(architecture): separate layer and module rules
chore(database): add local PostgreSQL service
chore(dev): add architecture validation command
ci(backend): add initial validation workflow
docs(architecture): document dependency fitness tests
build(persistence): add Doctrine persistence stack
feat(persistence): configure PostgreSQL connection
feat(persistence): add bounded context schemas migration
chore(dev): add persistence commands
```

À éviter :

```text
feat: update project
chore: changes
feat: finish epic
```

Une modification documentaire liée au suivi d’implémentation peut être isolée du commit fonctionnel correspondant lorsqu’elle représente une intention différente.

---

# Règle de mise à jour

Mettre à jour ce fichier après chaque étape validée.

Pour chaque étape :

1. passer son statut à `TERMINÉ` ;
2. documenter uniquement ce qui a réellement été réalisé ;
3. noter les validations exécutées ;
4. noter le ou les commits atomiques correspondants ;
5. ajouter l’étape suivante sans la marquer comme terminée avant validation.

Ce fichier doit refléter **l’état réel du repository** et non l’état prévu du backlog.

### Étape 3.10.2 — Cash blocker provider

**Statut : TERMINÉE**

Cash Management fournit `OPEN_CASH_SESSION` lorsqu’une session ouverte existe
sur l’une des caisses du store.

### Validation

```text
Conteneur Symfony : OK
Deptrac layers/modules : 0 violation
```

### Commit

```text
feat(cash): provide store closure cash blocker
```

## Prochaine étape

### Étape 3.10.3 — Store suspendu

**Statut : TERMINÉE**

Les opérations `InitializeStock`, `AdjustStock` et `OpenCashSession` refusent
désormais les stores suspendus via `OperationalGuard` en mode standard.

### Validation

```text
Conteneur Symfony : OK
Deptrac layers/modules : 0 violation
```

### Commit

```text
feat(cash): enforce suspended store guard
```

## Epic 3.10 — StoreClosure integration

**Statut : EN COURS**

### Étape 3.10.1 — Inventory blocker provider

**Statut : TERMINÉE**

Inventory fournit `STOCK_REMAINING` lorsqu’un stock positif existe dans le
store demandé, via le contrat Organization de fermeture.

### Validation

```text
Conteneur Symfony : OK
Deptrac layers/modules : 0 violation
```

### Commit

```text
feat(inventory): provide store closure stock blocker
```

## Prochaine étape

### Étape 3.10.2 — Cash blocker provider

**Statut : À FAIRE**

---

## État courant — Étape 3.11.4 Audit sensible

**Statut : TERMINÉE**

Les handlers Inventory et Cash enregistrent les opérations sensibles via
`SecurityAuditTrail`, avec des actions distinctes du ledger métier :
initialisation et ajustement de stock, archivage de caisse, ouverture et
fermeture de session, encaissement, sortie et retrait.

### Validation

```text
Conteneur Symfony : OK
Deptrac layers/modules : 0 violation
```

### Commit

```text
feat(audit): record inventory operations
feat(audit): record cash operations
```

## Prochaine étape

## Prochaine étape

### Epic 3.12 — Application Contracts pour Lot 4

**Statut : À FAIRE**

---

## État courant — Epic 3.11 Authorization, audit & permissions

### Étape 3.11.1 — Permissions Inventory

**Statut : TERMINÉE**

Le catalogue partagé `PermissionCode` contient désormais uniquement les
permissions Inventory prévues pour les capacités livrées :
`INVENTORY_READ`, `INVENTORY_INITIALIZE`, `INVENTORY_ADJUST` et
`STOCK_MOVEMENT_READ`. Les permissions Stock avancées restent absentes tant
qu’elles ne sont pas utilisées.

### Validation

```text
PermissionCodeTest : OK (1 test, 44 assertions)
```

### Commit

```text
feat(access): add inventory permissions
```

## Prochaine étape

### Étape 3.11.2 — Permissions Cash

**Statut : TERMINÉE**

Le catalogue partagé `PermissionCode` contient les permissions Cash
Management prévues : registre, sessions et mouvements (lecture, ouverture,
fermeture, encaissement, sortie et retrait), sans synonymes supplémentaires.

### Validation

```text
PermissionCodeTest : OK (1 test, 55 assertions)
```

### Commit

```text
feat(access): add cash management permissions
```

## Prochaine étape

### Étape 3.11.3 — Rôles système

**Statut : TERMINÉE**

Les rôles système ont été étendus sans introduire de dépendance de rôle dans
les Domain métier : le propriétaire conserve toutes les permissions, tandis
que manager, caissier et comptable reçoivent uniquement les capacités
Inventory/Cash correspondant à leur politique opérationnelle.

### Validation

```text
SystemRoleCatalogTest : OK (1 test, 38 assertions)
```

### Commit

```text
feat(access): grant inventory and cash permissions
```

## Prochaine étape

### Étape 3.11.4 — Audit sensible

**Statut : À FAIRE**

---

## État courant — Epic 3.12 Application Contracts pour Lot 4

### Étapes 3.12.1 et 3.12.2 — Contrats Inventory/Cash

**Statut : TERMINÉES**

Les contrats synchrones `InventoryStockConsumer` et `CashMovementRecorder`
sont disponibles avec leurs commandes d’entrée et résultats idempotents
versionnés (`CONTRACT_VERSION = 1`). Aucune classe Sales, transaction
distribuée ou dépendance vers les agrégats d’un autre module n’a été ajoutée.

### Étape 3.12.3 — Contract tests

**Statut : TERMINÉE**

Les tests vérifient la surface des interfaces, la version contractuelle et le
marquage explicite des résultats rejoués.

### Validation

```text
Contract tests : OK (2 tests, 10 assertions)
Conteneur Symfony : OK
Deptrac layers/modules : 0 violation
```

### Commit

```text
feat(inventory): expose sale stock consumption contract
```

## Prochaine étape

### Epic 3.13 — Inventory & Cash API

**Statut : EN COURS**

### Étape 3.13.1 — API Stock

**Statut : TERMINÉE**

Les endpoints `GET /api/stores/{storeId}/stocks` et
`GET /api/stores/{storeId}/stocks/{productId}` sont exposés dans la
présentation du module Inventory. La lecture passe par un service applicatif,
respecte la permission `INVENTORY_READ` et conserve les quantités décimales
en chaînes JSON. Les POST `/initialize` et `/adjust` convertissent les DTO
JSON en commandes applicatives dédiées.

### Validation

```text
Symfony container : OK
Routes API Stock : OK
Deptrac layers/modules : 0 violation
```

### Commit

```text
feat(api): expose stock read operations
feat(api): expose stock operations
```

## Prochaine étape

### Étape 3.13.2 — API StockMovement

**Statut : TERMINÉE**

Les historiques immuables sont accessibles par store et par produit via les
deux endpoints GET dédiés. Aucun endpoint PATCH ou DELETE n’est exposé.

### Validation

```text
Routes StockMovement : OK
Deptrac layers/modules : 0 violation
```

### Commit

```text
feat(api): expose stock movement history
```

## Prochaine étape

### Étape 3.13.3 — API CashRegister

**Statut : TERMINÉE**

Les endpoints de lecture, création, mise à jour et transitions explicites
`activate`, `deactivate` et `archive` sont exposés dans le module
CashManagement. Les transitions restent intentionnelles et aucun CRUD
générique de ledger n’est ajouté.

### Validation

```text
Symfony container : OK
Routes CashRegister : OK
Deptrac layers/modules : 0 violation
```

### Commit

```text
feat(api): expose cash register management
```

## Prochaine étape

### Étape 3.13.4 — API CashSession

**Statut : TERMINÉE**

Le cycle de vie CashSession expose l’ouverture, la lecture et la fermeture
avec des DTO Money (montant décimal et devise séparés). Les opérations
réutilisent `CashSessionHandler` et restent intentionnelles.

### Validation

```text
Symfony container : OK
Routes CashSession : OK
Deptrac layers/modules : 0 violation
```

### Commit

```text
feat(api): expose cash session lifecycle
```

## Prochaine étape

### Étape 3.13.5 — API CashMovement

**Statut : TERMINÉE**

Les mouvements sont consultables par session et enregistrés via trois
opérations intentionnelles : `cash-in`, `cash-out` et `withdrawals`. Aucun
endpoint générique de création, PATCH ou DELETE n’est exposé.

### Validation

```text
Symfony container : OK
Routes CashMovement : OK
Deptrac layers/modules : 0 violation
```

### Commit

```text
feat(api): expose cash movement operations
```

## Prochaine étape

### Étape 3.13.6 — Contrat d’erreurs API

**Statut : TERMINÉE**

Le contrat d’erreurs Inventory/Cash est documenté dans
`docs/api/inventory-cash-errors.md`. Les chemins API correspondants sont
désormais couverts par le décorateur OpenAPI avec les réponses 400, 401, 403,
404, 409 et 422.

### Validation

```text
Tests Platform/API : OK (20 tests, 55 assertions)
```

### Commit

```text
docs(api): document inventory and cash errors
```

## Prochaine étape

### Étape 3.13.7 — OpenAPI Inventory/Cash

**Statut : TERMINÉE**

La découverte API Platform inclut désormais les namespaces Catalog, Pricing,
Inventory et CashManagement. Le périmètre, les formats Decimal/Money, les
scopes tenant/store, l’idempotence et l’immutabilité des ledgers sont
documentés dans `docs/api/inventory-cash-openapi.md`.

### Validation

```text
Symfony container : OK
Export OpenAPI : OK (routes Stock/Cash présentes)
```

### Commit

```text
docs(api): document inventory and cash endpoints
```

## Prochaine étape

### Epic 3.14 — Integration, PostgreSQL & RLS tests

**Statut : EN COURS**

### Étape 3.14.1 — Tests Domain Stock

**Statut : TERMINÉE**

Les invariants d’initialisation, de réinitialisation et de quantité négative
sont couverts, y compris l’initialisation explicite à zéro.

### Validation

```text
StockTest : OK (5 tests, 7 assertions)
```

### Commit

```text
test(inventory): cover stock invariants
```

## Prochaine étape

### Étape 3.14.2 — Tests ledger StockMovement

**Statut : TERMINÉE**

Les tests vérifient l’explication complète d’un ajustement sortant
(`previous = 10`, `quantity = 3`, `resulting = 7`, type
`ADJUSTMENT_OUT`) ainsi que l’immutabilité de l’aggregate.

### Validation

```text
StockMovementTest : OK (2 tests, 7 assertions)
```

### Commit

```text
test(inventory): verify stock movement ledger
```

## Prochaine étape

### Étape 3.14.3 — Atomicité Stock

**Statut : EN COURS**

Le socle PostgreSQL d’atomicité mutation/audit/outbox est couvert par le test
transactionnel existant. Les scénarios d’injection spécifiques à l’écriture
Stock puis StockMovement restent à ajouter avant clôture.

### Validation

```text
TransactionalAuditAtomicityTest : OK (4 tests, 15 assertions)
```

## Prochaine étape

Ajouter les doubles de repository Stock et les injections d’échec entre la
mise à jour du stock et l’insertion du mouvement.

Le scénario de concurrence PostgreSQL existant reste vert et couvre le
verrouillage optimiste ainsi que la prévention d’un stock négatif.

### Validation complémentaire

```text
StockConcurrencyTest : OK (2 tests, 8 assertions)
```

La couverture sur les tables métier `inventory.stock` doit encore remplacer
le schéma de spike avant de clore l’étape 3.14.4.

---

### Étape 3.14.5 — Tests Domain CashSession

**Statut : TERMINÉE**

Le cycle ouverture/fermeture, le calcul du solde attendu, les écarts et le
rejet d’une seconde fermeture sont couverts au niveau domaine.

### Validation

```text
CashSessionTest : OK (3 tests, 4 assertions)
```

### Commit

```text
test(cash): cover cash session invariants
```

## Prochaine étape

### Étape 3.14.6 — Concurrence OpenCashSession

**Statut : TERMINÉE**

Le test PostgreSQL vérifie la présence de l’index unique partiel
`cash_session_one_open_idx` sur `(organization_id, cash_register_id)` pour le
seul statut `OPEN`, garantissant une seule session ouverte par caisse.

### Validation

```text
PostgresRowLevelSecurityTest : OK (10 tests, 54 assertions)
```

### Commit

```text
test(cash): verify concurrent session guard
```

## Prochaine étape

### Étape 3.14.7 — Tests CashMovement

**Statut : TERMINÉE**

Les tests couvrent les types `CASH_IN`, `CASH_OUT` et `CASH_WITHDRAWAL`, ainsi
que le rejet d’un montant nul.

### Validation

```text
CashMovementTest : OK (3 tests, 5 assertions)
```

### Commit

```text
test(cash): verify cash movements
```

## Prochaine étape

### Étape 3.14.8 — Atomicité Cash

**Statut : EN COURS**

Le socle transactionnel partagé couvre les scénarios de rollback mutation,
audit et outbox utilisés par les opérations Cash. Les injections ciblées entre
insertion `CashMovement`, fermeture `CashSession`, audit et outbox restent à
ajouter pour clôturer cette étape.

### Validation

```text
TransactionalAuditAtomicityTest + TransactionalOutboxTest : OK (8 tests, 26 assertions)
```

## Prochaine étape

Ajouter un scénario d’intégration Cash qui force l’échec après l’écriture du
ledger et vérifie l’absence d’effet partiel.

---

### Étape 3.14.9 — Isolation tenant

**Statut : EN COURS**

Le socle RLS vérifie l’absence de contexte, l’isolation entre deux
connexions, le rejet des écritures cross-tenant et l’absence de fuite après
commit ou rollback. Les scénarios avec fixtures métier `inventory` et
`cash_management` doivent encore être ajoutés pour clore cette étape.

### Validation

```text
PostgresRowLevelSecurityTest : OK (10 tests, 54 assertions)
```

## Prochaine étape

Ajouter des fixtures Stock/Cash appartenant à deux tenants et vérifier les
lectures et écritures cross-tenant au niveau des repositories.

Un test de contrat garantit désormais que les méthodes tenant-aware des
repositories Inventory/Cash exigent explicitement un `OrganizationId`.

### Validation complémentaire

```text
TenantAwareRepositoryContractTest : OK (5 tests, 13 assertions)
Deptrac layers/modules : 0 violation
```

Les scénarios d’accès avec fixtures PostgreSQL restent à compléter.

Un test de non-régression vérifie désormais que `zandu_runtime` peut utiliser
les schémas `inventory` et `cash_management` après application de la
migration de privilèges.

### Validation complémentaire

```text
PostgresRowLevelSecurityTest : OK (11 tests, 56 assertions)
```

### Commit

```text
test(tenant): verify lot three schema grants
```

La suite CI avait révélé un manque de privilège `USAGE` sur les schémas
`inventory` et `cash_management`. Une migration dédiée corrige ce point ; le
workflow multi-store repasse désormais avec 47 assertions.

### Commit

```text
fix(integration): grant runtime schema access
```

### Validation globale

```text
make test : OK (432 tests, 1934 assertions)
```

La CI est verte après la migration de privilèges ; l’isolation métier avec
fixtures dédiées Stock/Cash reste la prochaine couverture ciblée.

---

### Étape 3.14.10 — RLS

**Statut : TERMINÉE**

Les 19 tables tenant-owned disposent de RLS et `FORCE ROW LEVEL SECURITY`,
avec des politiques explicites basées sur `app.organization_id`. Les tests
vérifient l’absence de contexte, l’isolation inter-connexions et le rejet des
écritures cross-tenant.

### Validation

```text
PostgresRowLevelSecurityTest : OK (10 tests, 54 assertions)
```

## Prochaine étape

Ajouter les scénarios repository cross-tenant avec fixtures métier Inventory et
Cash, puis clôturer l’Epic 3.14.

### Validation globale actualisée

```text
make test : OK (438 tests, 1949 assertions)
make architecture : OK (0 violation layers/modules)
```

Le repository est prêt pour le lot suivant ; les fixtures cross-tenant
spécifiques restent une amélioration de couverture à planifier.

### Clôture complémentaire Epic 3.14

**Étapes 3.14.8 et 3.14.9 : TERMINÉES**

Des tests PostgreSQL dédiés couvrent désormais les lignes réelles du Lot 3 :

- une caisse du tenant B est invisible depuis le contexte du tenant A ;
- une session Cash du tenant courant reste lisible ;
- une écriture `CashMovement` suivie d’une exception est annulée avec toute
  la transaction.

### Validation du gate

```text
make test : OK (440 tests, 1952 assertions)
make architecture : OK (0 violation layers/modules)
composer audit --locked : OK (aucune vulnérabilité)
```

Les contrôles de qualité sont désormais verts : le formatage de l’ensemble du
backend a été normalisé et les diagnostics PHPStan corrigés, notamment les
types d’itérables des providers/query services, la reconstruction des sources
de mouvements et la couverture exhaustive des actions d’audit Cash/Inventory.

```text
make quality : OK (PHP-CS-Fixer, PHPStan)
```

Le gate Lot 3 est donc validé sur les contrôles automatisés disponibles.

---

## Lot 4 — Sales & CompleteSale cash

**État courant autoritatif : TERMINÉ — Gate M2 validé le 27 août 2026.**

Les sous-sections suivantes constituent le journal chronologique de
l'implémentation. Leurs blocs « Prochaine étape » décrivent l'étape qui suivait
au moment de leur rédaction ; ils sont désormais tous réalisés et sont
supplantés par la synthèse de clôture en fin de section.

Le périmètre livré comprend :

- cycle `DRAFT` complet et annulation sans suppression métier de la vente ;
- snapshots produit, packaging, conversion, prix et politique fiscale ;
- politique pilote `NO_TAX` décidée dans l'ADR-0020 ;
- Payment CASH confirmé, Inventory et CashMovement distincts et idempotents ;
- `CompleteSale` transactionnel avec verrou pessimiste, revalidation Pricing,
  `BusinessDate`, montant remis et monnaie rendue ;
- API Create/Read/Lines/Cancel/Complete/Receipt et contrat OpenAPI ;
- permissions et scopes Store, RLS Sales/Payment, concurrence PostgreSQL et
  scénario HTTP M2 avec rejeu idempotent.

### Epic 4.1 — Sales foundation

**Statut : TERMINÉ**

Le module `Sales` conserve ses frontières propres et ne dépend d’aucun
aggregate Domain Inventory, Cash ou Catalog. La migration
`Version20260826130000` crée `sales.sale` et `sales.sale_line` avec :

- clés tenant-scoped et relation composite vers `organization.stores` ;
- statuts et contraintes d’agrégats financiers non négatifs ;
- index tenant/store/status et business date ;
- versionnement optimiste ;
- RLS `FORCE ROW LEVEL SECURITY` et privilèges `zandu_runtime`.

### Validation

```text
Migration Sales : OK
Deptrac layers/modules : 0 violation
```

### Commit

```text
feat(database): add sales persistence foundation
```

## Prochaine étape

Implémenter l’agrégat `Sale` et son cycle de vie DRAFT → COMPLETED/CANCELLED
(Epic 4.2), avec ses tests de domaine.

### Epic 4.2 — Sale aggregate & lifecycle

**Statut : TERMINÉ**

L’agrégat `Sale` est disponible avec les statuts `DRAFT`, `AWAITING_PAYMENT`,
`COMPLETED` et `CANCELLED`. Les invariants de vente vide, d’édition après
finalisation et d’annulation d’une vente finalisée sont couverts par les tests
de domaine.

### Validation

```text
SaleTest : OK (3 tests, 4 assertions)
PHPStan : OK
Deptrac layers/modules : 0 violation
```

### Commit

```text
feat(sales): add sale aggregate lifecycle
```

## Prochaine étape

Implémenter `SaleLine` et les snapshots historiques (Epic 4.3), puis exposer le
contrat applicatif Catalog nécessaire à la résolution d’un produit vendable.

### Epic 4.3 — SaleLine & snapshots

**Statut : TERMINÉ**

`SaleLine` conserve désormais les identifiants produit/packaging, les libellés
et codes, l’unité, les quantités saisies et de base, le facteur de conversion,
les montants et les références de prix. Le contrat `SaleProductProvider` expose
un descripteur applicatif sans dépendance vers `Catalog\Domain`.

### Validation

```text
SaleTest : OK (3 tests, 4 assertions)
PHPStan : OK
Deptrac layers/modules : 0 violation
```

### Commit

```text
feat(sales): add sale line snapshots and catalog contract
```

## Prochaine étape

Implémenter la résolution de prix déterministe de l’Epic 4.4 à partir du
contrat produit vendable et des snapshots de ligne.

### Epic 4.4 — Sale pricing

**Statut : TERMINÉ**

Le `SalePricingCalculator` effectue la multiplication exacte quantité × prix
unitaire avec `Money` et `Decimal`, sans conversion flottante. Le
`SalePricingService` délègue la résolution au contrat `PricingSnapshotResolver`
et rejette explicitement un `sourceVersion` obsolète avec
`SALE_PRICING_CHANGED`.

### Validation

```text
SalePricingCalculatorTest : OK (1 test, 2 assertions)
PHPStan : OK
PHP-CS-Fixer : OK
```

### Commit

```text
feat(sales): add deterministic sale pricing
```

## Prochaine étape

Implémenter l’Epic 4.5 — paiement minimal `CASH`, distinct de
`CashMovement`, avec statut `CONFIRMED` et purpose `SALE`.

### Epic 4.5 — Minimal Payment CASH

**Statut : TERMINÉ**

Le bounded context `Payments` expose un agrégat `Payment` limité au MVP :
`purpose=SALE`, `method=CASH`, montants strictement positifs et confirmation
unique. Les paiements confirmés ne sont pas réécrits. La migration crée la
table tenant-scoped `payments.payment`, son index d’idempotence par vente,
ses contraintes métier et sa politique RLS.

### Validation

```text
PaymentTest : OK (1 test, 2 assertions)
Migration Payments : OK
PHPStan : OK
```

### Commit

```text
feat(payments): add cash sale payment
```

## Prochaine étape

Implémenter l’intégration Inventory de l’Epic 4.6 via
`InventoryStockConsumer`, sans importer `StockRepository` ou
`Inventory\Domain` depuis Sales.

### Epic 4.6 — Inventory integration

**Statut : TERMINÉ**

`InventoryConsumptionService` consomme le stock uniquement via
`InventoryStockConsumer` et `ConsumeStockForSale`. Les produits physiques
suivis sont transmis avec leur `baseQuantity`; les produits non suivis et les
services ne déclenchent aucun appel Inventory. Sales n’importe aucun
repository ni aggregate du domaine Inventory.

### Validation

```text
InventoryConsumptionServiceTest + SaleTest : OK (4 tests, 6 assertions)
PHPStan : OK
PHP-CS-Fixer : OK
```

### Commit

```text
feat(inventory): consume stock through sales contract
```

## Prochaine étape

Implémenter l’Epic 4.7 — intégration Cash via `CashMovementRecorder`, avec
session ouverte obligatoire et paiement SALE idempotent.

### Epic 4.7 — Cash integration

**Statut : TERMINÉ**

`CashPaymentService` délègue l’enregistrement d’un paiement de vente à
`CashMovementRecorder` avec le tenant, le Store, la session Cash, la vente,
le montant et l’acteur. La devise de paiement est vérifiée contre celle de la
vente ; l’idempotence est portée par le résultat du contrat
`CashSalePaymentResult`. Sales ne manipule directement aucune session ou
entité Cash.

### Validation

```text
CashPaymentServiceTest : OK (1 test, 2 assertions)
PHPStan : OK
PHP-CS-Fixer : OK
```

### Commit

```text
feat(cash): record sale payment movement
```

## Prochaine étape

Implémenter l’Epic 4.8 — workflow `CompleteSale` coordonnant vente, paiement,
Inventory, Cash et Outbox dans une transaction locale atomique.

### Epic 4.8 — CompleteSale

**Statut : TERMINÉ**

Le cas d’usage `CompleteSale` coordonne la consommation Inventory, le
paiement, l’écriture Cash et la finalisation de la vente dans une
`TenantTransaction`. Les dépendances externes sont exprimées par des contrats
applicatifs (`InventoryStockConsumer`, `CashMovementRecorder` et
`PaymentRecorder`). Un échec de paiement laisse la vente dans l’état `DRAFT`.

### Validation

```text
CompleteSaleServiceTest : OK (1 test, 1 assertion)
PHPStan : OK
PHP-CS-Fixer : OK
```

### Commit

```text
feat(sales): add complete sale transaction workflow
```

## Prochaine étape

Ajouter le chemin nominal de `CompleteSale` avec ligne, paiement confirmé et
effets Inventory/Cash vérifiés, puis couvrir l’idempotence et la concurrence
(Epic 4.9).

La protection de rejeu est désormais en place : une vente déjà `COMPLETED`
sort sans réexécuter les effets externes.

### Epic 4.9 — Idempotence & concurrency

**Statut : TERMINÉ**

`CompleteSale` accepte désormais une clé d’idempotence et consulte un store
injectable avant d’exécuter les effets. La clé est marquée après finalisation ;
les rejoués connus sont ignorés. L’implémentation mémoire sert de baseline de
contrat, avant son remplacement par une persistance PostgreSQL atomique et les
tests de concurrence réelle.

### Validation

```text
SaleCompletionIdempotencyTest + CompleteSaleServiceTest : OK (2 tests, 4 assertions)
PHPStan : OK
PHP-CS-Fixer : OK
```

### Commit

```text
feat(sales): add complete sale idempotency key
```

### Persistance idempotence PostgreSQL

**Statut : TERMINÉE**

Les clés de rejeu sont maintenant persistées dans
`sales.sale_completion_keys`, protégées par tenant et par clé primaire
composite. L’enregistrement utilise `ON CONFLICT DO NOTHING`, ce qui fournit
une réservation atomique compatible avec les retries concurrents.

### Validation

```text
Migration sale_completion_keys : OK
SaleCompletionIdempotencyContractTest + SaleCompletionIdempotencyTest : OK (2 tests, 4 assertions)
PHPStan : OK
```

### Commit

```text
feat(sales): persist completion idempotency keys
```

### Commit complémentaire

```text
feat(sales): make complete sale replay safe
```

### Epic 4.10 — Authorization & audit

**Statut : TERMINÉ**

Le catalogue partagé contient désormais `SALE_CREATE`, `SALE_READ`,
`SALE_UPDATE_DRAFT`, `SALE_CANCEL_DRAFT`, `SALE_COMPLETE` et
`SALE_PRICE_OVERRIDE`. Les rôles Store Manager et Cashier reçoivent les
capacités opérationnelles Sales ; Accountant reçoit la lecture seule ; le
propriétaire conserve l’ensemble du catalogue.

### Validation

```text
SystemRoleCatalogTest : OK (1 test, 38 assertions)
PHPStan : OK
PHP-CS-Fixer : OK
```

### Commit

```text
feat(access): add sales permissions and role grants
```

## Prochaine étape

Appliquer ces permissions et les guards opérationnels dans les handlers Sales,
puis publier les événements et audits de finalisation.

## Revue corrective consolidée — 27 août 2026

**Statut : TERMINÉ — Gate M2 validé**

La revue globale a corrigé, dans l’ordre de risque, les incohérences suivantes :

- toutes les mutations Inventory/Cash contrôlent désormais permission, tenant,
  scope Store et mode opérationnel ;
- la clôture d’une session calcule le solde attendu avec les mouvements réels et
  les lectures de mouvements exigent le Store, sans fuite cross-store ;
- les routes imbriquées API Platform utilisent explicitement leurs variables
  d’URI et les commandes Cash ont été séparées en fichiers PSR-4 ;
- les écritures SALE sont implémentées dans Payment, StockMovement et
  CashMovement avec contraintes d’unicité et adaptateurs persistants ;
- `CompleteSale` recharge et verrouille la vente persistée, refuse un montant
  différent du total, résout les produits suivis côté serveur et revendique la
  clé d’idempotence atomiquement avec une empreinte du payload ;
- la finalisation applique `SALE_COMPLETE`, le scope Store et le guard standard,
  calcule `business_date` depuis `Store.timeZone`, puis persiste audit et événements
  outbox dans la transaction locale ;
- Payments et les contrats inter-modules sont couverts par Deptrac. L’analyse
  modulaire passe de 3 221 dépendances non classées à 10, sans violation.

### Migrations ajoutées

```text
Version20260827090000 — cohérence Store Cash par clés composites
Version20260827091000 — mouvements SALE Inventory/Cash
Version20260827092000 — empreinte des payloads d’idempotence CompleteSale
```

### Validation consolidée

```text
PHP-CS-Fixer : OK
PHPStan : OK
Deptrac layers : 0 violation
Deptrac modules : 0 violation, 10 uncovered
StoreScopedAuthorizationWorkflowTest : OK (4 tests, 135 assertions)
Suite PHPUnit complète : OK (460 tests, 2 115 assertions)
Composer validate : OK
Composer audit --locked : aucune vulnérabilité
```

La suite complète a également révélé puis fait corriger un chargement PSR-4
aléatoire des commandes Cash. La relance complète après séparation des classes
en fichiers dédiés est verte.

### Clôture du Gate M2

Les écarts identifiés par la revue sont fermés :

- Create/Read/Cancel et l’édition des lignes Draft sont disponibles avec
  permissions et scopes Store ;
- l’API expose CompleteSale, Receipt et un contrat OpenAPI vérifié ;
- Pricing est revalidé avant finalisation et les changements silencieux sont
  refusés ;
- l’ADR-0020 décide explicitement la politique pilote `NO_TAX`, snapshotée sur
  chaque ligne ;
- `tenderedAmount` est contrôlé et `changeAmount` est retourné sans gonfler le
  Payment ni le CashMovement ;
- un scénario HTTP PostgreSQL couvre la vente cash complète et son rejeu avec
  un seul Payment, StockMovement et CashMovement ;
- deux connexions PostgreSQL prouvent la sérialisation pessimiste de
  CompleteSale ; les tests RLS isolent Sale, SaleLine et Payment.

La stratégie de commits atomiques a été restaurée : invariants Cash,
consommation Inventory, Payment, cycle Draft, CompleteSale, API, RLS,
concurrence, OpenAPI et documentation sont séparés dans l’historique.

### Commits de clôture du Lot 4

```text
9681767 fix(cash): enforce ledger and session invariants
6cf293a feat(inventory): consume stock idempotently for sales
f4e76b5 feat(payments): record confirmed cash sale payments
1e67b8d feat(sales): implement draft sale lifecycle
1d11cc7 feat(sales): complete cash sales atomically
869ba98 feat(api): expose cash sales workflow and receipt
fa08a8b test(tenant): verify sales and payment rls
26468c0 test(architecture): enforce sales module boundaries
0b25d95 test(sales): verify complete cash sale workflow
930c21f fix(api): stabilize sales errors and receipt lifecycle
371cb62 test(sales): serialize concurrent sale completion
87cce49 test(api): document cash sales operations
b82fab8 refactor(sales): keep presentation behind application views
5d92d9c docs(status): close lot four cash sales gate
```

---

## Lot 5 — Inventory Costing & Returns

**État courant : EN COURS — retours restockés au coût original.**

### Phase 0 — Décisions et alignement documentaire

Réalisé :

- l’ADR-0021 retient un coût d’ouverture explicite et interdit toute
  reconstruction du coût historique depuis le prix de vente ;
- toute variation physique post-activation doit être valorisée dans la même
  transaction, y compris `INITIAL_STOCK` et les ajustements ;
- l’ADR-0022 place `PaymentRefund` dans Payments, impose un `ReturnSale`
  terminé et retient `POST /api/payments/{paymentId}/refunds` ;
- le planning distingue les commandes transactionnelles Return et Refund et
  aligne la numérotation de ses epics ;
- le README annonce le démarrage du Lot 5 et référence son backlog.

Commits :

```text
5d9ff2c docs(adr): define inventory costing activation policy
64ad279 docs(adr): define cash refund ownership
ba87583 docs(planning): align lot five implementation order
45c34b9 docs(readme): announce lot five implementation
```

### Epic 5.1 — Inventory Costing foundation

**Statut : TERMINÉ**

- le namespace `Modules/InventoryCosting` est matérialisé par une erreur de
  domaine à code stable ;
- Deptrac distingue `InventoryCosting` de son `Application/Contract` ;
- Sales et Inventory peuvent consommer uniquement le contrat Costing ;
- InventoryCosting ne peut dépendre ni du domaine Sales ni de sa persistence ;
- les analyses de couches et de modules restent à zéro violation.

Commit :

```text
7791f62 refactor(costing): add inventory costing bounded context
```

### Epic 5.4 — Moving weighted average

**Statut : TERMINÉ**

- calculs d’entrée et de sortie séparés et typés ;
- quantité, valeur du mouvement, valeur totale et coût moyen retournés ;
- coûts unitaires calculés à 12 décimales et valeurs à 6 décimales ;
- aucune utilisation de `float` ;
- sortie finale absorbant exactement le résidu ;
- quantités fractionnaires, arrondis, sur-sortie et états incohérents testés.

Commit :

```text
f7d91ea feat(costing): add moving weighted average calculator
```

Validations après ces deux commits :

```text
PHPUnit ciblé Calculator : OK (9 tests, 28 assertions)
Suite PHPUnit complète : OK (470 tests, 2 147 assertions)
PHP-CS-Fixer : OK
PHPStan : OK
Deptrac layers : 0 violation, 10 uncovered
Deptrac modules : 0 violation, 10 uncovered
```

### Epic 5.2 — StockValuation aggregate

**Statut : TERMINÉ — modèle de domaine et persistence.**

- identité `StockValuationId` partageable par les contrats applicatifs ;
- ownership immuable Organization, Store, Product et Stock ;
- quantité, valeur totale, devise et version protégées par l’agrégat ;
- initialisation positive au coût d’ouverture exact ou initialisation nulle
  avec valeur strictement nulle ;
- entrées et sorties déléguées au calculateur de coût moyen ;
- version incrémentée uniquement après une mutation réussie ;
- échec de quantité ou de devise sans mutation partielle.

Commit :

```text
ab5af7a feat(costing): add stock valuation aggregate
```

### Epic 5.3 — StockValuationMovement ledger

**Statut : TERMINÉ — modèle de domaine et persistence append-only.**

- `StockMovementId` déplacé dans le SharedKernel afin d’être référencé sans
  dépendance au domaine Inventory ;
- ledger `readonly` sans mutation ni suppression métier ;
- types `OPENING`, `INITIAL_STOCK`, `ADJUSTMENT_IN`, `ADJUSTMENT_OUT`, `SALE`
  et `SALE_RETURN` ;
- cohérence direction/valeurs/devise/source vérifiée à la création ;
- lien `StockMovementId` obligatoire sauf pour le bootstrap historique
  `OPENING`, qui ne crée pas de faux mouvement physique ;
- identité, ownership, coûts précédents/résultants, source, corrélation et
  horodatage UTC conservés.

Commits :

```text
16efe46 refactor(inventory): share stock movement identity
622f8d9 feat(costing): add valuation movement ledger
```

Validation consolidée :

```text
StockValuationTest : OK (8 tests, 32 assertions)
StockValuationMovementTest : OK (6 tests, 19 assertions)
Suite PHPUnit complète : OK (484 tests, 2 198 assertions)
PHP-CS-Fixer : OK
PHPStan : OK
Deptrac layers : 0 violation, 10 uncovered
Deptrac modules : 0 violation, 10 uncovered
```

### Persistence Costing — PostgreSQL, repositories et RLS

**Statut : TERMINÉ**

- contrats de repository distincts pour l’agrégat courant et le ledger ;
- schéma PostgreSQL `inventory_costing` et migration appliquée aux bases de
  test et de développement ;
- décimaux persistés à 12 chiffres pour les quantités/coûts unitaires et à
  6 chiffres pour les valeurs monétaires ;
- unicité d’une valorisation par Stock et d’une valorisation par mouvement
  physique ;
- insertion `OPENING` idempotente, ledger append-only pour le rôle runtime et
  verrouillage optimiste de l’agrégat ;
- clés étrangères composites garantissant la cohérence Organization, Store,
  Product et Stock ;
- politiques RLS forcées et isolation inter-tenant prouvée en intégration ;
- Swagger UI et ReDoc rendus via Twig uniquement en environnement `dev`, les
  interfaces restant désactivées dans les autres environnements.

Commits :

```text
2407908 feat(costing): define valuation repository contracts
aa32717 feat(costing): persist valuation schema
6ddffc4 feat(costing): persist valuation repositories
7a1032c feat(dev): enable swagger and redoc interfaces
```

Validation consolidée :

```text
InventoryCostingPersistenceTest : OK (5 tests, 14 assertions)
DevelopmentDocumentationUiTest : OK (3 tests, 6 assertions)
Suite PHPUnit complète : OK (494 tests, 2 220 assertions)
PHP-CS-Fixer : OK
PHPStan : OK
Deptrac layers : 0 violation, 10 uncovered
Deptrac modules : 0 violation, 10 uncovered
Conteneurs Symfony dev/test/prod : OK
```

### Epic 5.5 — Costing bootstrap

**Statut : TERMINÉ**

- commande `InitializeStockValuation` avec coût d’ouverture explicite et
  justification obligatoire de 1 à 255 caractères ;
- permission sensible `INVENTORY_COSTING_INITIALIZE`, réservée actuellement au
  rôle Organization Owner et contrôlée au scope Store ;
- contrat Inventory dédié donnant accès à la position exacte sous verrou
  pessimiste sans exposer le domaine Inventory à Inventory Costing ;
- devise obtenue depuis le contexte métier du magasin ;
- quantité positive valorisée à 12 décimales pour le coût et 6 pour la valeur ;
- position nulle acceptée uniquement avec un coût d’ouverture nul ;
- création atomique de `StockValuation`, du ledger `OPENING`, de l’audit
  `STOCK_VALUATION_INITIALIZED` et de l’outbox versionnée ;
- endpoint `POST /api/stores/{storeId}/inventory-valuations/{productId}/initialize` ;
- seconde initialisation refusée par `VALUATION_ALREADY_INITIALIZED` sans effet
  dupliqué ;
- sérialisation concurrente prouvée sur la ligne Stock PostgreSQL ;
- contrat OpenAPI exposé dans Swagger UI et ReDoc en développement.

Commits :

```text
d230687 feat(inventory): expose locked stock position for costing
78f6274 feat(costing): add valuation bootstrap
4162507 feat(api): expose valuation bootstrap
064bbc9 test(costing): serialize valuation bootstrap
49fde55 test(api): isolate valuation bootstrap workflow
```

Validation consolidée :

```text
InitializeStockValuationHandlerTest : OK (4 tests, 30 assertions)
InventoryValuationBootstrapWorkflowTest : OK (1 test, 42 assertions)
StockValuationBootstrapConcurrencyTest : OK (1 test, 3 assertions)
Suite PHPUnit complète : OK (501 tests, 2 303 assertions)
PHP-CS-Fixer : OK
PHPStan : OK
Deptrac layers : 0 violation, 10 uncovered
Deptrac modules : 0 violation, 10 uncovered
```

### Valorisation des mouvements Inventory

**Statut : TERMINÉ**

- contrat applicatif Costing consommable par Inventory sans dépendance vers le
  domaine ou la persistence Costing ;
- `INITIAL_STOCK` crée la valorisation courante et un ledger lié au mouvement
  physique avec un coût unitaire explicite ;
- `ADJUSTMENT_IN` applique le coût entrant explicite et recalcule le coût moyen
  pondéré ;
- `ADJUSTMENT_OUT` interdit un coût fourni par le client et utilise le coût
  moyen courant ;
- contrôle avant mutation de l’égalité entre quantité physique précédente et
  quantité valorisée, puis contrôle du résultat ;
- Stock, StockMovement, StockValuation, ledger, audit et outbox participent à
  la même transaction tenant-scoped ;
- toute erreur Costing annule également le Stock et son mouvement physique ;
- les DTO et le contrat OpenAPI documentent `unitCost` sur l’initialisation et
  l’ajustement ;
- le bootstrap `OPENING` reste réservé à la reprise des stocks historiques
  existant avant l’activation de Costing.

Commits :

```text
dbefedc feat(costing): value inventory movements
a18c876 feat(inventory): value stock initialization and adjustments
```

Validation consolidée :

```text
RepositoryInventoryMovementValuerTest : OK (5 tests, 26 assertions)
Workflow HTTP opérationnel : OK (1 test, 50 assertions)
Suite PHPUnit complète : OK (508 tests, 2 380 assertions)
PHP-CS-Fixer : OK
PHPStan : OK
Deptrac layers : 0 violation, 10 uncovered
Deptrac modules : 0 violation, 10 uncovered
```

### Epic 5.6 — Intégration vente / costing

**Statut : TERMINÉ**

- le contrat de consommation Inventory passe le contexte acteur/corrélation et
  est versionné en v2 ;
- après l’insertion idempotente du `StockMovement SALE` et la diminution
  physique, Inventory demande sa valorisation dans la transaction
  `CompleteSale` existante ;
- Costing verrouille la valorisation, vérifie la quantité physique précédente,
  sort au coût moyen courant et persiste le ledger `SALE` lié au mouvement ;
- un rejeu de la même vente ne diminue ni Stock ni StockValuation et ne crée
  aucun ledger supplémentaire ;
- une valorisation absente provoque `VALUATION_NOT_INITIALIZED` et annule
  mouvement physique, paiement, mouvement de caisse, vente et clé
  d’idempotence ;
- le workflow M2 PostgreSQL vérifie quantité valorisée, coût unitaire, valeur
  sortie, valeur résiduelle, lien physique, rejeu et rollback.

Commit :

```text
2cf2052 feat(costing): value sale stock consumption
```

Validation consolidée :

```text
RepositoryInventoryMovementValuerTest : OK (6 tests, 33 assertions)
RepositoryInventoryStockConsumerTest : OK (2 tests, 14 assertions)
Workflow M2 HTTP : OK (1 test, 56 assertions)
Suite PHPUnit complète : OK (511 tests, 2 424 assertions)
PHP-CS-Fixer : OK
PHPStan : OK
Deptrac layers : 0 violation, 10 uncovered
Deptrac modules : 0 violation, 10 uncovered
```

### Epic 5.7 — SaleLineCostSnapshot

**Statut : TERMINÉ**

- modèle Sales readonly identifié par Organization et SaleLine, lié au Stock
  et au StockMovement ayant porté la consommation ;
- quantité de base, coût unitaire à 12 décimales, coût total à 6 décimales,
  devise, version de valorisation et horodatage UTC conservés ;
- cohérence quantité × coût, devise, valeurs positives et version validée dans
  le modèle et par les contraintes PostgreSQL ;
- table append-only pour le rôle runtime, unicité d’un snapshot par ligne,
  clés étrangères tenant-scoped et RLS forcée ;
- résultat Costing typé traduit par Inventory dans son contrat v3, sans fuite
  du domaine Costing vers Sales ;
- capture d’un snapshot par ligne suivie, y compris plusieurs lignes du même
  produit ; aucun snapshot fictif pour les services ou produits non suivis ;
- validation complète du mapping avant la première insertion afin d’éviter les
  écritures partielles ;
- capture après la sauvegarde des lignes `COMPLETED`, dans la transaction
  `CompleteSale`, afin de respecter la persistence actuelle qui réécrit les
  lignes ;
- le workflow M2 vérifie les identités Stock/StockMovement, quantité, coût,
  devise, version et absence de duplication au rejeu.

Commits :

```text
5c60070 feat(sales): persist sale line cost snapshots
838c50f feat(sales): capture sale line cost snapshots
```

Validation consolidée :

```text
SaleLineCostSnapshotServiceTest : OK (2 tests, 10 assertions)
SaleLineCostSnapshotPersistenceTest : OK (3 tests, 9 assertions)
Workflow M2 HTTP : OK (1 test, 66 assertions)
Suite PHPUnit complète : OK (516 tests, 2 455 assertions)
PHP-CS-Fixer : OK
PHPStan : OK
Deptrac layers : 0 violation, 10 uncovered
Deptrac modules : 0 violation, 10 uncovered
```

### Epic 5.8 — Atomicité CompleteSale avec costing

**Statut : TERMINÉ**

- une faute contrôlée est injectée exactement lors de l’insertion du premier
  `SaleLineCostSnapshot`, après les mutations physiques et financières de
  `CompleteSale` ;
- l’échec annule la vente, le paiement, le mouvement de caisse, le stock, le
  `StockMovement SALE`, la valorisation courante, le ledger `SALE` et le
  snapshot ;
- audit, outbox et clé d’idempotence restent également inchangés ;
- après nettoyage de l’identity map Doctrine consécutif au rollback, le même
  service réussit avec le repository réel et la même clé d’idempotence ;
- le retry produit une seule vente terminée, un seul snapshot et une seule
  revendication de clé.

Commit :

```text
700f65d test(costing): verify CompleteSale costing atomicity
```

Validation consolidée :

```text
Scénario de faute injectée : OK (1 test, 36 assertions)
Suite PHPUnit complète : OK (517 tests, 2 491 assertions)
PHP-CS-Fixer : OK
PHPStan : OK
Deptrac layers : 0 violation, 10 uncovered
Deptrac modules : 0 violation, 10 uncovered
```

### Epic 5.9 — ReturnSale foundation

**Statut : TERMINÉ — modèle de domaine sans persistence ni API.**

- identités typées `ReturnSaleId` et `ReturnSaleLineId` et statuts `DRAFT`,
  `COMPLETED`, `CANCELLED` ;
- `ReturnSaleLine` conserve directement la ligne de vente immutable et son
  éventuel `SaleLineCostSnapshot`, sans résolution du catalogue ou du tarif
  courant ;
- quantité retournée strictement positive, bornée par la quantité vendue et
  convertie en quantité de base avec le facteur snapshoté ;
- option `restock` explicite, motif optionnel normalisé et cohérence du
  snapshot de coût avec la ligne originale ;
- agrégat tenant-owned lié à la vente et au magasin, avec auteur, timestamps
  UTC, date métier, version et collection de lignes ;
- une seule occurrence de chaque ligne vendue dans un même retour ;
- complétion interdite sans ligne, annulation limitée au brouillon et toute
  mutation refusée après complétion ou annulation ;
- contrôle de l’organisation de l’acteur et des snapshots de coût.

Commits atomiques, ordonnés selon les dépendances du modèle :

```text
3b13878 feat(sales): add return sale line model
30a9f2a feat(sales): add return sale aggregate
```

Validation consolidée :

```text
ReturnSaleLineTest : OK (4 tests, 15 assertions)
ReturnSaleTest : OK (6 tests, 26 assertions)
Suite PHPUnit complète : OK (527 tests, 2 532 assertions)
PHP-CS-Fixer : OK
PHPStan : OK
Deptrac layers : 0 violation, 10 uncovered
Deptrac modules : 0 violation, 10 uncovered
```

### Persistence ReturnSale — PostgreSQL et RLS

**Statut : TERMINÉ**

- tables tenant-owned `sales.return_sale` et `sales.return_sale_line` avec
  contraintes de statuts, cycle de vie, version, quantités et numéros de ligne ;
- clés étrangères composites prouvant que le retour, la vente, la ligne
  originale et le produit appartiennent au même tenant et à la même vente ;
- les snapshots commerciaux restent portés par la ligne de vente immutable et
  le snapshot de coût original est rechargé lorsqu’il existe, sans copie
  divergente dans une nouvelle colonne JSON ;
- repository DBAL avec création, mise à jour optimiste, lecture verrouillée et
  recherche de tous les retours d’une vente ;
- lignes append-only pour le rôle runtime, parent modifiable uniquement pour
  son cycle de vie ;
- RLS forcée et isolation inter-tenant vérifiée en intégration ;
- migration `Version20260828120000` appliquée aux bases dev et test.

Commit :

```text
a500cd5 feat(sales): persist return sales
```

### Invariants ReturnSale — vente source et limites cumulatives

**Statut : TERMINÉ**

- la complétion recharge puis verrouille de façon pessimiste la vente source avant
  le retour, ce qui sérialise tous les retours concurrents d’une même vente ;
- seule une vente `COMPLETED` est retournable, sinon `SALE_NOT_RETURNABLE` ;
- les quantités de base de tous les retours déjà `COMPLETED` sont cumulées par
  ligne de vente originale ;
- les retours `DRAFT` ou `CANCELLED` ne consomment pas la quantité retournable ;
- tout dépassement est refusé avec `RETURN_QUANTITY_EXCEEDS_SOLD` avant la
  mutation du retour ;
- la date métier est calculée avec le fuseau du magasin et la complétion est
  persistée dans la transaction tenant-scoped.

Commit :

```text
3cbb291 feat(sales): enforce cumulative return limits
```

Validation consolidée :

```text
ReturnSalePersistenceTest : OK (3 tests, 13 assertions)
CompleteReturnSaleServiceTest : OK (4 tests, 8 assertions)
Suite PHPUnit complète : OK (534 tests, 2 553 assertions)
Conteneur Symfony test : OK
PHP-CS-Fixer : OK
PHPStan : OK
Deptrac layers : 0 violation, 10 uncovered
Deptrac modules : 0 violation, 10 uncovered
```

### Epic 5.10 — Inventory restock

**Statut : TERMINÉ**

- nouveau type de mouvement physique `SALE_RETURN`, direction entrante et
  source `RETURN / ReturnSaleId` ;
- contraintes PostgreSQL étendues par `Version20260828140000`, appliquée aux
  bases dev et test ;
- contrat Inventory v1 consommable par Sales, sans exposition du domaine
  Inventory ;
- agrégation des lignes par produit avant mutation et ordre de verrouillage
  déterministe pour réduire les risques de deadlock ;
- verrou pessimiste de chaque Stock, création append-once du mouvement puis
  hausse optimiste de la quantité ;
- idempotence portée par l’unicité existante
  `(organization, product, source_type, source_reference_id)` : un rejeu du
  même retour ne modifie plus le Stock ;
- résultat typé contenant StockId, StockMovementId, quantités précédente,
  retournée et résultante, prêt pour l’intégration Costing ;
- aucune mutation pour `restock=false` : Sales filtre ces lignes avant
  l’appel Inventory.

Le mécanisme est appelé par `CompleteReturnSaleService` avec la valorisation de
l’Epic 5.11 dans la même transaction tenant-scoped.

Commit :

```text
3909fbc feat(inventory): restock returned sale items
```

Validation consolidée :

```text
StockMovementTest + RepositoryInventoryStockRestockerTest : OK (5 tests, 25 assertions)
Suite PHPUnit complète : OK (537 tests, 2 571 assertions)
Conteneur Symfony test : OK
PHP-CS-Fixer : OK
PHPStan : OK
Deptrac layers : 0 violation, 10 uncovered
Deptrac modules : 0 violation, 10 uncovered
```

### Epic 5.11 — Return costing

**Statut : TERMINÉ**

- nouveau type applicatif Costing `SALE_RETURN`, entrée exigeant un coût
  explicite et traduite en ledger `StockValuationMovement SALE_RETURN` ;
- restauration au `unitCost` du `SaleLineCostSnapshot` original, jamais au
  coût moyen courant ni depuis Catalog/Pricing ;
- plusieurs lignes historiques d’un même produit sont agrégées en valeur et
  quantité, puis valorisées avec leur coût original pondéré à 12 décimales ;
- la valeur restaurée est ajoutée au total courant et le coût moyen résultant
  est recalculé ;
- chaque ledger Costing référence exactement le `StockMovement SALE_RETURN` et
  le `ReturnSaleId` source ;
- `CompleteReturnSaleService` exige un snapshot de coût pour toute ligne
  `restock=true`, sinon `SALE_LINE_COST_SNAPSHOT_NOT_FOUND` ;
- Stock, mouvement physique, valorisation, ledger et passage du retour à
  `COMPLETED` partagent la même transaction ;
- une erreur Inventory/Costing survient avant la complétion et provoque le
  rollback transactionnel ;
- un rejeu du mécanisme physique déjà commité ne recrée ni mouvement ni
  valorisation.

Commits atomiques :

```text
21b2396 feat(costing): value sale return movements
deb6ef9 feat(costing): restore original sale cost on return
```

Validation consolidée :

```text
Tests ciblés Costing/Inventory/Returns : OK (17 tests, 79 assertions)
Suite PHPUnit complète : OK (542 tests, 2 593 assertions)
Conteneur Symfony test : OK
PHP-CS-Fixer : OK
PHPStan : OK
Deptrac layers : 0 violation, 10 uncovered
Deptrac modules : 0 violation, 10 uncovered
```

### Epic 5.12 — Return amount calculation

**Statut : TERMINÉ**

- `ReturnAmountCalculator` alloue remise, base taxable, taxe, sous-total et
  total exclusivement depuis les snapshots immuables de `SaleLine` ;
- l’allocation utilise la cible cumulative après retour moins la cible
  cumulative avant retour, sans consulter Catalog, Pricing ni la fiscalité
  courante ;
- les calculs intermédiaires utilisent 24 décimales et les montants persistés
  12 décimales avec un arrondi déterministe `HalfUp` ;
- le dernier retour absorbe le résidu : plusieurs retours partiels somment
  exactement au montant, à la taxe et à la remise originaux ;
- les quantités et montants cumulés ne peuvent pas dépasser les snapshots de
  la ligne vendue ;
- toute ligne doit recevoir ses montants avant que `ReturnSale` passe à
  `COMPLETED` ;
- les montants sont conservés dans `sales.return_sale_line_amount`, table
  tenant-scoped sous RLS et append-only pour le rôle runtime ;
- la vente source reste verrouillée pendant le calcul, la valorisation du
  restock, la complétion et la persistance des snapshots monétaires.

Commit atomique :

```text
664c9c0 feat(sales): calculate return amounts from original snapshots
```

Validation consolidée :

```text
Tests ciblés Return amounts/Returns : OK (22 tests, 80 assertions)
Suite PHPUnit complète : OK (548 tests, 2 618 assertions)
Migration dev et test : Version20260828160000
Conteneur Symfony test : OK
PHP-CS-Fixer : OK
PHPStan : OK
Deptrac layers : 0 violation, 10 uncovered
Deptrac modules : 0 violation, 10 uncovered
```

### Epic 5.13 — Essential cash refund

**Statut : TERMINÉ**

- `PaymentRefund` appartient à Payments et conserve paiement, retour,
  `CashSession`, montant, devise, raison, acteur, statut et clé d’idempotence ;
- le ledger `payments.payment_refund` est tenant-scoped, protégé par RLS et
  append-only pour le rôle runtime ;
- Payments verrouille le paiement original avant de lire les cumuls, exige un
  paiement `CASH` confirmé et vérifie séparément les plafonds du paiement et du
  `ReturnSale` ;
- Sales expose uniquement le contrat `RefundableReturnProvider` : Payments ne
  dépend ni du domaine ni de la persistence Sales ;
- même clé et même payload renvoient le refund existant, tandis qu’un payload
  différent produit `IDEMPOTENCY_CONFLICT` ;
- CashManagement exige une session `OPEN` du même magasin et de la même devise,
  puis crée exactement un `CashMovement REFUND` sortant ;
- refund, mouvement Cash, audit et outbox sont enregistrés dans la même
  transaction tenant-scoped ; une faute Cash laisse le refund non persisté ;
- les permissions `PAYMENT_REFUND_CREATE` et `PAYMENT_REFUND_READ` sont
  intégrées aux rôles système ;
- l’API canonique `POST /api/payments/{paymentId}/refunds` exige
  `Idempotency-Key` et est publiée dans Swagger UI/ReDoc ;
- aucun chemin de remboursement ne dépend d’Inventory ni de Costing.

Commits atomiques :

```text
0e1bca1 feat(payments): add payment refund ledger
025ec96 feat(cash): record cash refund movement
1c01242 feat(payments): add cash payment refund
```

Validation consolidée :

```text
Suite PHPUnit complète : OK (556 tests, 2 639 assertions)
Migration dev et test : Version20260828180000
Route API Platform : POST /api/payments/{paymentId}/refunds
Conteneur Symfony test : OK
PHP-CS-Fixer : OK
PHPStan : OK
Deptrac layers : 0 violation, 10 uncovered
Deptrac modules : 0 violation, 10 uncovered
```

### API Returns — workflow complet

**Statut : TERMINÉ**

- commandes dédiées à la création d’un retour, à l’ajout de lignes et à son
  annulation, avec réutilisation du service atomique de complétion ;
- création limitée aux ventes `COMPLETED` et mutation sous verrou du retour ;
- permissions `SALE_RETURN_CREATE`, `SALE_RETURN_READ`,
  `SALE_RETURN_COMPLETE` et `SALE_RETURN_CANCEL`, intégrées aux rôles système ;
- contrôle du scope Store et garde opérationnelle sur toutes les mutations ;
- audit sensible et événements outbox versionnés pour création, ajout de ligne,
  complétion et annulation ;
- lectures tenant-scoped unitaire et par vente, projetées sans exposer le
  domaine ;
- six opérations du chapitre 27 exposées par API Platform : création, ajout de
  ligne, complétion, annulation, lecture et liste par vente ;
- contrat OpenAPI protégé par un test et visible dans Swagger UI/ReDoc en
  environnement de développement.

Commits atomiques :

```text
2fec4c5 feat(sales): add return sale application workflow
a36961b feat(sales): expose return sale API
```

Validation consolidée :

```text
Suite PHPUnit complète : OK (560 tests, 2 659 assertions)
Routes API Platform Returns : 6/6
Conteneur Symfony : OK
PHP-CS-Fixer : OK
PHPStan : OK
Deptrac layers : 0 violation, 10 uncovered
Deptrac modules : 0 violation, 10 uncovered
```

### Concurrence ReturnSale — limite cumulative

**Statut : TERMINÉ**

- test PostgreSQL réel avec deux connexions et le rôle runtime tenant-scoped ;
- la première complétion conserve le verrou pessimiste de la vente source ;
- une seconde tentative concurrente ne peut pas lire puis valider un cumul
  obsolète : elle attend le verrou ;
- après libération, elle observe les `8` unités déjà retournées et détecte que
  `8 + 8` dépasserait les `12` unités vendues ;
- le second retour reste `DRAFT`, sans dépassement du cumul complété.

Commit atomique :

```text
b17c473 test(returns): verify concurrent return quantity safety
```

Validation consolidée :

```text
ReturnSalePersistenceTest : OK (4 tests, 22 assertions)
Suite PHPUnit complète : OK (561 tests, 2 665 assertions)
PHP-CS-Fixer : OK
PHPStan : OK
```

### Atomicité PaymentRefund — matrice de fautes tardives

**Statut : TERMINÉ**

- scénario d’intégration exécuté sur PostgreSQL avec les repositories réels de
  Payment, PaymentRefund, CashMovement, audit et outbox ;
- fautes injectées après les écritures métier au niveau de l’audit, de l’outbox
  explicite et juste avant `COMMIT` ;
- chaque faute restaure un état sans `PaymentRefund`, sans `CashMovement`, sans
  audit et sans message outbox partiel ;
- le paiement reste `CONFIRMED` et la session de caisse reste `OPEN` ;
- le chemin nominal confirme que le refund, le mouvement Cash, l’audit et les
  deux messages outbox sont validés ensemble ;
- correction associée du type Doctrine `datetimetz_immutable`, qui accepte
  désormais les `TIMESTAMPTZ` PostgreSQL contenant des microsecondes.

Commits atomiques :

```text
8cf9aef fix(persistence): parse PostgreSQL timestamp microseconds
0ed9d9a test(payments): verify refund transaction rollback
```

Validation consolidée :

```text
CashPaymentRefundAtomicityTest : OK (4 tests, 26 assertions)
PostgreSqlDateTimeTzImmutableTypeTest : OK (1 test, 2 assertions)
Suite PHPUnit complète : OK (566 tests, 2 693 assertions)
Conteneur Symfony : OK
PHP-CS-Fixer : OK
PHPStan : OK
Deptrac layers : 0 violation, 10 uncovered
Deptrac modules : 0 violation, 10 uncovered
```

### Atomicité CompleteReturnSale — matrice PostgreSQL

**Statut : TERMINÉ**

- scénario d’intégration avec les repositories réels de Sales, Inventory,
  Inventory Costing, audit et outbox ;
- faute `inventory` injectée après création du mouvement `SALE_RETURN`, hausse
  du Stock, mise à jour de la valorisation et création du ledger Costing ;
- fautes supplémentaires injectées à l’audit, à l’outbox et juste avant
  `COMMIT` ;
- chaque faute maintient le retour `DRAFT`, le Stock à `8`, la valorisation à
  `3 200 XAF` et ne conserve ni montant de retour, ni mouvement physique, ni
  ledger Costing, ni audit, ni outbox ;
- le chemin nominal valide atomiquement le retour `COMPLETED`, le Stock à `14`,
  la valorisation à `5 600 XAF` et tous les ledgers associés.

Commit atomique :

```text
5604d36 test(returns): verify return transaction rollback
```

Validation consolidée :

```text
CompleteReturnSaleAtomicityTest : OK (5 tests, 49 assertions)
Suite PHPUnit complète : OK (571 tests, 2 742 assertions)
PHP-CS-Fixer : OK
PHPStan : OK
Deptrac layers : 0 violation, 10 uncovered
Deptrac modules : 0 violation, 10 uncovered
```

### Idempotence transverse Return / Refund

**Statut : TERMINÉ**

- `CompleteReturnSaleService` reconnaît désormais sous verrou un retour déjà
  `COMPLETED` et renvoie son résultat persisté après les contrôles
  d’autorisation et de scope ;
- le rejeu ne rappelle ni Inventory ni Costing et ne duplique aucun mouvement,
  montant, audit ou message outbox ;
- le refund rejoué avec la même `Idempotency-Key` et le même payload renvoie le
  même `PaymentRefund` persisté ;
- aucun second `CashMovement REFUND`, audit ou message outbox n’est créé ;
- les preuves sont exécutées avec les repositories PostgreSQL réels.

Commits atomiques :

```text
6cb1aa8 fix(sales): make return completion idempotent
429a523 test(payments): verify persisted refund replay
```

Validation consolidée :

```text
Tests ciblés Return/Refund : OK (17 tests, 100 assertions)
Suite PHPUnit complète : OK (571 tests, 2 745 assertions)
PHP-CS-Fixer : OK
PHPStan : OK
Deptrac layers : 0 violation, 10 uncovered
Deptrac modules : 0 violation, 10 uncovered
```

### Isolation tenant et scopes Store — Return / Refund

**Statut : TERMINÉ**

- sous le rôle PostgreSQL runtime du tenant A, une vente du tenant B est
  invisible et aucune création de `ReturnSale` n’est possible ;
- un paiement d’un autre tenant est également résolu comme absent avant toute
  création de refund ou sortie de caisse ;
- les handlers transmettent les permissions `SALE_RETURN_CREATE` et
  `PAYMENT_REFUND_CREATE` avec le scope exact du magasin source ;
- un refus d’autorisation empêche la sauvegarde du retour et tout
  `CashMovement REFUND` ;
- les scénarios négatifs conservent zéro effet métier ou transverse.

Commits atomiques :

```text
c20ed9e test(returns): verify tenant and store isolation
4c38e8b test(payments): verify refund tenant and store isolation
```

Validation consolidée :

```text
Tests ciblés isolation Return/Refund : OK (21 tests, 92 assertions)
Suite PHPUnit complète : OK (575 tests, 2 767 assertions)
PHP-CS-Fixer : OK
PHPStan : OK
Deptrac layers : 0 violation, 10 uncovered
Deptrac modules : 0 violation, 10 uncovered
```

### Démonstration consolidée et audit du Gate Lot 5

**Statut : TERMINÉ**

La démonstration PostgreSQL enchaîne dans une même fixture une vente cash déjà
valorisée, un retour partiel avec restock au coût original puis un remboursement
cash du montant snapshoté. Elle confirme :

```text
Stock après vente                    8
Stock après retour                  14
Valeur après vente          3 200 XAF
Valeur après retour         5 600 XAF
StockMovement SALE_RETURN            1
StockValuationMovement SALE_RETURN   1
PaymentRefund CONFIRMED               1
CashMovement REFUND                   1
Audits sensibles                      2
Messages outbox                       4
```

Commit atomique :

```text
897712c test(returns): demonstrate return and cash refund workflow
```

Audit du chapitre 39 :

- fondation Costing, moving weighted average, snapshots, ledgers append-only et
  atomicité CompleteSale : validés ;
- ReturnSale, quantités et montants cumulatifs, restock optionnel, coût original,
  concurrence, rollback et idempotence : validés ;
- PaymentRefund cash, plafonds, séparation Return/Refund, rollback et
  idempotence : validés ;
- permissions, scopes Store, audit, API/OpenAPI, contrat d’erreurs, PostgreSQL,
  contraintes, RLS et isolation tenant : validés ;
- absence de `Product.costPrice`, de calcul monétaire en `float` et des domaines
  explicitement hors périmètre : vérifiée ;
- ADR de bootstrap inchangée et toujours alignée ; aucune valorisation
  historique n’est inventée ;
- lint Composer/Symfony, PHP-CS-Fixer, PHPStan, Deptrac, PHPUnit, Composer audit,
  image production immutable et backup/restore : verts localement ;
- CI GitHub distante : workflow `Backend CI` terminé avec succès sur le commit
  `7c1e3ff` le 28 août 2026 ([run 33187545106](https://github.com/johnmabs/zandu-sales-manager/actions/runs/33187545106)).

Validation locale consolidée :

```text
Suite PHPUnit complète : OK (576 tests, 2 777 assertions)
PHP-CS-Fixer : OK
PHPStan : OK
Deptrac layers/modules : 0 violation
Composer validate : OK
Composer audit --locked : aucune vulnérabilité
Conteneur Symfony : OK
Image production immutable : health/ready OK
Backup/restore PostgreSQL : OK, 41 migrations restaurées
GitHub Actions : OK — run 33187545106 sur 7c1e3ff
```

### Epic 6.1 — Purchasing foundation

**Statut : TERMINÉ**

- la structure `Domain`, `Application/Contract`, `Infrastructure` et
  `Presentation/Api` du module Purchasing est matérialisée ;
- `PurchasingRuleViolation` fournit le contrat d’erreur métier stable ;
- Deptrac distingue le module Purchasing de son contrat applicatif et interdit
  les dépendances directes vers les domaines Catalog, Inventory et Costing ;
- le schéma PostgreSQL `purchasing` existe sur les bases locale et de test ;
- aucune table n’est créée avant de disposer des invariants tenant et RLS de
  l’agrégat concerné.

Commits atomiques :

```text
9db9c28 refactor(purchasing): add bounded context structure
83140b1 feat(database): add purchasing schema foundation
```

Validation locale :

```text
Suite PHPUnit complète : OK (577 tests, 2 781 assertions)
Composer validate et conteneur Symfony : OK
PHP-CS-Fixer : OK
PHPStan : OK
Deptrac layers/modules : 0 violation, 10 uncovered
Migrations dev/test : version 20260828200000 appliquée
```

### Epic 6.2 — Supplier

**Statut : TERMINÉ**

- l’agrégat organisation-scoped porte le nom, les coordonnées facultatives,
  l’audit, la version et le cycle `ACTIVE/INACTIVE/ARCHIVED` sans suppression ;
- les transitions et erreurs métier sont explicites et les fournisseurs
  archivés deviennent immuables tout en restant historiquement référençables ;
- `purchasing.supplier` applique FK organisation, contraintes SQL, versioning
  optimiste et RLS forcée ;
- les repositories ne peuvent ni lire ni modifier un fournisseur d’un autre
  tenant ;
- création, modification, activation, désactivation et archivage s’exécutent
  dans une transaction tenant après autorisation et guard opérationnel ;
- le propriétaire dispose de toutes les permissions Supplier, le manager et le
  comptable de la lecture, et le caissier d’aucun accès Supplier.

Commits atomiques :

```text
826b80e feat(purchasing): add supplier aggregate
f669e5a feat(purchasing): persist suppliers
6a95c0a feat(access): add supplier permissions
6dba00d feat(purchasing): add supplier management
6cbd682 test(access): align supplier permission catalog
```

Validation locale :

```text
Suite PHPUnit complète : OK (592 tests, 2 866 assertions)
Composer validate et audit : OK
Conteneur Symfony : OK
PHP-CS-Fixer : OK
PHPStan : OK
Deptrac layers/modules : 0 violation, 10 uncovered
Migrations dev/test : version 20260828210000 appliquée
```

### Epic 6.3 — PurchasingPolicy

**Statut : TERMINÉ**

- l’ADR-0023 fixe une policy globale de déploiement pour le MVP et reporte
  explicitement toute configuration tenant-scoped à une décision versionnée ;
- la baseline autorise les réceptions directes avec
  `PURCHASING_PURCHASE_ORDER_REQUIRED_FOR_RECEIPT=0` ;
- `PURCHASING_OVER_RECEIPT_POLICY=FORBIDDEN` protège l’invariant de quantité ;
- une valeur inconnue échoue explicitement au lieu d’activer un fallback ;
- le mode strict exigeant un PurchaseOrder est testable par configuration ;
- l’exception future d’over-receipt restera bornée à une opération, avec
  permission, motif et auteur, et ne deviendra pas un mode global permissif.

Commits atomiques :

```text
2782aa9 docs(adr): define purchasing receipt policy
6b33388 feat(purchasing): add purchasing policy
```

Validation locale :

```text
Suite PHPUnit complète : OK (596 tests, 2 878 assertions)
Composer validate et audit : OK
Conteneur Symfony : OK
PHP-CS-Fixer : OK
PHPStan : OK
Deptrac layers/modules : 0 violation, 10 uncovered
```

### Epic 6.4 — PurchaseOrder

**Statut : TERMINÉ**

- l’agrégat `PurchaseOrder` porte le fournisseur, le magasin destinataire, le
  numéro tenant-scoped, la devise, le total attendu, les audits et la version ;
- chaque ligne conserve la quantité saisie, le packaging facultatif, le facteur
  de conversion, la quantité de base et les coûts d’achat/inventaire exacts ;
- les invariants interdisent une devise différente, un produit dupliqué, un
  snapshot incohérent, un coût négatif et toute édition après confirmation ;
- le cycle `DRAFT → CONFIRMED → PARTIALLY_RECEIVED → FULLY_RECEIVED → CLOSED`
  est explicite, ainsi que l’annulation avant toute réception ;
- le cumul reçu refuse zéro, les valeurs négatives et la sur-réception ; une
  clôture partielle exige un motif audité ;
- `purchasing.purchase_order` et `purchase_order_line` appliquent FKs tenant,
  unicités, contraintes numériques, version optimiste et RLS forcée ;
- un trigger PostgreSQL protège les snapshots confirmés et n’autorise ensuite
  que l’évolution de `received_quantity` ;
- les événements métier du cycle sont enregistrés par l’agrégat.

Commits atomiques :

```text
8a2c393 feat(purchasing): add purchase order aggregate
a8b30b0 feat(purchasing): persist purchase orders
a1d1837 feat(purchasing): add purchase order lifecycle
```

Validation locale :

```text
Suite PHPUnit complète : OK (609 tests, 2 917 assertions)
Composer validate et audit : OK
Conteneur Symfony : OK
PHP-CS-Fixer : OK
PHPStan : OK
Deptrac layers/modules : 0 violation, 10 uncovered
Migrations dev/test : version 20260828220000 appliquée
```

### Epic 6.5 — Use cases PurchaseOrder

**Statut : TERMINÉ**

- les sept cas d’usage prévus sont disponibles : création, ajout, modification
  et retrait de ligne, confirmation, annulation et clôture ;
- les permissions `PURCHASE_ORDER_*` utilisent obligatoirement un scope Store :
  owner complet, store manager opérationnel, accountant en lecture et cashier
  sans accès Purchasing ;
- Catalog expose un snapshot achetable tenant-scoped qui exige un produit actif
  et un packaging actif autorisé à l’achat ;
- la quantité de base et le coût unitaire d’inventaire sont calculés côté
  application depuis le facteur Catalog, jamais acceptés depuis le client ;
- la confirmation revalide Store actif, Supplier actif, produits, packagings et
  facteurs de conversion avant de figer la commande ;
- création, édition et confirmation utilisent le mode opérationnel standard ;
  annulation et clôture utilisent le mode de remédiation ;
- une clôture partielle produit l’audit et l’événement outbox
  `PARTIAL_PURCHASE_ORDER_CLOSED` ;
- les erreurs `PURCHASE_ORDER_HAS_RECEIPTS` et `OVER_RECEIPT_NOT_ALLOWED` sont
  alignées sur le contrat documenté du lot.

Commits atomiques :

```text
3748247 feat(access): add purchase order permissions
7dd4584 feat(catalog): expose purchasable product snapshots
426a6e6 feat(purchasing): add purchase order draft use cases
74dd9f4 feat(purchasing): add purchase order lifecycle use cases
9d18a23 test(purchasing): cover purchase order use cases
8a59cc3 fix(purchasing): align purchase order error codes
144dcf6 fix(purchasing): publish partial close audit event
```

Validation locale :

```text
Suite PHPUnit complète : OK (614 tests, 2 882 assertions)
Composer validate et audit : OK
Conteneur Symfony : OK
PHP-CS-Fixer : OK
PHPStan : OK
Deptrac layers/modules : 0 violation, 10 uncovered
```

### Epic 6.6 — GoodsReceipt foundation

**Statut : TERMINÉ**

- l’agrégat `GoodsReceipt` porte le tenant, le magasin, le fournisseur, un
  PurchaseOrder facultatif, un numéro tenant-scoped, les audits et la version ;
- chaque `GoodsReceiptLine` conserve les quantités saisie et de base, le facteur
  de conversion, le packaging facultatif et les coûts réel et d’inventaire ;
- les snapshots imposent les calculs exacts
  `base = quantité × facteur` et `coût inventaire = coût réel ÷ facteur` ;
- le domaine distingue strictement une réception directe d’une réception liée,
  interdit les produits dupliqués et garantit une devise commune ;
- le cycle `DRAFT → POSTED` ou `DRAFT → CANCELLED` est explicite et toute
  mutation métier après sortie du brouillon est rejetée ;
- `purchasing.goods_receipt` et `goods_receipt_line` appliquent FKs tenant,
  unicités, contraintes numériques, version optimiste et RLS forcée ;
- un trigger PostgreSQL interdit toute mutation des lignes dès que la réception
  n’est plus en brouillon ;
- la publication du domaine ne crée volontairement encore aucun mouvement
  Inventory ou Costing : ces effets restent coordonnés par les Epics 6.8 à 6.10.

Commits atomiques :

```text
3a90d6b feat(purchasing): add goods receipt aggregate
1c6aed2 feat(purchasing): persist goods receipts
```

Validation locale :

```text
Tests ciblés domaine GoodsReceipt : OK (29 tests, 64 assertions)
Test d’intégration persistence : OK (1 test, 10 assertions)
Suite PHPUnit complète : OK (625 tests, 2 917 assertions)
Migrations dev/test : version 20260829080000 appliquée
Composer validate et audit : OK
Conteneur Symfony : OK
PHP-CS-Fixer : OK
PHPStan : OK
Deptrac layers/modules : 0 violation, 10 uncovered
```

### Epic 6.7 — Direct GoodsReceipt

**Statut : TERMINÉ**

- les permissions `GOODS_RECEIPT_CREATE`, `READ`, `POST` et `CANCEL` sont
  intégrées au catalogue : owner complet, store manager opérationnel,
  accountant en lecture et cashier sans accès Purchasing ;
- `CreateDirectGoodsReceipt` crée atomiquement l’en-tête et toutes ses lignes,
  sans fabriquer de PurchaseOrder artificiel ;
- la permission de création et le guard opérationnel utilisent obligatoirement
  le scope du magasin destinataire ;
- `PurchasingPolicy` interdit explicitement le flux direct lorsque le
  déploiement exige une commande fournisseur ;
- le fournisseur doit être actif et la devise de chaque coût doit correspondre
  à celle du magasin ;
- Catalog résout chaque produit/packaging achetable et fournit le facteur de
  conversion ; la quantité de base est calculée côté serveur ;
- le coût fourni est un coût unitaire d’inventaire en unité de base ; le coût
  réel facultatif reste absent pour une réception directe ;
- une réception directe vide est rejetée avant toute génération ou persistence.

Commits atomiques :

```text
3edb1b0 feat(access): add goods receipt permissions
04f3cf4 feat(purchasing): add direct goods receipt
```

Validation locale :

```text
Permissions GoodsReceipt : OK (2 tests, 62 assertions)
CreateDirectGoodsReceipt : OK (4 tests, 17 assertions)
Suite PHPUnit complète : OK (629 tests, 2 938 assertions)
Composer validate et audit : OK
Conteneur Symfony : OK
PHP-CS-Fixer : OK
PHPStan : OK
Deptrac layers/modules : 0 violation, 10 uncovered
```

### Epics 6.9 et 6.10 — Inventory et Costing des réceptions

**Statut : TERMINÉ — contrats prêts pour le coordinateur 6.8**

- Inventory reconnaît `PURCHASE_RECEIPT` comme mouvement entrant avec une source
  `GOODS_RECEIPT / GoodsReceiptId` ;
- l’unicité existante `(tenant, produit, source, référence)` rend la réception
  physique idempotente par produit ;
- `InventoryGoodsReceiver` verrouille les stocks dans l’ordre des ProductId,
  augmente les quantités et conserve les quantités avant/après dans son résultat
  de contrat versionné ;
- chaque nouveau mouvement physique est valorisé immédiatement par le service
  Costing existant, avec le même `stockMovementId` et le même instant ;
- Costing applique le coût unitaire d’inventaire en unité de base, augmente la
  valeur totale et recalcule la moyenne mobile pondérée ;
- le ledger `StockValuationMovement` accepte `PURCHASE_RECEIPT`, impose la source
  `GOODS_RECEIPT` et reste unique par mouvement physique ;
- les migrations `Version20260829100000` et `Version20260829110000` alignent les
  contraintes PostgreSQL des deux ledgers ;
- aucun endpoint ni changement d’état `GoodsReceipt` n’appelle encore ces
  contrats hors d’une transaction complète : le coordinateur 6.8 reste requis.

Commits atomiques :

```text
a3f10f5 feat(costing): value purchase receipts
4e05774 feat(inventory): receive supplier goods
```

Validation locale :

```text
Costing PURCHASE_RECEIPT : OK (9 tests, 53 assertions)
Inventory receiver et mouvement : OK (6 tests, 27 assertions)
Suite PHPUnit complète : OK (633 tests, 2 963 assertions)
Migrations dev/test : version 20260829110000 appliquée
Composer validate et audit : OK
Conteneur Symfony : OK
PHP-CS-Fixer : OK
PHPStan : OK
Deptrac layers/modules : 0 violation, 10 uncovered
```

### Epic 6.8 — Publication transactionnelle des réceptions

**Statut : TERMINÉ — Inventory, Costing et Purchasing coordonnés atomiquement**

- `PostGoodsReceipt` verrouille la réception de manière pessimiste dans une
  `TenantTransaction`, puis autorise l’action au scope Store ;
- le rejeu d’une réception déjà `POSTED` est sans effet et ne duplique ni
  mouvement, ni valorisation, ni événement ;
- le fournisseur actif, la devise du magasin, les snapshots Catalog et la
  policy opérationnelle sont revérifiés au moment de la publication ;
- une commande liée est elle aussi verrouillée et validée avant tout effet :
  même fournisseur, même magasin, produit commandé et absence de
  sur-réception ;
- `InventoryGoodsReceiver` crée les mouvements physiques et Costing dans la
  même transaction ; un résultat partiel ou incohérent bloque la publication ;
- les quantités reçues de la commande liée sont ensuite cumulées et persistées,
  puis la réception devient `POSTED` seulement après tous les ledgers ;
- l’audit de sécurité et l’événement métier
  `purchasing.goods_receipt_posted.v1` rejoignent la transactional outbox.

Commit atomique :

```text
5459196 feat(purchasing): add post goods receipt workflow
```

Validation locale :

```text
PostGoodsReceipt ciblé : OK (3 tests, 26 assertions)
Suite PHPUnit complète : OK (636 tests, 2 989 assertions)
Composer validate et audit : OK
Conteneur Symfony : OK
PHP-CS-Fixer : OK
PHPStan : OK
Deptrac layers/modules : 0 violation, 10 uncovered
```

### Epic 6.11 — Réceptions partielles liées

**Statut : TERMINÉ — création liée et cumul de publication couverts**

- `CreateLinkedGoodsReceipt` verrouille la commande et n’accepte que les états
  `CONFIRMED` ou `PARTIALLY_RECEIVED` ;
- le magasin, le fournisseur et la devise proviennent exclusivement de la
  commande, avec permission et garde opérationnelle au scope Store ;
- chaque ligne référence une `PurchaseOrderLine` réelle et réutilise ses
  snapshots produit, packaging et facteur de conversion ;
- le coût commercial réel est facultatif : lorsqu’il est fourni, le coût
  Inventory en unité de base est recalculé exactement ; sinon le coût de la
  commande est conservé ;
- la quantité de base est calculée côté serveur et prévalidée contre le reliquat
  commandé avant la persistence du brouillon ;
- la création ne modifie pas les quantités de la commande : seul
  `PostGoodsReceipt` les cumule après les écritures Inventory et Costing ;
- un scénario cumulatif publie successivement `4` puis `6` unités et démontre
  les transitions `PARTIALLY_RECEIVED` puis `FULLY_RECEIVED`.

Commit atomique :

```text
86001ba feat(purchasing): support partial goods receipts
```

Validation locale :

```text
Création liée et publication partielle ciblées : OK (8 tests, 53 assertions)
Suite PHPUnit complète : OK (641 tests, 3 016 assertions)
Composer validate et audit : OK
Conteneur Symfony : OK
PHP-CS-Fixer : OK
PHPStan : OK
Deptrac layers/modules : 0 violation, 10 uncovered
```

### Epic 6.12 — Sur-réception autorisée

**Statut : TERMINÉ — exception explicite, bornée et auditée**

- le brouillon conserve toujours la quantité physiquement constatée ; la
  décision de sur-réception est reportée à `PostGoodsReceipt`, sous verrou de la
  commande et dans la transaction Inventory/Costing ;
- le chemin par défaut rejette `received + incoming > ordered` avec
  `OVER_RECEIPT_NOT_ALLOWED` avant tout mouvement de stock ;
- l’exception exige une raison non vide de 500 caractères maximum et la
  permission sensible `PURCHASING_OVER_RECEIPT`, accordée uniquement au rôle
  Organization Owner dans le catalogue système ;
- `PurchaseOrderLine` accepte le cumul supérieur uniquement lorsque le handler
  transmet explicitement l’autorisation et classe alors la commande
  `FULLY_RECEIVED` ;
- la quantité n’est jamais tronquée : un scénario `10 commandé / 11 reçu`
  persiste bien `11` et Inventory reçoit les `11` unités ;
- l’audit `OVER_RECEIPT_AUTHORIZED` conserve l’acteur, la raison, la commande et
  le nombre de lignes dans la transactional outbox ;
- `Version20260829120000` aligne la contrainte PostgreSQL en conservant
  l’interdit absolu sur toute quantité reçue négative ;
- un test DB round-trip démontre également la persistence de `65` unités pour
  `60` commandées.

Commit atomique :

```text
c1c1671 feat(purchasing): add over receipt authorization
```

Validation locale :

```text
Sur-réception et persistence ciblées : OK (21 tests, 166 assertions)
Suite PHPUnit complète : OK (643 tests, 3 036 assertions)
Migrations dev/test : version 20260829120000 appliquée
Composer validate et audit : OK
Conteneur Symfony : OK
PHP-CS-Fixer : OK
PHPStan : OK
Deptrac layers/modules : 0 violation, 10 uncovered
```

### Epic 6.13 — Fondation GoodsReceiptCorrection

**Statut : EN COURS — domaine, création et persistence terminés**

- `GoodsReceiptCorrection` impose une raison, un cycle `DRAFT → POSTED`, des
  lignes produit uniques et l’immutabilité après publication ;
- chaque ligne conserve les quantités originale, effective courante et corrigée,
  puis calcule exactement la différence signée positive, négative ou nulle ;
- `CreateGoodsReceiptCorrection` exige `PURCHASING_RECEIPT_CORRECT`, un scope
  Store opérationnel et une réception source déjà `POSTED` ;
- la quantité effective est reconstruite depuis la réception originale et la
  somme des différences de toutes les corrections déjà publiées ;
- la réception source est verrouillée pendant la création afin de sérialiser le
  snapshot avec les autres workflows de correction ;
- `Version20260829130000` persiste les agrégats et lignes tenant-owned avec FKs,
  contrôles décimaux, trigger d’immutabilité et RLS forcée ;
- le repository DBAL fournit le verrou pessimiste et l’agrégation des
  différences publiées ; un round-trip PostgreSQL démontre le snapshot et la
  différence `-5`.

Commit atomique :

```text
33c2c78 feat(purchasing): add goods receipt correction foundation
```

Validation locale :

```text
Fondation correction ciblée : OK (9 tests, 101 assertions)
Suite PHPUnit complète : OK (649 tests, 3 062 assertions)
Migrations dev/test : version 20260829130000 appliquée
Composer validate et audit : OK
Conteneur Symfony : OK
PHP-CS-Fixer : OK
PHPStan : OK
Deptrac layers/modules : 0 violation, 10 uncovered
```

### Epic 6.13 — Inventory et Costing des corrections

**Statut : TERMINÉ pour les contrats — coordinateur de publication restant**

- Inventory expose `InventoryGoodsReceiptCorrector` et verrouille les stocks
  dans l’ordre des produits ;
- une différence positive crée `GOODS_RECEIPT_CORRECTION_IN`, augmente le stock
  et valorise l’entrée au coût unitaire de la réception originale ;
- une différence négative crée `GOODS_RECEIPT_CORRECTION_OUT`, protège le stock
  contre une quantité négative et utilise le coût moyen courant ;
- une différence nulle ne verrouille aucun stock et ne produit aucun ledger ;
- tous les mouvements portent la source idempotente
  `GOODS_RECEIPT_CORRECTION / GoodsReceiptCorrectionId` ;
- Costing dispose des deux types dédiés et produit les
  `StockValuationMovement` prospectifs correspondants ;
- `Version20260829140000` aligne les contraintes de types et de sources des
  ledgers physiques et valorisés.

Commit atomique :

```text
36624bf feat(costing): value goods receipt corrections
```

Validation locale :

```text
Inventory/Costing correction ciblés : OK (21 tests, 99 assertions)
Suite PHPUnit complète : OK (651 tests, 3 076 assertions)
Migrations dev/test : version 20260829140000 appliquée
Composer validate et audit : OK
Conteneur Symfony : OK
PHP-CS-Fixer : OK
PHPStan : OK
Deptrac layers/modules : 0 violation, 10 uncovered
```

### Epic 6.13 — Publication des corrections

**Statut : TERMINÉ — workflow compensatoire coordonné**

- `PostGoodsReceiptCorrection` verrouille correction et réception source dans
  une même `TenantTransaction` et autorise l’action au scope Store ;
- chaque `currentEffectiveQuantity` est recalculée juste avant les effets ; un
  snapshot concurrent devenu obsolète produit `GOODS_RECEIPT_CORRECTION_STALE` ;
- Inventory/Costing est appliqué avant le passage à `POSTED` et le nombre de
  mouvements non nuls est contrôlé ;
- une commande liée voit ses quantités reçues corrigées et son état recalculé
  entre `PARTIALLY_RECEIVED` et `FULLY_RECEIVED` ;
- la réception originale reste immutable ; seule la correction compensatoire
  est publiée ;
- audit `GOODS_RECEIPT_CORRECTED` et événement
  `purchasing.goods_receipt_corrected.v1` sont écrits dans la transactional
  outbox ;
- le rejeu d’une correction déjà publiée ne duplique aucun effet.

Commit atomique :

```text
20b649c feat(purchasing): post goods receipt corrections
```

Validation locale :

```text
Coordinateur correction ciblé : OK (2 tests, 9 assertions)
Suite PHPUnit complète : OK (653 tests, 3 095 assertions)
Composer validate et audit : OK
Conteneur Symfony : OK
PHP-CS-Fixer : OK
PHPStan : OK
Deptrac layers/modules : 0 violation, 10 uncovered
```

### Prochaine étape

Implémenter l’Epic 6.14 — `PurchaseReturn` distinct des corrections de réception,
avec cycle `DRAFT → SHIPPED/CANCELLED`, protection du stock et du reliquat
retournable, mouvement `PURCHASE_RETURN` et valorisation au coût moyen courant.

### Epic 6.14 — Fondation PurchaseReturn

**Statut : TERMINÉ — workflow et preuves PostgreSQL terminés**

- `PurchaseReturn` porte magasin source, fournisseur, réception/commande
  facultatives, raison obligatoire et cycle `DRAFT → SHIPPED/CANCELLED` ;
- les lignes conservent produit, quantité de base positive et lien facultatif à
  la ligne de réception, avec produit unique par retour ;
- toute mutation est interdite après expédition ou annulation ;
- les permissions Create/Read/Ship/Cancel sont attribuées au Store Manager,
  tandis que l’Accountant reste en lecture seule ;
- `Version20260829150000` ajoute FKs tenant, contraintes terminales, trigger
  d’immutabilité et RLS forcée ;
- le repository DBAL fournit verrou pessimiste et cumul des quantités déjà
  expédiées par produit/réception ;
- le round-trip PostgreSQL démontre une expédition persistée et un reliquat
  agrégé sans modifier la commande source.
- `CreatePurchaseReturn` verrouille la réception publiée, impose le magasin
  source et calcule le reliquat depuis la quantité reçue, les corrections
  publiées et les retours déjà expédiés ;
- Inventory verrouille et décrémente le stock via un mouvement idempotent
  `PURCHASE_RETURN`, tandis que Costing valorise la sortie au coût moyen
  courant sans coût entrant ;
- `Version20260831100000` ajoute les types et sources physiques/valorisés et
  répare la contrainte de valeur totale qui omettait les corrections IN/OUT ;
- `ShipPurchaseReturn` revalide le reliquat et le stock dans la transaction,
  rend le rejeu sans effet, conserve la quantité reçue de la commande source,
  puis écrit audit et outbox.
- `CancelPurchaseReturn` annule uniquement un brouillon, reste idempotent au
  rejeu, refuse un retour expédié et écrit audit/outbox sans effet Inventory ou
  Costing.
- l’API `PurchaseReturn` expose création par magasin, ajout de ligne,
  consultation, expédition et annulation ; ses lectures sont tenant-scopées et
  autorisées par la permission `PURCHASE_RETURN_READ`.
- l’intégration PostgreSQL prouve l’expédition physique et valorisée, son rejeu
  sans doublon, ainsi que l’écriture unique de l’audit et de l’outbox ;
- un échec injecté dans l’outbox annule intégralement document, stock,
  mouvements, valorisation et audit ;
- deux connexions PostgreSQL prouvent le verrou du retour contre une expédition
  concurrente ; le rôle runtime ne peut lire le document depuis un autre tenant.

Commit atomique :

```text
46c4d61 feat(purchasing): add purchase return foundation
1805484 feat(purchasing): create purchase returns
4020e91 feat(costing): value purchase returns
ebaaf6c feat(purchasing): ship purchase returns
eb79b36 feat(purchasing): cancel purchase returns
971ad06 feat(api): expose purchase returns
68e2147 feat(purchasing): add purchase return lines
f662689 test(purchasing): verify purchase return shipment
02d39bc test(purchasing): verify purchase return rollback
13bddc9 test(purchasing): verify purchase return concurrency
```

Validation locale : tests PostgreSQL Purchasing OK (5 tests, 35 assertions),
PHPStan et architecture vertes. La suite complète doit être confirmée par la
CI lors de la prochaine publication manuelle.

### Epics 7.1 à 7.17 + annulation — cycle StockCount physique complet

**Statut : TERMINÉ — cycle physique DRAFT → SHIPPED → RECEIVED**

- `StockTransfer` impose deux magasins distincts, un produit unique par ligne
  et le cycle `DRAFT → SHIPPED → RECEIVED` ou `DRAFT → CANCELLED` ;
- le brouillon permet ajout, modification, suppression et annulation, avec
  permissions atomiques et scopes Store ;
- `Version20260831110000` persiste documents et lignes avec contraintes, verrou
  optimiste, repository DBAL et RLS forcée ;
- `ShipStockTransfer` verrouille le document puis les stocks source dans
  l’ordre des produits, accepte `0 <= shipped <= requested` et ignore les
  mouvements nuls ;
- chaque quantité positive produit une sortie idempotente `TRANSFER_OUT` liée à
  `TRANSFER / StockTransferId`, puis un événement outbox transactionnel ;
- `Version20260831120000` étend le ledger et distingue `TRANSFER_OUT` de
  `TRANSFER_IN` dans la clé d’idempotence, condition nécessaire à la réception.
- le transit reste représenté par le document `SHIPPED` et ses quantités, sans
  créer un stock physique artificiel ;
- `ReceiveStockTransfer` fige toutes les quantités reçues en une fois, ignore
  les mouvements nuls, crée ou verrouille le stock destination et produit les
  `TRANSFER_IN` idempotents ;
- la réception reste autorisable en remédiation pendant une suspension, exige
  le scope du magasin destination et publie un résumé d’écart dans l’outbox ;
- `Version20260831130000` persiste `receivedBy/receivedAt` et garantit leur
  cohérence avec le statut `RECEIVED`.
- chaque ligne reçue expose l’écart immuable `shipped - received` ; le transfert
  fournit le drapeau et la collection des seuls écarts positifs, sans créer de
  mouvement compensatoire au magasin destination.
- l’expédition retire la valeur au coût moyen source et fige coût unitaire et
  valeur transportée sur la ligne ; la réception réutilise exclusivement ce
  coût, initialise si nécessaire la valorisation destination et conserve la
  différence comme perte de valeur en transit ;
- `Version20260831140000` persiste les snapshots monétaires et autorise les
  mouvements de valorisation `TRANSFER_OUT/TRANSFER_IN` liés au transfert.
- `Version20260831150000` persiste sous RLS les commandes de transfert par
  phase et refuse un même `commandId` réutilisé avec un payload différent ; un
  rejeu identique retourne le document existant sans mouvement ni outbox en
  double ;
- deux connexions PostgreSQL prouvent qu'une expédition concurrente avec une
  vente, ou deux expéditions incompatibles, se sérialisent sur le Stock source :
  la quantité ne devient jamais négative et aucune mise à jour n'est perdue ;
- une réception déjà expédiée reste une remédiation autorisable pendant la
  suspension, tandis que création et expédition restent des opérations
  standard ; un transfert `SHIPPED` bloque la fermeture des magasins source et
  destination via `STOCK_TRANSFER_IN_TRANSIT` jusqu'à sa réception ;
- l'Epic 7.8 introduit `StockCount`, `StockCountId`, les statuts
  `DRAFT/OPEN/FINALIZING/COMPLETED/CANCELLED`, les modes `BLIND/GUIDED`, les
  scopes `FULL/PARTIAL` et les compteurs de progression, avec `BLIND` par
  défaut.
- l'Epic 7.9 ajoute l'agrégat séparé `StockCountLine`, son identifiant typé, le
  snapshot `expectedQuantity`, la distinction explicite entre quantité non
  comptée et zéro, la révision et les états `PENDING/RECONCILED` ;
- `CreateStockCount` crée atomiquement un brouillon autorisé dans le scope
  Store, bloque les magasins suspendus, valide les produits physiques suivis
  par Inventory pour un périmètre PARTIAL, puis publie l'outbox de création ;
- le périmètre PARTIAL est snapshoté sans doublon dans le document, tandis que
  FULL reste vide jusqu'à sa résolution serveur au démarrage ; les six
  permissions StockCount sont attribuées au Store Manager et la lecture à
  l'Accountant ;
- `Version20260831160000` persiste le brouillon et son périmètre demandé avec
  contraintes, index, verrou optimiste préparé et RLS forcée.
- `StartStockCount` verrouille le document DRAFT, résout le périmètre FULL
  depuis les positions du magasin ou réutilise le snapshot PARTIAL, trie les
  produits puis passe atomiquement le document à `OPEN` ;
- chaque produit produit une `StockCountLine` séparée avec sa quantité
  théorique figée ; l'absence de position devient explicitement zéro sans créer
  de Stock vide, puis l'outbox `inventory.stock_count_started.v1` est publiée ;
- `Version20260831170000` persiste les lignes et les
  `OpenStockCountScope` sous RLS, avec FKs tenant/store, unicité
  `(organization, store, product)` et index de reprise de réconciliation ;
- les acquisitions de scope utilisent un advisory lock transactionnel stable :
  deux comptages concurrents d'un même produit sont sérialisés puis le second
  reçoit `STOCK_COUNT_ALREADY_OPEN_FOR_PRODUCT` ;
- le repository central des mouvements prend le même verrou avant toute
  nouvelle écriture et renvoie `STOCK_COUNT_PRODUCT_LOCKED` pour un produit
  compté, tout en autorisant les produits hors scope et les rejeux idempotents
  déjà enregistrés.
- `RecordStockCount` accepte explicitement zéro, refuse les quantités négatives
  et permet de corriger une saisie tant que le document reste `OPEN` ; chaque
  écriture incrémente la révision et la version propres à la ligne ;
- la version attendue est vérifiée sous verrou PostgreSQL et une lecture
  obsolète produit `STOCK_COUNT_LINE_VERSION_CONFLICT` sans écraser la saisie
  concurrente ;
- le batch trie et verrouille les produits dans un ordre déterministe, rejette
  les doublons et s'exécute dans une transaction unique : un conflit tardif
  annule toutes les lignes déjà traitées ;
- `countedLineCount` n'augmente que lors de la première saisie d'une ligne ; une
  correction ne gonfle pas artificiellement la progression.
- `BeginStockCountFinalization` verrouille le document puis toutes ses lignes,
  exige à la fois un compteur complet et l'absence réelle de
  `countedQuantity IS NULL`, puis passe atomiquement de `OPEN` à `FINALIZING` ;
- le démarrage de finalisation est rejouable sans deuxième outbox, reste une
  opération de remédiation sur un magasin suspendu et conserve tous les scopes
  produits ;
- le verrou partagé avec `RecordStockCount` sérialise une dernière saisie face
  à la finalisation ; dès `FINALIZING`, toute nouvelle correction reçoit
  `STOCK_COUNT_NOT_OPEN`.
- `ReconcileStockCountBatch` verrouille le document `FINALIZING`, sélectionne
  uniquement les lignes `PENDING` dans l'ordre produit et borne chaque lot à
  500 lignes ; un appel sans ligne restante est un rejeu sans effet ;
- chaque ligne compare sous verrou le Stock courant à son snapshot théorique ;
  une divergence, y compris l'apparition inattendue d'une position absente au
  démarrage, renvoie `STOCK_COUNT_SNAPSHOT_CONFLICT` et annule tout le batch ;
- un écart positif crée ou corrige la position avec
  `STOCK_COUNT_CORRECTION_IN`, un écart négatif produit
  `STOCK_COUNT_CORRECTION_OUT`, et un écart nul marque seulement la ligne sans
  mouvement artificiel ;
- les mouvements portent la source `STOCK_COUNT / StockCountId` : le verrou de
  scope les autorise uniquement pour le comptage propriétaire et continue de
  refuser toute écriture concurrente étrangère ;
- `Version20260831180000` étend les contraintes du ledger physique à ces deux
  mouvements et à leur source ; le compteur `reconciledLineCount` progresse
  dans la même transaction que les Stocks, mouvements et lignes ;
- une preuve PostgreSQL injecte un conflit sur la deuxième ligne après une
  première correction : quantité, ledgers physique et de valorisation, états
  de ligne et compteur sont tous restaurés ;
- l'Epic 7.16 traite chaque transaction de batch comme un checkpoint durable :
  une preuve vide l'EntityManager et recrée le handler et les repositories après
  un premier lot commité, puis reprend exclusivement les lignes `PENDING` ;
- le Stock déjà corrigé, l'identifiant de son mouvement et son état
  `RECONCILED` restent inchangés pendant les lots suivants ; le batch à variance
  zéro n'ajoute aucun mouvement et un appel après épuisement retourne `0/0`
  sans effet secondaire.
- `CompleteStockCountFinalization` verrouille le document, exige le statut
  `FINALIZING`, un compteur entièrement réconcilié et l'absence réelle de ligne
  `PENDING` sous verrou PostgreSQL ; une tentative prématurée renvoie
  `STOCK_COUNT_HAS_PENDING_LINES` ;
- la transition vers `COMPLETED` fige `completedBy/completedAt`, libère les
  `OpenStockCountScope` et publie `inventory.stock_count_completed.v1` dans une
  transaction unique, tout en conservant toutes les lignes comme preuve ;
- `Version20260831190000` impose en base la cohérence des paires d'audit, des
  statuts DRAFT/OPEN/FINALIZING/COMPLETED/CANCELLED et des trois compteurs lors
  de la complétion ;
- un rejeu d'un document déjà terminé ne republie pas l'outbox ; une preuve
  d'échec de publication confirme le rollback du statut, de l'audit et de la
  libération des scopes.
- `CancelStockCount` accepte DRAFT et OPEN avec la permission dédiée et le mode
  de remédiation ; il fige `cancelledBy/cancelledAt`, publie
  `inventory.stock_count_cancelled.v1` avec le statut antérieur et reste
  rejouable sans deuxième événement ;
- l'annulation OPEN libère les scopes mais conserve toutes les lignes snapshot
  et ne crée aucun mouvement ; DRAFT ne possède ni lignes ni scope à retirer,
  tandis que FINALIZING et COMPLETED reçoivent `STOCK_COUNT_CANNOT_CANCEL` ;
- une panne d'outbox restaure atomiquement le statut OPEN, les champs d'audit
  et tous les scopes. La contrainte de cycle de vie de
  `Version20260831190000` couvre les annulations issues de DRAFT comme de OPEN.
- chaque `STOCK_COUNT_CORRECTION_OUT` est désormais valorisé au coût moyen
  courant ; un `STOCK_COUNT_CORRECTION_IN` réutilise strictement le coût moyen
  existant et ignore toute tentative de surcharge arbitraire ;
- si aucune valorisation n'existe, le batch renvoie
  `INVENTORY_COST_REQUIRED`. La reprise exige un `manualUnitCost`, un motif de
  1 à 500 caractères et la permission sensible `INVENTORY_COST_ASSIGN`,
  réservée par défaut au propriétaire de l'organisation ;
- le motif manuel est audité sur le mouvement physique et chaque correction
  produit dans la même transaction un `StockValuationMovement` source
  `STOCK_COUNT`, relié à la fois au `StockCountId` et au `StockMovement` ;
- `Version20260901000000` étend les contraintes PostgreSQL de type, source et
  variation de valeur aux corrections de comptage. Les preuves couvrent OUT au
  coût moyen, IN au coût moyen, refus sans coût, reprise autorisée avec coût et
  rollback intégral avant reprise.
- `StockCountRepository::hasOpenForStore` exploite l'index tenant/store/statut
  et expose `OPEN_STOCK_COUNT` pour les statuts `OPEN` et `FINALIZING` seulement ;
  DRAFT, COMPLETED et CANCELLED ne bloquent pas la fermeture ;
- le câblage direct historique d'Inventory masquait le provider Cash. Un
  `CompositeStoreClosureBlockerProvider` fusionne désormais sans doublon les
  providers tagués Cash, Purchasing et Inventory ;
- Purchasing fournit enfin les blockers documentés du Lot 6 :
  `OPEN_PURCHASE_ORDER`, `DRAFT_GOODS_RECEIPT`, `OPEN_PURCHASE_RETURN` et
  `OPEN_GOODS_RECEIPT_CORRECTION`. Les requêtes PostgreSQL tenant-scoped
  distinguent les documents réellement ouverts de leurs états terminaux ;
- une preuve avec le composite Symfony réel vérifie que l'annulation d'un
  comptage retire immédiatement `OPEN_STOCK_COUNT`, tandis que les tests
  PostgreSQL Purchasing vérifient l'apparition puis le retrait de chaque
  blocker au changement d'état.

Validation StockCount/Costing ciblée verte. Suite complète : 739 tests, 3 620
assertions. PHPStan, PHP-CS-Fixer, conteneur Symfony et Deptrac
layers/modules verts (0 violation, 10 uncovered).

### Prochaine sous-étape

Exposer l'API StockCount prévue par le Lot 7, avec le filtrage serveur requis
pour le mode BLIND.

### API StockTransfer — workflow complet exposé

**Statut : TERMINÉ — brouillon, expédition, réception et annulation**

- API Platform expose la collection, le détail et la création des transferts,
  ainsi que l'ajout, la modification et la suppression des lignes, puis les
  commandes `ship`, `receive` et `cancel` ; les neuf opérations figurent dans
  Swagger UI et ReDoc en développement ;
- les lectures restent tenant-scopées et appliquent une autorisation en OU sur
  les magasins source et destination : un transfert inaccessible est retiré
  de la collection et le détail inconnu produit le contrat `NOT_FOUND` ;
- les mutations réutilisent les handlers applicatifs et leurs transactions ;
  expédition et réception exigent l'en-tête `Idempotency-Key`, refusent les
  lignes dupliquées et rejouent une commande identique sans mouvement ni
  événement supplémentaire ;
- l'ajout d'une ligne revalide côté serveur que le produit est physique et
  suivi en inventaire, sans faire confiance au payload client ;
- la représentation publique expose quantités demandées, expédiées, reçues et
  écarts de transit, mais ne divulgue ni coût unitaire ni valeur transportée ;
- le parcours API prouve les rollbacks sur expédition et réception invalides,
  le rejeu, les mouvements `TRANSFER_OUT/TRANSFER_IN`, la consultation, la
  suppression d'une ligne et l'annulation d'un second brouillon ;
- la réception vers une position destination inexistante persiste désormais
  le Stock avant son mouvement, respectant la clé étrangère PostgreSQL sans
  compromettre l'atomicité de la transaction.

Validation locale complète : 742 tests, 3 698 assertions. Composer, conteneur
Symfony, PHPStan et PHP-CS-Fixer sont verts. Deptrac layers/modules reste à
0 violation et 10 dépendances non classées.

### API StockCount — workflow complet exposé

**Statut : TERMINÉ — workflow complet, confidentialité BLIND et PostgreSQL validés**

- API Platform expose la collection, le détail et la création par magasin,
  l'ouverture, la saisie simple et batch, la finalisation reprenable et
  l'annulation ; les huit opérations sont présentes dans OpenAPI ;
- les lectures utilisent une projection applicative tenant-scopée, appliquent
  `STOCK_COUNT_READ` au scope Store et traduisent une ressource absente ou
  cross-tenant en `NOT_FOUND` ;
- la projection serveur masque complètement `expectedQuantity` et `variance`
  pour un comptage `BLIND` en `DRAFT/OPEN`, puis les révèle lorsque la saisie
  est gelée ; le mode `GUIDED` les expose pendant la saisie ;
- le endpoint de finalisation démarre `FINALIZING`, réconcilie par checkpoints
  transactionnels bornés à 500 lignes, reprend les seules lignes `PENDING`,
  accepte les coûts manuels protégés déjà prévus par l'Epic 7.18, puis clôture
  et libère les scopes ; un rejeu d'un comptage terminé reste sans effet ;
- le repository DBAL sait lister les comptages dans le tenant actif et utilise
  désormais le contrat public `NOT_FOUND` pour les lectures et verrous absents ;
- un parcours API PostgreSQL couvre BLIND/GUIDED, saisie simple/batch,
  finalisation, rejeu, mouvements physiques/valorisés et annulation.

Validation ciblée PostgreSQL : 1 test, 55 assertions. Suite complète : 746
tests, 3 767 assertions. PHPStan, PHP-CS-Fixer, conteneur Symfony et Deptrac
layers/modules sont verts (0 violation, 10 uncovered). Composer est valide et
l'audit ne relève aucune vulnérabilité.

# Frontend Foundation

## Epic F0.1 — Workspace

**Statut : TERMINÉ — workspace pnpm reproductible initialisé**

- `frontend/` contient les membres `apps/admin`, `apps/pos`,
  `packages/shared` et `packages/config` sous un workspace pnpm unique ;
- `pnpm-workspace.yaml` découvre uniquement `apps/*` et `packages/*` ;
- le manifeste racine épingle pnpm `11.25.0`, centralise les scripts de test et
  ne requiert aucun orchestrateur supplémentaire ;
- Admin et POS déclarent `@zandu/shared` via `workspace:*` ; deux tests Node
  natifs prouvent la résolution locale depuis chaque application ;
- `pnpm-lock.yaml` est l'unique lockfile frontend et une installation
  `--frozen-lockfile` réussit sur les cinq projets ;
- `.github/workflows/frontend-ci.yml` exécute cette installation verrouillée et
  les tests de résolution Admin/POS sur chaque changement du workspace ;
- l'ADR-0024 accepte pnpm et réserve l'introduction future de Turborepo/Nx à un
  besoin mesuré et une décision distincte ;
- les dépendances générées, le store pnpm et les couvertures frontend sont
  ignorés par Git.

Validations : installation pnpm avec lockfile gelé OK ; 2 tests workspace, 2
réussis ; résolution récursive des cinq projets OK ; syntaxe YAML du workflow
Frontend CI valide ; `git diff --check` OK.
## Epic F0.2 — Admin bootstrap

**Statut : TERMINÉ — application Next.js initialisée**

- `frontend/apps/admin` est une application Next.js 16, React 19 et TypeScript ;
- la configuration TypeScript active `strict` ;
- l'App Router expose `GET /`, qui rend le placeholder de shell `Zandu Admin` ;
- les scripts `dev`, `build`, `start`, `test` et `typecheck` permettent le cycle
  de développement et les validations ciblées ;
- le test Node de l'Admin vérifie le placeholder de la page racine, en plus du
  test existant de résolution du package workspace partagé ;
- les artefacts générés par Next.js et TypeScript sont ignorés par Git.

Validations : `pnpm install --frozen-lockfile` OK ; tests Admin : 2 réussis ;
`tsc --noEmit` OK ; build de production Next.js/Webpack OK ; `GET /` servi
localement retourne une page contenant `<h1>Zandu Admin</h1>` ;
`git diff --check` OK.

Commit recommandé : `build(admin): initialize Next.js application`.

## Epic F0.3 — POS bootstrap

**Statut : TERMINÉ — application React/Vite/Tauri initialisée**

- `frontend/apps/pos` utilise React 19, TypeScript strict et Vite ;
- le shell React rend le placeholder `Zandu POS` dans le navigateur ;
- `src-tauri/` intègre le runtime Tauri v2, sa configuration de fenêtre, ses
  capabilities minimales et son icône applicative, sans introduire de logique
  métier ou de stockage local ;
- les scripts `dev`, `build`, `preview`, `tauri`, `test` et `typecheck` couvrent
  le cycle de développement du POS ;
- les tests Node vérifient le placeholder POS et la résolution du package
  workspace partagé ;
- `Cargo.lock`, le lockfile pnpm et les artefacts générés sont gérés pour une
  installation reproductible.

Validations : `pnpm install --frozen-lockfile` OK ; tests POS : 2 réussis ;
`tsc -b --pretty false` OK ; build Vite OK ; le serveur de développement sert
l'entrée POS sur `http://127.0.0.1:1420/` ; `cargo check` Tauri OK ; `tauri
dev` lance Vite puis le binaire desktop ; `cargo fmt -- --check` et `git diff
--check` OK.

Commit recommandé : `build(pos): initialize React Vite Tauri application`.

## Epic F0.4 — TypeScript conventions

**Statut : TERMINÉ — socle TypeScript strict partagé appliqué aux deux applications**

- `frontend/tsconfig.base.json` centralise `strict`, `noImplicitAny`, les accès
  indexés vérifiés, les variables `catch` en `unknown`, les propriétés
  optionnelles exactes et les contrôles d’override/cohérence de casse ;
- les configurations Admin et POS (navigateur et Vite) héritent de ce socle,
  tout en conservant leurs options propres de runtime et de build ;
- un test Node natif vérifie les garanties du socle et son héritage par les
  trois configurations TypeScript ; il est inclus dans `pnpm test` à la racine
  du workspace ;
- le socle impose l’absence d’`any` implicite et privilégie les valeurs
  inconnues contrôlées aux frontières ; la validation de contrats réseau et
  l’usage des modèles générés restent réservés au futur API client.

Validations : test des conventions TypeScript (2 sous-tests) OK ; `pnpm test`
OK ; typecheck Admin et POS OK ; builds de production Next.js et Vite OK ;
`git diff --check` OK.

Commit recommandé : `build(frontend): configure strict TypeScript`.

## Epic F0.5 — Lint / Format / Imports

**Statut : TERMINÉ — outillage de qualité partagé configuré**

- ESLint en configuration plate applique les règles TypeScript communes : pas
  d’`any` explicite, pas de cast `as` non vérifié, imports de types explicites
  et code inutilisé refusé (sauf identifiants volontairement préfixés par `_`) ;
- les imports sont ordonnés, séparés par groupes et dédupliqués ; Admin et POS
  ne peuvent pas s’importer mutuellement, et les packages partagés ne peuvent
  pas dépendre d’une application ;
- Prettier fournit le formatage reproductible du workspace en excluant les
  artefacts générés Next.js, Vite et Tauri ;
- les commandes racine `lint`, `format`, `format:check`, `typecheck`, `test`
  et `build` sont disponibles ; les tests Foundation vérifient leur présence et
  les règles essentielles de qualité.

Validations : `pnpm install --frozen-lockfile` OK ; `pnpm format:check` OK ;
`pnpm lint` OK ; `pnpm typecheck` OK ; `pnpm test` OK (tests Foundation et
tests Admin/POS) ; builds de production Next.js et Vite OK ; `git
diff --check` OK.

Commit recommandé : `chore(frontend): add code quality tooling`.

## Epic F0.6 — Design tokens

**Statut : TERMINÉ — primitives visuelles stables partagées par Admin et POS**

- le package workspace `@zandu/design-tokens` expose une feuille CSS de tokens
  strictement visuels : couleurs sémantiques, espacement, rayons, typographie,
  breakpoints et niveaux de superposition ;
- Admin et POS dépendent explicitement de ce package et utilisent les mêmes
  tokens pour leur canvas, texte, typographie, espacement et titre de shell ;
- aucun composant ni règle métier n’est introduit : les futures densités et
  composants de chaque interface peuvent rester distincts tout en partageant
  l’identité visuelle ;
- un test vérifie la présence des primitives stables et leur consommation par
  les deux applications.

Validations : `pnpm install --frozen-lockfile` OK ; `pnpm format:check` OK ;
`pnpm lint` OK ; `pnpm typecheck` OK ; `pnpm test` OK (3 tests Foundation,
plus les tests Admin/POS) ; builds de production Next.js et Vite OK ; `git
diff --check` OK.

Commit recommandé : `feat(ui): add shared design tokens`.

## Epic F0.7 — UI primitives

**Statut : TERMINÉ — bibliothèque de primitives génériques partagée**

- le package workspace `@zandu/ui` fournit Button, IconButton, champs de
  formulaire, choix, Dialog, Drawer, DropdownMenu, Tooltip, feedback states,
  pagination et les autres primitives Foundation demandées ;
- ses styles utilisent exclusivement les tokens visuels partagés et sont
  consommés par Admin et POS via une dépendance workspace explicite ;
- les composants restent génériques et ne connaissent aucun concept métier ;
- les contrôles restent sémantiques, les focus sont visibles, et Dialog/Drawer
  gèrent focus initial, cycle de tabulation et fermeture avec Échap ;
- un test vérifie les exports, les contrats d’accessibilité et l’usage des
  tokens ; le typecheck compile aussi directement le package UI.

Validations : `pnpm install --frozen-lockfile` OK ; `pnpm format:check` OK ;
`pnpm lint` OK ; `pnpm typecheck` OK ; `pnpm test` OK (4 tests Foundation,
plus les tests Admin/POS) ; builds de production Next.js et Vite OK ; `git
diff --check` OK.

Commit recommandé : `feat(ui): add shared UI primitives`.

## Epic F0.8 — Formatting métier

**Statut : TERMINÉ — formats partagés sans calcul métier frontend**

- le package workspace `@zandu/domain-formatting` expose `formatMoney`,
  `formatQuantity`, `formatBusinessDate`, `formatDateTime` et
  `formatPercentage` ;
- Money et Quantity restent des chaînes décimales : groupement, déplacement
  décimal des ratios et localisation sont réalisés sans convertir la valeur API
  en nombre JavaScript ;
- BusinessDate est validée et formatée comme date de calendrier UTC, sans la
  redériver depuis le fuseau navigateur ; les timestamps utilisent le fuseau
  Store explicitement fourni ;
- les tests couvrent une valeur monétaire supérieure à la précision numérique
  JavaScript, pourcentages, dates/fuseaux et transports invalides ;
- les tests Foundation exécutent les sources TypeScript sans transformation
  sémantique, et le package est compilé par `pnpm typecheck`.

Validations : `pnpm install --frozen-lockfile` OK ; `pnpm format:check` OK ;
`pnpm lint` OK ; `pnpm typecheck` OK ; `pnpm test` OK (5 tests Foundation,
plus les tests Admin/POS) ; builds de production Next.js et Vite OK ; `git
diff --check` OK.

Commit recommandé : `feat(formatting): add domain display helpers`.

## Epic F0.9 — API client

**Statut : TERMINÉ — transport Symfony réel et contrat OpenAPI généré**

- `@zandu/api-client` centralise le transport HTTP, expose une façade stable
  pour le parcours Foundation et adapte les réponses Symfony au modèle
  frontend, notamment `token` vers `accessToken` ;
- les réponses sans contenu, dont le `204` de logout, ne sont plus décodées
  comme du JSON ; les erreurs et correlation IDs restent gérés au même bord ;
- login, refresh et logout sont documentés avec leurs payloads réels dans
  l'OpenAPI backend ; les types générés sont isolés sous
  `packages/api-client/src/generated/` et reproductibles avec
  `make frontend-openapi` ;
- le test d'intégration MSW branche `AuthenticationManager` sur le transport
  réel et couvre session, accès effectif et magasins accessibles.

Validations : génération OpenAPI, formatage, lint, typecheck, tests Foundation,
unitaires, composants et intégration, ainsi que builds Admin/POS réussis.

## Epic F0.10 — Error contract

**Statut : TERMINÉ — contrat UX d’erreur partagé, sans client API**

- le package workspace `@zandu/error-contract` définit `ApiError`, `UiError`,
  `FieldErrors` et `ErrorMapper` ;
- le mapper couvre validation, authentification, autorisation, ressource
  absente, conflit métier, conflit d’idempotence, réseau et erreur serveur ;
- les réponses backend actuelles `code`, `message`, `correlationId` sont
  compatibles avec le contrat ; le code pilote le message UX et aucun statut
  HTTP n’est montré directement à l’utilisateur ;
- le mapper préserve erreurs de champ et correlation ID, fournit retry/action
  quand pertinent, et accepte des mappings de code enrichis par les features ;
- les tests couvrent les huit catégories, `INSUFFICIENT_STOCK`, la préservation
  des métadonnées et une surcharge de feature.

Validations : `pnpm install --frozen-lockfile` OK ; `pnpm format:check` OK ;
`pnpm lint` OK ; `pnpm typecheck` OK ; `pnpm test` OK (6 tests Foundation,
plus les tests Admin/POS) ; builds de production Next.js et Vite OK ; `git
diff --check` OK.

Commit recommandé : `feat(errors): add frontend error contract`.

## Epic F0.11 — Authentication

**Statut : TERMINÉ — cycle de session frontend en mémoire et extensible par transport**

- le package workspace `@zandu/auth` expose un `AuthenticationManager`, un
  `AuthState` explicite (`UNKNOWN`, `AUTHENTICATING`, `AUTHENTICATED`,
  `UNAUTHENTICATED`, `REFRESHING`) et les contrats de transport et d’acteur ;
- les access et refresh tokens sont conservés uniquement en mémoire ; aucun
  adaptateur `localStorage`, `sessionStorage` ou stockage persistant de
  credentials n’est introduit ;
- login et bootstrap résolvent l’acteur avant de rendre l’état authentifié ; un
  access token expiré est renouvelé au bootstrap ;
- la rotation de refresh est single-flight, les opérations explicitement
  compatibles peuvent être reprises via `refreshAndRetry`, et un échec vide la
  session pour revenir au login ;
- logout révoque le refresh token avant le nettoyage local ; le claim JWT
  `authorizationVersion` est comparé à l’acteur résolu pour éliminer une
  session devenue invalide.

Validations : `pnpm install --lockfile-only` OK ; `pnpm format:check` OK ;
`pnpm lint` OK ; `pnpm typecheck` OK ; `pnpm test` OK (7 tests Foundation,
plus les tests Admin/POS) ; builds de production Next.js et Vite OK ; `git
diff --check` OK.

Commit recommandé : `feat(auth): add frontend session lifecycle`.

## Epic F0.12 — OrganizationContext

**Statut : TERMINÉ — sélection explicite issue de la session Symfony**

- `@zandu/organization-context` fournit un état explicite, un manager, un
  provider React et refuse les identifiants inconnus, dupliqués ou inactifs ;
- `GET /api/session` projette atomiquement l'acteur, son organisation courante,
  `authorizationVersion`, les permissions effectives, le scope et les magasins
  accessibles, sans exposer ni recalculer les `RoleAssignment` côté frontend ;
- IdentityAccess obtient les informations d'organisation et de magasins via
  des contrats Application publics d'Organization, dans la transaction tenant ;
- le runtime Admin compose session, OrganizationContext, StoreContext,
  EffectiveAccess, cache TanStack Query et shell avant de rendre les routes
  protégées ; le changement de tenant invalide/isole le cache ;
- la liste backend des magasins est désormais filtrée par le scope effectif,
  de sorte qu'un acteur `SELECTED_STORES` ne reçoit pas les autres magasins.

Validations : 750 tests backend (3 800 assertions), PHPStan sans erreur,
PHP CS Fixer vert, deux analyses Deptrac sans violation, validations frontend
complètes et builds Admin/POS réussis.

## Epic F0.13 — StoreContext

**Statut : TERMINÉ — contexte de store distinct du tenant actif**

- le package workspace `@zandu/store-context` fournit `StoreContextManager`,
  `StoreContextState` et le contrat `AccessibleStore` rattaché explicitement à
  une organisation déjà autorisée ; il ne choisit ni ne persiste
  d’organisation ;
- le contexte distingue `UNKNOWN`, aucun store accessible, aucun store
  sélectionnable, sélection requise et store actif, au lieu de réduire le
  périmètre à un simple `activeStoreId` ;
- les stores actifs accessibles sont les seuls sélectionnables ; les états
  `SUSPENDED`, `CLOSURE_PENDING` et `CLOSED` restent exposés afin que le
  sélecteur puisse les gérer sans fabriquer une nouvelle autorisation côté
  client ;
- une mise à jour du périmètre conserve un store toujours valide, efface un
  store révoqué ou devenu non opérationnel, puis sélectionne automatiquement
  l’unique store valide ou demande une nouvelle sélection ;
- les tests couvrent les cas 0/1/plusieurs stores, les statuts suspendu/fermé,
  la révocation pendant une session active, ainsi que les incohérences de
  tenant et de scope.

Validations : `pnpm install --lockfile-only` OK ; `pnpm format:check` OK ;
`pnpm lint` OK ; `pnpm typecheck` OK ; `pnpm test` OK (8 tests Foundation,
plus les tests Admin/POS) ; builds de production Next.js et Vite OK ; `git
diff --check` OK.

Commit recommandé : `feat(store-context): add accessible store selection`.

## Epic F0.14 — Authorization frontend

**Statut : TERMINÉ — permissions et scopes serveur pour les guards UX**

- le package workspace `@zandu/authorization` expose la projection
  `EffectiveAccess` serveur, sa version d’autorisation, les permissions, le
  scope organisation ou magasins sélectionnés et les magasins accessibles ;
- `can`, `useCan` et `<Can>` vérifient permission, organisation et magasin, et
  refusent par défaut un accès absent ou non résolu ; aucun rôle n’est utilisé
  dans la logique frontend ;
- `canViewNavigation` rend la navigation visible lorsqu’au moins une capacité
  pertinente est autorisée ; ces helpers restent des guards UX, le backend
  conserve explicitement la frontière de sécurité ;
- le provider vérifie les projections de scope incohérentes avant de les
  distribuer aux composants.

Validations : `pnpm install --frozen-lockfile` OK ; `pnpm format:check` OK ;
`pnpm lint` OK ; `pnpm typecheck` OK ; `pnpm test` OK (9 tests Foundation,
plus les tests Admin/POS) ; builds de production Next.js et Vite OK ; `git
diff --check` OK.

Commit recommandé : `feat(authorization): add frontend permission guards`.

## Epic F0.15 — Server state

**Statut : TERMINÉ — cache spécialisé et isolé par tenant/store**

- le package workspace `@zandu/server-state` introduit TanStack Query, un
  `QueryClient` partagé et un provider React, sans copier les entités serveur
  dans un store global maison ;
- `queryKeys` centralise les clés organizations, stores, products, stock,
  suppliers, purchaseOrders et sales ; chaque clé dépendante d’un tenant porte
  `organizationId`, et celles dépendantes d’un magasin portent aussi `storeId` ;
- les lectures utilisent un retry borné, les mutations ne sont jamais rejouées
  automatiquement, et `transitionOrganizationCache` supprime les données des
  autres tenants puis invalide celles du tenant activé ;
- le package ne gère aucun state UI local, qui reste hors du cache serveur.

Validations : `pnpm install --frozen-lockfile` OK ; `pnpm format:check` OK ;
`pnpm lint` OK ; `pnpm typecheck` OK ; `pnpm test` OK (10 tests Foundation,
plus les tests Admin/POS) ; builds de production Next.js et Vite OK ; `git
diff --check` OK.

Commit recommandé : `feat(server-state): add tenant-scoped query cache`.

## Epic F0.16 — Forms

**Statut : TERMINÉ — formulaires standardisés, validation locale et erreurs serveur**

- le package workspace `@zandu/forms` standardise les formulaires React avec
  React Hook Form, des schémas Zod de validation runtime et une validation UX
  au blur puis à la correction ;
- `validateLocalPayload` limite les schémas runtime aux payloads locaux et
  retourne les erreurs par champ, sans reproduire les invariants métier dont le
  backend reste la source d’autorité ;
- `applyServerFieldErrors` raccorde le contrat `FieldErrors` du mapper d’erreurs
  à React Hook Form avec le type `server` ;
- l’état de soumission expose séparément valeurs modifiées, désactivation et
  soumission en cours ; `useUnsavedChangesWarning` protège la fermeture ou le
  rechargement navigateur d’un formulaire modifié.

Validations : `pnpm install --frozen-lockfile` OK ; `pnpm format:check` OK ;
`pnpm lint` OK ; `pnpm typecheck` OK ; `pnpm test` OK (11 tests Foundation,
plus les tests Admin/POS) ; builds de production Next.js et Vite OK ; `git
diff --check` OK.

Commit recommandé : `feat(forms): add validated form foundation`.

## Epic F0.17 — Tables Admin

**Statut : TERMINÉ — tables Admin composables et état URL partageable**

- les modules Admin `tables/query-state` et `tables/AdminTable` séparent l’état
  URL, les colonnes et actions propres à chaque feature, et le rendu d’une page
  reçue du serveur ; aucune DataTable universelle ni filtre client massif n’est
  introduit ;
- `page`, `sort`, `direction`, `search` et les filtres nommés `filter.*` sont
  lus et écrits dans l’URL ; chaque changement de recherche, filtre ou tri
  revient à la première page, ce qui préserve refresh, partage et navigation
  historique ;
- `AdminTable` rend une page `items/page/pageSize/totalItems`, le tri accessible,
  les actions par ligne, la pagination serveur et les états loading, error et
  empty à partir des primitives UI existantes ;
- les tests couvrent le round-trip de l’état URL, les réinitialisations de page
  et les contrats sémantiques de table, tri, actions, pagination et états.

Validations : `pnpm format:check` OK ; `pnpm lint` OK ; `pnpm typecheck` OK ;
`pnpm test` OK (11 tests Foundation, plus 3 tests Admin et les tests POS) ;
builds de production Next.js et Vite OK ; `git diff --check` OK.

Commit recommandé : `feat(admin): add server-paginated table foundation`.

## Epic F0.18 — Routing Admin

**Statut : TERMINÉ — routes Admin par capacités UX**

- les routes Next.js `/login` et `/app` sont matérialisées ;
- `/app` expose séparément les capacités `organization`, `stores`, `members`,
  `catalog`, `pricing`, `inventory`, `purchasing`, `cash` et `sales` ;
- chaque route reste une page placeholder indépendante, sans introduire le shell
  ou la navigation de l’Epic F0.19.

Validations : `pnpm format:check` OK ; `pnpm lint` OK ; `pnpm typecheck` OK ;
`pnpm test` OK (dont le test ciblé des routes Admin) ; build Vite OK ; build
Next.js compilé et pages statiques générées. Dans cet environnement, le
processus Next.js ne termine toutefois pas après cette étape et laisse son
verrou temporaire `.next/lock` ; `git diff --check` OK.

Commit recommandé : `feat(admin): add capability-based routes`.

## Epic F0.19 — Admin Shell

**Statut : TERMINÉ — shell Admin desktop-first et session protégée**

- un shell réutilisable compose navigation filtrée par capacités, sélecteurs
  d’organisation et de magasin, menu acteur/logout, fil d’Ariane et région de
  notifications accessible ;
- les sélecteurs reçoivent les contextes résolus par le runtime et ne décident
  ni des organisations ni des magasins accessibles ;
- la frontière de session conserve les routes protégées masquées pendant le
  bootstrap, le rafraîchissement ou l’absence de session, sans simuler de
  transport HTTP ;
- une frontière `error.tsx` localise les erreurs de routes ; l’intégration
  concrète du runtime de session reste découplée de ce shell.

Validations : `pnpm install --lockfile-only` OK ; `pnpm format:check` OK ;
`pnpm lint` OK ; `pnpm typecheck` OK ; `pnpm test` OK (7 tests Admin, 37 tests
Foundation) ; `git diff --check` OK. Le build Next.js Admin compile et passe
le typage, mais ne termine pas la génération statique dans cet environnement et
laisse son verrou temporaire `.next/lock` ; le verrou est supprimé ensuite.

Commit recommandé : `feat(admin): add desktop application shell`.

## Epic F0.20 — POS Shell

**Statut : TERMINÉ — shell POS sans navigation profonde**

- le shell POS maintient visibles le magasin, la caisse, le caissier et le
  statut de session caisse ;
- les statuts futurs de connectivité et synchronisation sont représentés sans
  simuler une capacité offline ou une synchronisation effective ;
- la zone de vente reste dégagée de sidebar profonde et laisse les futures
  features gérer leurs propres raccourcis clavier et entrées scanner.

Validations : `pnpm install --lockfile-only` OK ; `pnpm format:check` OK ;
`pnpm lint` OK ; `pnpm typecheck` OK ; `pnpm test` OK (7 tests Admin, 3 tests
POS, 37 tests Foundation) ; build Vite POS OK ; `git diff --check` OK.

Commit recommandé : `feat(pos): add operational shell`.

## Epic F0.21 — Notifications

**Statut : TERMINÉ — feedback transitoire et échecs métier différenciés**

- le package partagé `@zandu/notifications` fournit un centre de toasts
  dismissibles, son viewport React et une confirmation générique dont l’impact
  métier est obligatoire ;
- seuls les messages informationnels, de succès ou d’avertissement sont des
  toasts ; les échecs métier critiques restent routés vers une erreur de page,
  et les erreurs non critiques restent inline ;
- les shells Admin et POS montent le provider et leur viewport global, sans
  introduire de logique métier, d’offline ou de mutation.

Validations : `pnpm install --lockfile-only` OK ; `pnpm format:check` OK ;
`pnpm lint` OK ; `pnpm typecheck` OK ; `pnpm test` OK (7 tests Admin, 3 tests
POS, 40 tests Foundation) ; build Vite POS OK ; `git diff --check` OK.

Commit recommandé : `feat(notifications): add shared feedback center`.

## Epic F0.22 — Idempotency-Key frontend

**Statut : TERMINÉ — clé stable par intention de mutation critique**

- le package `@zandu/idempotency` génère et valide des clés compatibles avec
  l’en-tête backend `Idempotency-Key` ;
- le gestionnaire mémorise la clé par intention, marque distinctement une issue
  inconnue après timeout et ne régénère pas de clé au retry de cette intention ;
- les états idle, submitting, unknown outcome, success, business failure et
  technical failure sont matérialisés sans implémenter le transport F0.23.

Validations : `pnpm install --lockfile-only` OK ; `pnpm format:check` OK ;
`pnpm lint` OK ; `pnpm typecheck` OK ; `pnpm test` OK (7 tests Admin, 3 tests
POS, 43 tests Foundation) ; build Vite POS OK ; `git diff --check` OK.

Commit recommandé : `feat(idempotency): add critical mutation key manager`.

## Epic F0.23 — Network abstraction

**Statut : TERMINÉ — transport HTTP partagé et configuration publique validée**

- `@zandu/config` valide exclusivement `API_BASE_URL` et `APP_ENV`, sans
  exposer de secret dans le bundle ;
- `@zandu/api-client` centralise URL, cookies, Bearer token, corrélation,
  idempotence, JSON, timeout, refresh JWT unique et décodage du contrat
  d’erreur ;
- un timeout de commande idempotente est explicitement marqué comme issue
  inconnue, pour permettre le retry F0.22 avec la même clé.

Validations : `pnpm install --lockfile-only` OK ; `pnpm format:check` OK ;
`pnpm lint` OK ; `pnpm typecheck` OK ; `pnpm test` OK (7 tests Admin, 3 tests
POS, 47 tests Foundation) ; build Vite POS OK ; `git diff --check` OK.

Commit recommandé : `feat(api-client): add centralized HTTP transport`.

## Epic F0.24 — Feature organization

**Statut : TERMINÉ — frontières applicatives par feature**

- les routes Admin délèguent chacune à leur feature (`organization`, `stores`,
  `access`, `catalog`, `pricing`, `inventory`, `purchasing`, `cash`, `sales`) ;
- les éléments Admin transverses sont regroupés sous `components/` sans faire
  remonter de logique propre aux features ;
- le POS distingue la composition `app/` de la feature `terminal`, sans
  pré-créer de faux modules pour les workflows non implémentés.

Validations : `pnpm format:check` OK ; `pnpm lint` OK ; `pnpm typecheck` OK ;
`pnpm test` OK (7 tests Admin, 3 tests POS, 49 tests Foundation) ; build Vite
POS OK ; `git diff --check` OK.

Commit recommandé : `refactor(frontend): organize application features`.

## Epic F0.25 — Testing pyramid

**Statut : TERMINÉ — quatre niveaux de tests exécutables et ciblés**

- Vitest distingue explicitement les tests unitaires Node, les tests de
  composants React sous JSDOM et les tests d’intégration ; Testing Library
  vérifie l’interaction accessible avec les composants ;
- MSW intercepte la frontière `fetch` du client API : le scénario d’intégration
  couvre les en-têtes Bearer/corrélation, la projection retournée et la
  conservation des erreurs de champ du backend ;
- Playwright déclare un E2E Chromium indépendant, démarre l’Admin et vérifie
  son entrée publique ; il est découvert par le runner et s’exécute via
  `pnpm test:e2e` dans un environnement Chromium compatible ;
- les scripts ciblés `test:unit`, `test:component`, `test:integration` et
  `test:e2e` sont disponibles ; les trois premiers sont inclus dans `pnpm test`.

Validations : découverte Playwright OK (1 scénario Chromium) ; tests Vitest
unit/component/integration OK (5 tests) ; `pnpm format:check`, `pnpm lint`,
`pnpm typecheck` et `pnpm test` OK. L’exécution locale du navigateur E2E est
impossible sur Ubuntu 26.04, non encore supporté par Playwright 1.57.0 ; la
configuration et le scénario restent vérifiés par découverte.

Commit recommandé : `test(frontend): establish testing pyramid`.

## Epic F0.26 — Observability frontend

**Statut : TERMINÉ — télémétrie client structurée et sûre**

- le package `@zandu/observability` offre un port d’export indépendant du
  fournisseur, aligné sur OpenTelemetry : erreurs techniques, chargements de
  routes et échecs API sont des événements structurés ;
- chaque événement porte version client, environnement et horodatage ; les
  échecs API conservent méthode, route normalisée, statut, type de défaillance,
  issue inconnue et `correlationId` lorsqu’il est disponible ;
- les query strings et identifiants de routes sont supprimés ou normalisés, et
  l’API d’observabilité ne reçoit ni headers, ni corps de requête, ni messages
  d’erreur : tokens, mots de passe et données client ne peuvent donc pas être
  exportés par cette couche ;
- `@zandu/api-client` dépend uniquement du port `ApiFailureObserver` et publie
  les échecs terminaux, y compris après un retry de refresh, sans introduire
  d’exporteur concret ni de second backend.

Validations : tests ciblés Observability/API OK (2 tests) ; typecheck des
packages Observability et API client OK ; les validations frontend globales sont
réexécutées avec succès avant clôture.

Commit recommandé : `feat(observability): add safe frontend telemetry`.

## Epic F0.27 — CI

**Statut : TERMINÉ — validations frontend séparées et reproductibles**

- le workflow GitHub Actions frontend conserve l’installation `pnpm` verrouillée
  et sépare qualité/build web, compilation Tauri et E2E Chromium en jobs
  indépendants avec délais bornés ;
- le job qualité exécute formatage, lint, typecheck, tests unitaires/composants/
  intégration, puis les builds Admin Next.js et POS Vite ;
- le job Tauri installe les prérequis Linux, utilise Rust stable et exécute
  `cargo check --locked`, sans prétendre produire un installateur Windows depuis
  le runner Linux ;
- le job E2E installe Chromium Playwright séparément et exécute `pnpm test:e2e` ;
  un test Foundation protège la présence de tous les contrôles exigés dans le
  workflow.

Validations : test de contrat CI OK ; `pnpm format:check`, `pnpm lint`,
`pnpm typecheck` et `pnpm test` OK (52 tests Foundation, 5 tests Vitest) ;
`cargo check --manifest-path frontend/apps/pos/src-tauri/Cargo.toml --locked`
OK ; format YAML du workflow et `git diff --check` OK. Le build Admin compile
localement, mais Next.js ne termine pas sa génération statique dans cet
environnement ; les builds Admin et POS restent donc deux étapes CI distinctes.

Commit recommandé : `ci(frontend): add full validation pipeline`.
