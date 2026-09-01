# 10. Epic 5.3 — StockValuationMovement

```text
StockValuationMovement
├── StockValuationMovementId
├── OrganizationId
├── StoreId
├── ProductId
├── StockId
├── StockMovementId
├── type
├── quantity
├── unitCost
├── value
├── previousTotalValue
├── resultingTotalValue
├── previousAverageCost
├── resultingAverageCost
├── source
├── occurredAt
├── correlationId
└── append-only
```

Invariant :

```text
StockMovementId
→ max 1 StockValuationMovement
```

Commit :

```text
feat(costing): add valuation movement ledger
```

---
