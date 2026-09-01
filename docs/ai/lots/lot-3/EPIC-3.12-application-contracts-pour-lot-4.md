# 18. Epic 3.12 — Application Contracts pour Lot 4

## Objectif

Préparer les interfaces synchrones dont `Sales` aura besoin sans implémenter Sales.

---

## Étape 3.12.1 — Inventory consumption contract

Préparer :

```text
InventoryStockConsumer
```

Intention future :

```text
consumeStockForSale(...)
```

Entrée possible :

```text
ConsumeStockForSale
├── organizationId
├── storeId
├── saleId
└── items[]
    ├── productId
    └── baseQuantity
```

Le contrat doit être :

- versionnable ;
- idempotent ;
- transaction-compatible ;
- sans aggregate Catalog/Sales.

Au Lot 3, il peut être contract-testé sans être exposé par API commerciale.

### Commit proposé

```text
feat(inventory): expose sale stock consumption contract
```

---

## Étape 3.12.2 — Cash sale movement contract

Préparer :

```text
CashMovementRecorder
```

Intention future :

```text
recordSalePayment(...)
```

Entrée :

```text
organizationId
storeId
cashSessionId
saleId
amount
actorId
```

Résultat idempotent.

Ne pas créer `Payment`.

### Commit proposé

```text
feat(cash): expose sale cash movement contract
```

---

## Étape 3.12.3 — Contract tests

Prouver :

- aucune dépendance Domain croisée ;
- version contractuelle ;
- idempotence ;
- erreurs métier stables ;
- transaction locale compatible avec futur coordinateur Sales.

### Commit proposé

```text
test(contracts): verify inventory and cash application contracts
```

---

## Definition of Done — Epic 3.12

- contrats Lot 4 disponibles ;
- versionnés ;
- contract tests ;
- aucune classe Sales introduite ;
- aucune transaction distribuée supposée.

---
