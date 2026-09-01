# 58. Failure matrix reconciliation batch

Une transaction de batch doit garantir :

```text
Stock
StockMovement
StockValuation
StockValuationMovement
StockCountLine.reconciliationStatus
```

atomiques pour les lignes du batch.

Un crash entre batches est acceptable.

Un crash dans un batch rollback le batch.

---
