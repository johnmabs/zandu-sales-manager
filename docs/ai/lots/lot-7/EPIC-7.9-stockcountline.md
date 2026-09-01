# 19. Epic 7.9 — StockCountLine

Aggregate séparé :

```text
StockCountLine
├── StockCountLineId
├── StockCountId
├── OrganizationId
├── StoreId
├── ProductId
├── expectedQuantity
├── countedQuantity?
├── countedBy?
├── countedAt?
├── revision
├── reconciliationStatus
└── Version
```

Contrainte :

```text
UNIQUE(stock_count_id, product_id)
```

## Reconciliation status

```text
PENDING
RECONCILED
```

Un modèle plus riche n’est introduit que si réellement nécessaire.

Commit :

```text
feat(inventory): add stock count line aggregate
```

---
