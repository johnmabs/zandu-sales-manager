# 13. Epic 5.6 — Intégration vente / costing

Le workflow `CompleteSale` est enrichi :

```text
Sale
Payment
CashMovement
Stock
StockMovement SALE
StockValuation
StockValuationMovement
SaleLineCostSnapshot
Outbox
```

Pour chaque ligne suivie :

```text
valueOut = baseQuantity × currentAverageUnitCost
```

Contrat applicatif :

```text
InventoryCostingService.valueSaleConsumption(...)
```

Commit :

```text
feat(costing): value sale stock consumption
```

---
