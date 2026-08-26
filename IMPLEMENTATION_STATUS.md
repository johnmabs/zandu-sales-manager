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
```

## Références

- Spécification d’architecture DDD v1.1
- ADR techniques 0001–0019
- `zandu-lot-0-architecture-executable.md`
- `zandu-lot-1-administration-operationnelle.md`

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
