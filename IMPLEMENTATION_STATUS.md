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

# État actuel de l’Epic 0.1

```text
0.1.1  TERMINÉ  Initialisation Symfony
0.1.2  TERMINÉ  Structure racine DDD
0.1.3  TERMINÉ  Premiers bounded contexts
0.1.4  TERMINÉ  Namespaces Zandu
0.1.5  TERMINÉ  Image Docker backend
0.1.6  À FAIRE  PostgreSQL local
0.1.7  À FAIRE  Bootstrap développeur
```

---

# Prochaine étape

## 0.1.6 — Ajouter PostgreSQL local

**Statut : À FAIRE**

Objectifs :

- ajouter un service PostgreSQL local ;
- utiliser des variables d’environnement ;
- configurer un healthcheck PostgreSQL ;
- permettre au backend de joindre le service ;
- ne pas introduire SQLite côté serveur ;
- ne pas encore mélanger cette étape avec l’installation/configuration Doctrine de l’Epic 0.3.

### Commit atomique prévu

```text
chore(database): add local PostgreSQL service
```

---

## 0.1.7 — Ajouter le bootstrap développeur

**Statut : À FAIRE**

Capacités minimales envisagées :

```text
install
start
stop
test
lint
database-create
database-migrate
```

### Commit atomique prévu

```text
chore(dev): add local development commands
```

---

# Definition of Done — Epic 0.1

```text
[x] repository Git initialisé à la racine
[x] Symfony fonctionne
[ ] PostgreSQL fonctionne
[x] structure Modules / SharedKernel / Platform présente
[x] premiers bounded contexts matérialisés
[x] autoload PSR-4 Zandu valide
[x] test minimal passe
[x] image Docker backend construite
[x] runtime PHP aligné sur PHP 8.5
[ ] bootstrap développeur disponible
[x] aucune logique métier cross-context introduite
```

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
