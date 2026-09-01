# 9. Epic 2.4 — Product lifecycle

## Objectif

Créer le cœur du catalogue : un `Product` autonome pour chaque SKU vendable du MVP.

---

## Étape 2.4.1 — Ajouter l’aggregate `Product`

Modèle :

```text
Product
├── ProductId
├── OrganizationId
├── ProductCode
├── name
├── description?
├── status
├── type
├── baseUnitId
├── inventoryTracked
├── taxCategoryId?
├── categoryId?
├── createdAt
├── createdBy
├── activatedAt?
├── activatedBy?
├── updatedAt?
├── updatedBy?
└── Version
```

Statuts :

```text
DRAFT
ACTIVE
INACTIVE
ARCHIVED
```

Types :

```text
PHYSICAL
SERVICE
```

### Invariants

```text
ProductCode unique par Organization
```

```text
SERVICE
→ inventoryTracked = false
```

```text
ACTIVE
→ baseUnitId immutable
```

```text
ACTIVE
→ ProductCode immutable
```

```text
ARCHIVED
→ historique conservé
→ nouvelles opérations commerciales interdites
```

Un produit `INACTIVE` peut encore être référencé par un workflow historique ou déjà engagé selon les règles du contexte consommateur.

### Domain events

```text
ProductCreated
ProductUpdated
ProductActivated
ProductDeactivated
ProductReactivated
ProductArchived
```

### Commit proposé

```text
feat(catalog): add product aggregate lifecycle
```

---

## Étape 2.4.2 — Ajouter les value objects Product

Créer seulement les concepts qui apportent un invariant réel :

```text
ProductCode
ProductName
```

Éviter les wrappers sans valeur métier.

`ProductCode` :

- normalisé ;
- unique par organization ;
- non vide ;
- immuable après activation.

### Commit proposé

```text
feat(catalog): add product value objects
```

---

## Étape 2.4.3 — Persistence Product

Ajouter :

- mapping Doctrine ;
- repository ;
- migration ;
- contrainte d’unicité `(organization_id, product_code)` ;
- version optimiste ;
- index de lecture ;
- RLS tenant ;
- tests PostgreSQL.

Repository :

```text
ProductRepository

save(...)
get(OrganizationId, ProductId)
find(OrganizationId, ProductId)
findByCode(OrganizationId, ProductCode)
```

Pas de `GenericRepository`.

### Commit proposé

```text
feat(catalog): persist product aggregate
```

---

## Étape 2.4.4 — Use case `CreateProduct`

Command :

```text
CreateProduct
├── productCode
├── name
├── description?
├── type
├── baseUnitId
├── inventoryTracked
├── categoryId?
└── taxCategoryId?
```

`organizationId` et `actorId` viennent de `ActorContext`.

Le produit est créé :

```text
status = DRAFT
```

### Validation applicative

- organization opérationnelle ;
- permission suffisante ;
- base unit existante et autorisée ;
- category éventuelle du même tenant ;
- `SERVICE` interdit `inventoryTracked = true`.

### Commit proposé

```text
feat(catalog): add create product use case
```

---

## Étape 2.4.5 — Update Product

Command :

```text
UpdateProduct
```

Peut modifier uniquement les propriétés autorisées selon l’état.

Éviter :

```text
PATCH
status = ...
```

sans intention métier explicite.

### Commit proposé

```text
feat(catalog): add product profile update
```

---

## Étape 2.4.6 — Activate Product

Command :

```text
ActivateProduct
```

Avant activation :

```text
Product
├── ProductCode valide
├── name valide
├── baseUnitId valide
├── base packaging présent
└── règles type/inventoryTracked valides
```

Le produit ne doit pas être activé dans un état incohérent.

### Commit proposé

```text
feat(catalog): add product activation
```

---

## Étape 2.4.7 — Deactivate / Reactivate / Archive Product

Créer :

```text
DeactivateProduct
ReactivateProduct
ArchiveProduct
```

`ARCHIVED` est terminal dans le workflow normal du MVP.

Aucun delete métier.

### Commit proposé

```text
feat(catalog): add product availability lifecycle
```

---

## Definition of Done — Epic 2.4

- Product aggregate fonctionnel ;
- 1 SKU vendable = 1 Product ;
- aucun ProductVariant ;
- lifecycle testé ;
- ProductCode unique par tenant ;
- ProductCode immutable après activation ;
- baseUnit immutable après activation ;
- SERVICE incompatible avec inventoryTracked ;
- aucun delete métier ;
- persistence PostgreSQL réelle ;
- RLS actif ;
- tests concurrence utiles ;
- domain events disponibles.

---
