# Zandu Sales Manager — Suivi d’implémentation

Ce document suit l’avancement réel de l’implémentation de **Zandu Sales Manager** à partir du **Lot 0 — Architecture exécutable**.

Il ne remplace ni la spécification DDD, ni les ADR, ni le backlog du Lot 0.  
Son rôle est de conserver une trace simple de ce qui a effectivement été réalisé dans le repository.

---

## Références

- Spécification d’architecture DDD v1.1
- ADR techniques 0001–0013
- `zandu-lot-0-architecture-executable.md`

---

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

## Documentation d’architecture

**Statut : EN COURS**

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

La spécification DDD v1.1 et le backlog du Lot 0 doivent encore être intégrés dans `docs/` s’ils ne le sont pas déjà.

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

# État actuel du Lot 0

```text
Epic 0.1   TERMINÉ   Initialisation du repository backend
Epic 0.2   TERMINÉ   Fitness tests d’architecture
Epic 0.3   TERMINÉ   Persistence foundation
Epic 0.4   EN COURS  SharedKernel foundation
Epic 0.5   À FAIRE   API foundation
Epic 0.6   À FAIRE   Authentication foundation
Epic 0.7   À FAIRE   Architectural spikes
Epic 0.8   À FAIRE   Operations & observability
Gate Lot 0 À FAIRE   Validation finale de l’architecture exécutable
```

---

# Prochaine étape

## Epic 0.4 — SharedKernel foundation

**Statut : EN COURS**

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

## Prochaine étape

### 0.4.3 — Ajouter les identifiants métier typés initiaux

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

## Prochaine étape

### 0.4.4 — Ajouter l’abstraction Decimal

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

## Prochaine étape

### 0.4.5 — Implémenter Decimal avec brick/math

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

## Prochaine étape

### 0.4.6 — Ajouter Money et Currency

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

## Prochaine étape

### 0.4.7 — Ajouter Quantity

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

## Prochaine étape

### 0.4.8 — Ajouter les primitives d’exécution nécessaires

**Statut : À FAIRE**

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

---

# Definition of Done globale — Lot 0

État provisoire :

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
[ ] SharedKernel foundation validée
[ ] API foundation validée
[ ] authentication foundation validée
[ ] architectural spikes réalisés
[ ] exploitation et observabilité minimales validées
[ ] documentation finale du Lot 0 à jour
[ ] CI finale entièrement verte
[ ] Gate Lot 0 validé
```

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
