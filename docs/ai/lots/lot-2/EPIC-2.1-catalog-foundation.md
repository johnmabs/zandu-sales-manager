# 6. Epic 2.1 — Catalog foundation

## Objectif

Créer le bounded context `Catalog` et préparer sa persistence sans introduire de logique `Inventory` ou `Sales`.

---

## Étape 2.1.1 — Créer le module Catalog

Créer :

```text
src/Modules/Catalog/
├── Domain/
├── Application/
│   └── Contract/
├── Infrastructure/
└── Presentation/
    └── Api/
```

### Validation

- autoload valide ;
- Deptrac vert ;
- aucun import direct d’un Domain externe ;
- aucun Symfony/Doctrine dans `Catalog\Domain`.

### Commit proposé

```text
refactor(catalog): add bounded context structure
```

---

## Étape 2.1.2 — Ajouter le schéma PostgreSQL `catalog`

Créer le schéma logique :

```text
catalog
```

Ne créer que les tables nécessaires au fur et à mesure des Epics.

Ne pas créer de tables :

```text
stock
stock_movement
sale
payment
cash_session
```

### Commit proposé

```text
feat(database): add catalog schema
```

---

## Étape 2.1.3 — Étendre les architecture fitness tests

Vérifier explicitement :

```text
Catalog\Domain
✗ Organization\Domain
✗ IdentityAccess\Domain
✗ Inventory\Domain
✗ Sales\Domain
✗ CashManagement\Domain
```

Les échanges externes futurs passent par :

```text
Application\Contract
```

### Commit proposé

```text
test(architecture): protect catalog boundaries
```

---

## Definition of Done — Epic 2.1

- module `Catalog` matérialisé ;
- schema `catalog` disponible ;
- architecture tests verts ;
- aucune logique métier d’un lot ultérieur ;
- aucune dépendance cross-domain interdite.

---
