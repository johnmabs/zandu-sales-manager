# 9. Epic 5.2 — StockValuation

```text
StockValuation
├── StockValuationId?
├── OrganizationId
├── StoreId
├── ProductId
├── StockId
├── quantityOnHand
├── totalValue
├── currency
├── status?
└── Version
```

Invariants :

```text
quantityOnHand >= 0
totalValue >= 0
quantityOnHand = 0 → totalValue = 0
currency = Store.currency
StockValuation unique par StockId
```

Lorsque quantité > 0 :

```text
averageUnitCost = totalValue / quantityOnHand
```

Commit :

```text
feat(costing): add stock valuation aggregate
```

---
