# 8. Epic 3.2 — Stock aggregate & persistence

## Objectif

Créer l’autorité opérationnelle sur la quantité courante d’un produit suivi dans un store.

---

## Étape 3.2.1 — Ajouter l’aggregate `Stock`

Modèle :

```text
Stock
├── StockId
├── OrganizationId
├── StoreId
├── ProductId
├── StockQuantity quantityOnHand
├── bool initialized
├── initializedAt?
├── initializedBy?
└── Version
```

L’usage d’un flag `initialized` explicite ou d’un état équivalent doit être cohérent avec la persistence retenue.

Identité métier :

```text
OrganizationId
+
StoreId
+
ProductId
```

### Invariants

```text
quantityOnHand >= 0
```

```text
1 Stock max
par Organization + Store + Product
```

```text
organizationId immutable
storeId immutable
productId immutable
```

Une position non initialisée ne peut pas recevoir silencieusement des opérations nécessitant un stock initial selon la politique retenue.

### Operations Domain

L’aggregate expose des intentions :

```text
initialize(...)
adjust(...)
receive(...)
consumeForSale(...)
restockFromSaleReturn(...)
shipTransfer(...)
receiveTransfer(...)
reconcile(...)
```

Mais le Lot 3 n’expose publiquement que les opérations réellement nécessaires :

```text
initialize(...)
adjust(...)
```

Les autres peuvent être préparées uniquement si nécessaires aux contrats du Lot 4.

Pas de :

```text
setQuantity(...)
increasePublic(...)
decreasePublic(...)
```

### Commit proposé

```text
feat(inventory): add stock aggregate
```

---

## Étape 3.2.2 — Value objects de quantité

Utiliser :

```text
StockQuantity
MovementQuantity
```

Invariants :

```text
StockQuantity >= 0
MovementQuantity > 0
```

Réutiliser les primitives Decimal/Quantity du SharedKernel selon les décisions issues du Spike C.

Aucune précision technique nouvelle ne doit être inventée silencieusement.

### Commit proposé

```text
feat(inventory): add stock quantity value objects
```

---

## Étape 3.2.3 — `StockRepository`

Contrat :

```text
StockRepository
```

Opérations minimales :

```text
save(...)
get(OrganizationId, StoreId, ProductId)
find(OrganizationId, StoreId, ProductId)
```

Éventuellement :

```text
getById(OrganizationId, StockId)
```

si usage réel.

Pas de `GenericRepository`.

### Commit proposé

```text
feat(inventory): add stock repository contract
```

---

## Étape 3.2.4 — Persistence Stock

Ajouter :

- Doctrine mapping ;
- PostgreSQL migration ;
- repository Doctrine/DBAL ;
- version optimiste ou mécanisme retenu par ADR ;
- contrainte unique :

```text
UNIQUE (
  organization_id,
  store_id,
  product_id
)
```

- CHECK éventuel :

```text
quantity_on_hand >= 0
```

- indexes ;
- RLS.

### Validation

- round-trip exact ;
- zéro accepté ;
- négatif impossible ;
- tenant isolation ;
- concurrency primitive fonctionnelle.

### Commit proposé

```text
feat(inventory): persist stock aggregate
```

---

## Definition of Done — Epic 3.2

- aggregate Stock opérationnel ;
- quantité exacte ;
- stock négatif interdit ;
- unicité triplet garantie en base ;
- persistence réelle ;
- version/concurrence préparée ;
- RLS actif ;
- tests verts.

---
