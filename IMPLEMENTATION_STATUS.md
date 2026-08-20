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

---

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

Aucune installation Doctrine n’a été introduite.

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

echo $pdo->query("SELECT current_database()")->fetchColumn(), PHP_EOL;
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

Les commandes Doctrine de création/migration de base ne sont pas encore exposées, Doctrine n’étant pas encore installé.

Elles seront ajoutées lors de l’Epic de persistence.

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

ou, si la correction des collectors a nécessité un commit séparé :

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

si cette règle a été commitée séparément.

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

Les règles sont configurées dès maintenant.

Doctrine ORM et API Platform n’étant pas encore installés à cette étape, aucune dépendance n’a été ajoutée artificiellement uniquement pour tester ces deux collectors.

Les interdictions seront revalidées avec des classes réelles lorsque ces frameworks seront introduits dans les Epics concernés.

Cette validation différée ne remet pas en cause la règle actuellement définie dans `deptrac.layers.php`.

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
```

Les dépendances non explicitement autorisées sont rejetées.

---

## Domain indépendant des frameworks

```text
Domain → Symfony       ✗
Domain → Doctrine      ✗
Domain → ApiPlatform   ✗
```

La protection contre Symfony a été validée par une violation réelle.

Les protections Doctrine et API Platform seront rejouées avec des dépendances réelles lors de leur installation.

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

Les règles Doctrine et API Platform sont déjà configurées.

Leur validation à partir de dépendances réelles sera rejouée au moment de l’installation respective de Doctrine et API Platform.

Aucun package n’est installé prématurément uniquement pour satisfaire un fitness test.

---

# État actuel du Lot 0

```text
Epic 0.1  TERMINÉ   Initialisation du repository backend
Epic 0.2  TERMINÉ   Fitness tests d’architecture
Epic 0.3  EN COURS  Persistence foundation
Epic 0.4  À FAIRE   SharedKernel foundation
Epic 0.5  À FAIRE   API foundation
Epic 0.6  À FAIRE   Authentication foundation
Epic 0.7  À FAIRE   Architectural spikes
Epic 0.8  À FAIRE   Operations & observability
Gate Lot 0 À FAIRE  Validation finale de l’architecture exécutable
```

---

# Prochaine étape

## Epic 0.3 — Persistence foundation

**Statut : EN COURS**

Objectif général : introduire la persistence PostgreSQL applicative sans violer les frontières DDD déjà rendues exécutables.

Travaux attendus :

```text
Doctrine ORM
Doctrine Migrations
PostgreSQL
mapping explicite
repositories d’infrastructure
transactions
optimistic locking / versioning
migrations réversibles
tests de persistence
```

Contraintes :

- PostgreSQL reste la base transactionnelle serveur ;
- SQLite n’est pas introduit côté serveur ;
- Doctrine appartient à l’infrastructure ;
- `Domain` ne dépend jamais de Doctrine ;
- les mappings Doctrine doivent respecter la séparation des bounded contexts ;
- aucune dépendance cross-context directe ne doit être introduite par les repositories ;
- les règles Deptrac existantes restent vertes ;
- `Domain → Doctrine` devra être revalidé avec une dépendance réelle après installation de Doctrine.

## 0.3.1 — Installer Doctrine ORM, DBAL et Migrations

**Statut : TERMINÉ**

### Réalisé

Les composants `doctrine/doctrine-bundle`,
`doctrine/doctrine-migrations-bundle` et `doctrine/orm` sont installés et
chargés par Symfony. Doctrine DBAL utilise la variable d’environnement
`DATABASE_URL`.

Le cache Symfony `prod`, compilé avant l’installation de Doctrine, a été
reconstruit. Les commandes `dbal:*`, `doctrine:*` et
`doctrine:migrations:*` sont désormais enregistrées.

La fixture générée utilise maintenant le namespace applicatif attendu :

```text
Zandu\DataFixtures\AppFixtures
```

### Validations exécutées

```bash
composer validate --no-check-publish
php bin/console lint:container
php bin/phpunit
vendor/bin/deptrac analyse --config-file=deptrac.layers.php --no-cache
vendor/bin/deptrac analyse --config-file=deptrac.modules.php --no-cache
```

Résultats :

```text
Composer valide
Conteneur Symfony valide
PHPUnit : OK (1 test, 1 assertion)
Deptrac layers : 0 violation, 0 warning, 0 erreur
Deptrac modules : 0 violation, 0 warning, 0 erreur
```

## 0.3.2 — Valider la connexion PostgreSQL et les migrations

**Statut : TERMINÉ POUR LA CONFIGURATION — SCHÉMA INITIAL À FAIRE**

### Réalisé

La connexion a été testée depuis le conteneur backend au travers de Doctrine
DBAL :

```bash
php bin/console dbal:run-sql \
    'SELECT current_database() AS database_name, 1 AS connection_ok'
```

Résultat :

```text
database_name = zandu
connection_ok = 1
```

Le sous-système Doctrine Migrations est opérationnel. La commande
`doctrine:migrations:status` identifie la base `zandu` et le répertoire
`migrations/`. Aucune migration n’est encore disponible ou exécutée.

La même suite PHPUnit a été exécutée dans Docker avec PHP 8.5.9 :

```text
OK (1 test, 1 assertion)
```

### Validation minimale attendue

```text
[x] Doctrine installé et configuré
[x] connexion Doctrine → PostgreSQL fonctionnelle
[x] Doctrine absent du Domain
[x] migrations configurées
[ ] migration initiale exécutable
[ ] rollback validé
[ ] stratégie de transaction testée
[x] make architecture reste vert
[x] make test reste vert
[x] make lint reste vert
[ ] CI reste verte
```

Les sous-étapes et commits atomiques seront définis au démarrage de l’Epic.

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
[ ] persistence foundation validée
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
