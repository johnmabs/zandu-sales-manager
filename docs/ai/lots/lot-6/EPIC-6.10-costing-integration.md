# 16. Epic 6.10 — Costing integration

Une réception fournit :

```text
inventoryUnitCost
```

en base unit.

Calcul :

```text
incomingValue = baseQuantity × inventoryUnitCost
newTotalValue = oldTotalValue + incomingValue
newAverageUnitCost = newTotalValue / newQuantity
```

Créer `StockValuationMovement` lié au `StockMovement PURCHASE_RECEIPT`.

## actualUnitCost vs inventoryUnitCost

`actualUnitCost` représente l’information commerciale de réception.

`inventoryUnitCost` est le coût en base unit transmis au costing.

Si packaging :

```text
inventoryUnitCost = packagingCost / conversionFactor
```

avec calcul Decimal exact.

Aucune taxe/frais n’est soustrait implicitement.

Commit :

```text
feat(costing): value purchase receipt
```

---
