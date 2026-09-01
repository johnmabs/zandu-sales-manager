# 27. Epic 7.15 — ReconcileStockCountBatch

Traitement interne :

```text
ReconcileStockCountBatch
```

Pour chaque ligne :

```text
reconciliationStatus = PENDING
```

faire :

```text
1. verify current Stock.quantityOnHand == expectedQuantity

2. variance =
   countedQuantity - expectedQuantity

3. Stock.reconcile(...)

4. if variance > 0:
       STOCK_COUNT_CORRECTION_IN

5. if variance < 0:
       STOCK_COUNT_CORRECTION_OUT

6. if variance = 0:
       no StockMovement

7. update Costing if movement exists

8. line → RECONCILED
```

Le batch doit être transactionnel.

Commit :

```text
feat(inventory): reconcile stock count batch
```

---
