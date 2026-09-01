# 4. Epic 0.1 — Initialisation du repository backend

## Objectif

Obtenir un projet Symfony exécutable, reproductible et prêt à accueillir l’architecture modulaire.

## Étape 0.1.1 — Initialiser Symfony

Créer le projet backend PHP/Symfony sans introduire prématurément les modules métier.

### Résultat attendu

```text
backend/
├── bin/
├── config/
├── public/
├── src/
├── tests/
├── composer.json
└── symfony.lock
```

### Validation

- Symfony démarre ;
- la configuration de test fonctionne ;
- aucune logique métier n’est encore introduite.

### Commit proposé

```text
build(backend): initialize Symfony application
```

---

## Étape 0.1.2 — Définir la structure racine DDD

Créer :

```text
src/
├── Modules/
├── SharedKernel/
└── Platform/
```

Créer également un fichier de documentation ou des placeholders nécessaires pour conserver les dossiers vides.

### Commit proposé

```text
refactor(architecture): introduce modular monolith root structure
```

---

## Étape 0.1.3 — Ajouter les premières structures de module

Créer uniquement les modules requis pour les premiers vertical slices et spikes :

```text
src/Modules/
├── Sales/
├── Inventory/
└── CashManagement/
```

Chaque module :

```text
Module/
├── Domain/
├── Application/
│   └── Contract/
├── Infrastructure/
└── Presentation/
    └── Api/
```

Ne pas créer artificiellement tous les futurs bounded contexts si aucun code ne les utilise encore.

### Commit proposé

```text
refactor(modules): add initial bounded context skeletons
```

---

## Étape 0.1.4 — Configurer les namespaces

Convention :

```text
Zandu\Modules\Sales\...
Zandu\Modules\Inventory\...
Zandu\Modules\CashManagement\...
Zandu\SharedKernel\...
Zandu\Platform\...
```

Vérifier l’autoload PSR-4.

### Commit proposé

```text
build(autoload): configure Zandu namespaces
```

---

## Étape 0.1.5 — Ajouter Docker pour le backend

Créer l’image de développement/production selon une structure compatible avec l’ADR Docker.

La même image backend doit pouvoir exécuter à terme :

```text
API
Worker
Scheduled Job
Migration
```

### Commit proposé

```text
build(docker): add backend container image
```

---

## Étape 0.1.6 — Ajouter PostgreSQL local

Ajouter PostgreSQL à `docker-compose.yml` / `compose.yaml`.

Ne pas ajouter de base alternative SQLite côté serveur.

### Validation

- conteneur PostgreSQL healthy ;
- backend joignable ;
- configuration par variables d’environnement.

### Commit proposé

```text
chore(database): add local PostgreSQL service
```

---

## Étape 0.1.7 — Ajouter le bootstrap développeur

Prévoir une commande simple de bootstrap, par exemple via `Makefile`, `justfile` ou scripts Composer.

Exemples de capacités :

```text
install
start
stop
test
lint
database-create
database-migrate
```

Ne pas sur-construire l’outillage.

### Commit proposé

```text
chore(dev): add local development commands
```

---

## Definition of Done — Epic 0.1

- le repository démarre depuis une machine propre avec les prérequis documentés ;
- Symfony fonctionne ;
- PostgreSQL fonctionne ;
- la structure `Modules / SharedKernel / Platform` existe ;
- les premiers bounded contexts ont leur squelette ;
- l’autoload est valide ;
- un test minimal passe ;
- aucune dépendance métier cross-context n’a été introduite.

---
