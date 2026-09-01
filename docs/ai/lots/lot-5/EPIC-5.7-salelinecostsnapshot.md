# 14. Epic 5.7 — SaleLineCostSnapshot

```text
SaleLineCostSnapshot
├── SaleLineId
├── StockId
├── StockMovementId
├── quantity
├── unitCost
├── totalCost
├── currency
├── valuationVersion
└── occurredAt
```

Règles :

- immutable après completion ;
- service → aucun coût Inventory fictif ;
- produit non suivi → pas de snapshot Costing ;
- quantité = baseQuantity vendue.

Commit :

```text
feat(sales): add sale line cost snapshot
```

---
