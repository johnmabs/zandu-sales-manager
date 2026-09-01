# 9. Epic 3.3 — StockMovement ledger

## Objectif

Créer le journal immuable expliquant toute variation de stock.

---

## Étape 3.3.1 — Ajouter `StockMovement`

Modèle :

```text
StockMovement
├── StockMovementId
├── OrganizationId
├── StoreId
├── ProductId
├── StockId
├── StockMovementType type
├── MovementQuantity quantity
├── StockQuantity previousQuantity
├── StockQuantity resultingQuantity
├── StockMovementSource source
├── reason?
├── performedBy?
└── occurredAt
```

### Types nécessaires au Lot 3

```text
INITIAL_STOCK
ADJUSTMENT_IN
ADJUSTMENT_OUT
```

Les enums futurs peuvent exister uniquement si déjà décidés de façon centralisée :

```text
SALE
PURCHASE_RECEIPT
SALE_RETURN
TRANSFER_IN
TRANSFER_OUT
STOCK_COUNT_CORRECTION_IN
STOCK_COUNT_CORRECTION_OUT
```

mais le Lot 3 ne doit pas exposer leurs use cases prématurément.

### Direction

La direction est dérivée du type :

```text
INITIAL_STOCK → IN
ADJUSTMENT_IN → IN
ADJUSTMENT_OUT → OUT
```

Pas de champ `direction` libre permettant :

```text
ADJUSTMENT_OUT + IN
```

### Commit proposé

```text
feat(inventory): add immutable stock movement ledger
```

---

## Étape 3.3.2 — `StockMovementSource`

Value object :

```text
StockMovementSource
├── type
└── referenceId
```

Pour le Lot 3 :

```text
INITIALIZATION
MANUAL_ADJUSTMENT
```

Le futur Lot 4 pourra utiliser :

```text
SALE
```

Le futur Purchasing :

```text
PURCHASE_RECEIPT
```

### Commit proposé

```text
feat(inventory): add stock movement source
```

---

## Étape 3.3.3 — Persistence append-only

Ajouter :

- mapping ;
- table ;
- repository d’écriture append-only ;
- lecture query dédiée si nécessaire ;
- aucune méthode update/delete métier ;
- protections DB utiles ;
- RLS ;
- indexes sur :

```text
organization_id
store_id
product_id
stock_id
occurred_at
source
```

### Commit proposé

```text
feat(inventory): persist immutable stock movements
```

---

## Definition of Done — Epic 3.3

- StockMovement disponible ;
- journal append-only ;
- direction dérivée ;
- source structurée ;
- aucune mutation historique ;
- persistence PostgreSQL ;
- RLS ;
- tests d’immutabilité.

---
