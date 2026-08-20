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

Structure cible proposée :

```text
docs/
├── architecture/
│   ├── ddd/
│   │   └── zandu-sales-manager-ddd-v1.1.docx
│   └── adr/
│       └── ...
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

Un service PostgreSQL local a été ajouté à l’orchestration Docker Compose.

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

Le hostname PostgreSQL accessible depuis le réseau Docker est :

```text
postgres
```

Le backend et PostgreSQL partagent le même réseau Docker Compose.

Un healthcheck PostgreSQL basé sur `pg_isready` est configuré.

Le backend dépend de l’état healthy de PostgreSQL avant son démarrage.

Un volume Docker persistant est utilisé pour les données PostgreSQL.

Le port PostgreSQL est exposé localement :

```text
5432:5432
```

Aucune installation Doctrine n’a été introduite à cette étape.

Aucune migration ou table métier n’a été créée.

SQLite n’est pas utilisé comme base serveur.

### Validations exécutées

Démarrage de l’environnement :

```bash
docker compose up -d
```

État observé :

```text
backend    Running
postgres   Healthy
```

Vérification des services :

```bash
docker compose ps
```

Connexion PostgreSQL directe :

```bash
docker compose exec postgres \
  psql -U zandu -d zandu -c "SELECT version();"
```

Résolution DNS depuis le backend :

```bash
docker compose exec backend php -r \
'echo gethostbyname("postgres"), PHP_EOL;'
```

Connexion PostgreSQL réelle via PDO depuis le backend :

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

La connexion a retourné la base :

```text
zandu
```

Les validations Symfony et PHPUnit continuent de fonctionner avec PostgreSQL présent.

### Commit atomique

```text
chore(database): add local PostgreSQL service
```

---

## 0.1.7 — Ajouter le bootstrap développeur

**Statut : TERMINÉ**

### Réalisé

Un `Makefile` a été ajouté à la racine du repository.

Il fournit une interface commune pour les opérations locales de développement.

Commandes actuellement disponibles :

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

Les commandes liées aux migrations et à la création de base applicative ne sont volontairement pas ajoutées à ce stade, Doctrine n’étant pas encore installé.

Elles seront introduites lors de l’Epic consacré à la persistence.

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

Le bootstrap permet :

- de construire l’image backend ;
- d’installer les dépendances Composer ;
- de démarrer et arrêter l’environnement ;
- de lancer PHPUnit ;
- de valider Composer et le container Symfony ;
- d’ouvrir un shell backend ;
- de consulter les logs et l’état des services.

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

**Statut : EN COURS**

Objectif : rendre les frontières DDD exécutables et faire échouer automatiquement l’intégration continue lorsqu’une dépendance architecturale interdite est introduite.

Les règles doivent notamment protéger :

```text
Domain
→ SharedKernel uniquement

Application
→ own Domain
→ SharedKernel
→ public Application Contracts

Infrastructure
→ own Domain
→ own Application
→ SharedKernel
→ Platform

Presentation
→ own Application
```

Les dépendances inter-bounded-context doivent passer par des contrats applicatifs publics.

Exemple autorisé :

```text
Sales
→ Inventory\Application\Contract
```

Exemple interdit :

```text
Sales
→ Inventory\Domain
```

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

Aucune règle architecturale n’a été introduite dans le commit d’installation.

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

Une première configuration Deptrac a été créée pour matérialiser :

```text
Domain
Application
Infrastructure
Presentation
SharedKernel
Platform
```

Les collectors utilisent les namespaces `Zandu\...`.

Une correction des expressions régulières a été nécessaire pendant l’implémentation.

Les motifs fonctionnels utilisent notamment :

```text
.*Zandu\\Modules\\...
```

et non des expressions commençant strictement par :

```text
^Zandu
```

Les antislashs utilisés dans les chaînes PHP des expressions régulières ont également été corrigés afin que les namespaces soient réellement reconnus par Deptrac.

### Validation par violation volontaire

Une dépendance temporaire a été introduite :

```text
Sales\Domain
→ Sales\Infrastructure
```

Deptrac a correctement détecté la violation.

Après suppression du probe temporaire, l’analyse est revenue sans violation.

Cette validation confirme que les collectors et rulesets sont effectivement actifs.

---

## 0.2.3 — Séparer les règles de couches et les règles de modules

**Statut : TERMINÉ**

### Problème détecté

Une première tentative utilisait simultanément des layers techniques :

```text
Application
Domain
Infrastructure
Presentation
```

et des layers de bounded contexts :

```text
Sales
Inventory
CashManagement
```

dans la même configuration.

Une classe comme :

```text
Zandu\Modules\Sales\Application\...
```

appartenait alors simultanément à :

```text
Application
Sales
```

Deptrac produisait notamment :

```text
Warnings 1
```

et une seule dépendance physique pouvait générer plusieurs violations.

### Correction appliquée

Les deux dimensions architecturales ont été séparées :

```text
backend/
├── deptrac.layers.php
└── deptrac.modules.php
```

`deptrac.layers.php` contrôle uniquement les couches techniques.

`deptrac.modules.php` contrôle uniquement les frontières entre bounded contexts.

Cette séparation évite le chevauchement inutile des layers.

### Vue `deptrac.layers.php`

Elle protège les dépendances entre :

```text
Domain
Application
Infrastructure
Presentation
SharedKernel
Platform
```

### Vue `deptrac.modules.php`

Elle protège les frontières entre :

```text
Sales
SalesContract

Inventory
InventoryContract

CashManagement
CashManagementContract
```

Les contracts publics sont exclus du layer interne de leur module avec un collector booléen.

Conceptuellement :

```text
Inventory
=
tout Zandu\Modules\Inventory\...
SAUF
Zandu\Modules\Inventory\Application\Contract\...
```

Ainsi :

```text
Inventory\Application\Contract
```

appartient uniquement au layer :

```text
InventoryContract
```

et non simultanément à :

```text
Inventory
InventoryContract
```

### Commit atomique

```text
build(architecture): separate layer and module rules
```

---

## 0.2.4 — Protéger les frontières inter-bounded-context

**Statut : TERMINÉ**

### Règle validée

Une dépendance directe d’un module vers le domaine interne d’un autre bounded context est interdite.

Cas volontairement testé :

```text
Sales\Application
→ Inventory\Domain
```

Probe utilisé temporairement :

```text
Zandu\Modules\Sales\Application\InvalidCrossContextProbe
→
Zandu\Modules\Inventory\Domain\ArchitectureProbe
```

### Résultat

La vue technique :

```text
deptrac.layers.php
```

considère correctement :

```text
Application → Domain
```

comme une dépendance techniquement autorisée.

La vue modules :

```text
deptrac.modules.php
```

rejette cependant :

```text
Sales → Inventory
```

car le domaine interne d’Inventory n’est pas une API publique.

Le résultat attendu a été obtenu :

```text
Violations  1
Warnings    0
Errors      0
```

### Cas autorisé validé

Le probe a ensuite été remplacé par :

```text
Sales\Application
→ Inventory\Application\Contract
```

Résultat obtenu :

```text
Violations  0
Warnings    0
Errors      0
```

La règle suivante est donc effectivement exécutable :

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

si cette modification a été commitée séparément.

Lorsque cette règle a été introduite dans la même refactorisation que la séparation des configurations, elle reste couverte par :

```text
build(architecture): separate layer and module rules
```

---

## 0.2.5 — Ajouter une commande de validation d’architecture

**Statut : TERMINÉ**

### Réalisé

Le `Makefile` expose désormais :

```bash
make architecture
```

Cette commande exécute successivement :

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

Les validations d’architecture restent séparées de :

```bash
make lint
```

afin de distinguer clairement :

```text
lint
→ validation technique/configuration

architecture
→ fitness tests structuraux
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

Il utilise Docker Compose afin que la CI et l’environnement local reposent sur le même runtime.

Chaîne de validation :

```text
GitHub Actions
    ↓
Docker Compose
    ↓
Backend PHP 8.5
    ↓
Makefile
    ├── make lint
    ├── make test
    └── make architecture
```

Le workflow :

- checkout le repository ;
- construit l’image backend ;
- installe les dépendances Composer ;
- démarre les services ;
- vérifie leur état ;
- lance les validations techniques ;
- lance PHPUnit ;
- lance les deux analyses Deptrac ;
- arrête les services même en cas d’échec.

### Commit atomique

```text
ci(backend): add initial validation workflow
```

---

## Mise à niveau de `actions/checkout`

**Statut : TERMINÉ**

GitHub Actions a signalé que Node.js 20 était déprécié pour :

```text
actions/checkout@v4
```

L’action a été mise à jour vers :

```text
actions/checkout@v5
```

afin d’utiliser le runtime Node.js 24 attendu par les runners GitHub Actions actuels.

### Commit atomique

```text
ci(backend): upgrade checkout action to v5
```

---

## 0.2.7 — Valider l’échec réel de la CI sur violation architecturale

**Statut : TERMINÉ**

### Objectif

Vérifier que les fitness tests ne fonctionnent pas uniquement en local mais qu’ils protègent réellement le repository via GitHub Actions.

### Méthode

Une branche temporaire a été créée :

```text
test/architecture-violation
```

Une violation volontaire a été introduite :

```text
Sales\Application
→ Inventory\Domain
```

Les probes temporaires ont été commités uniquement sur cette branche de validation.

### Résultat attendu et observé

La pipeline GitHub Actions est passée au rouge lors de :

```text
Validate architecture
```

La violation a ensuite été supprimée.

`make architecture` est redevenu vert localement.

Après push de la correction, la pipeline GitHub Actions est redevenue verte.

### Validation de bout en bout

La chaîne suivante est donc confirmée :

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

et :

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

# État actuel de l’Epic 0.2

```text
0.2.1  TERMINÉ  Installation de Deptrac
0.2.2  TERMINÉ  Couches techniques
0.2.3  TERMINÉ  Séparation layers / modules
0.2.4  TERMINÉ  Frontières inter-bounded-context
0.2.5  TERMINÉ  Commande make architecture
0.2.6  TERMINÉ  CI backend GitHub Actions
0.2.7  TERMINÉ  Validation CI rouge → verte
```

---

# Garanties architecturales actuellement exécutables

## Couches techniques

Les règles suivantes sont actuellement matérialisées par Deptrac :

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

Presentation
→ Application
→ SharedKernel

Platform
→ SharedKernel
```

Les dépendances non explicitement autorisées sont rejetées.

---

## Frontières de bounded contexts

Les modules actuellement matérialisés sont :

```text
Sales
Inventory
CashManagement
```

Les contrats applicatifs publics sont séparés :

```text
SalesContract
InventoryContract
CashManagementContract
```

Exemple protégé :

```text
Sales\Application
→ Inventory\Application\Contract
✓
```

Exemple protégé :

```text
Sales\Application
→ Inventory\Domain
✗
```

Les règles cross-context internes ne peuvent donc pas contourner les Application Contracts publics.

---

# Prochaine étape

## 0.2.8 — Interdire les frameworks dans Domain

**Statut : À FAIRE**

Objectif : garantir qu’aucune classe du domaine ne dépend directement d’un framework ou d’un composant d’infrastructure.

Les dépendances suivantes doivent notamment être interdites depuis `Domain` :

```text
Symfony
Doctrine
API Platform
```

Exemples interdits :

```text
Domain
→ Symfony\Component\...

Domain
→ Doctrine\...

Domain
→ ApiPlatform\...
```

Cette règle devra être validée avec le même principe que les précédentes :

```text
1. ajouter la règle ;
2. introduire une violation volontaire ;
3. constater l’échec Deptrac ;
4. supprimer la violation ;
5. constater le retour au vert ;
6. vérifier la CI.
```

Aucune dépendance Doctrine ou API Platform n’étant encore installée, la stratégie exacte du fixture devra éviter d’introduire prématurément ces frameworks uniquement pour le test.

---

# Definition of Done — Epic 0.2

État actuel :

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
[ ] Domain explicitement protégé contre Symfony
[ ] Domain explicitement protégé contre Doctrine
[ ] Domain explicitement protégé contre API Platform
[ ] règles d’architecture documentées dans le repository
```

**Epic 0.2 : EN COURS**

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
