# 21. Atomicité GoodsReceiptCorrection

```text
BEGIN

load posted GoodsReceipt
validate correction against effective quantity

for each non-zero difference:
    update Stock
    append correction StockMovement
    update StockValuation
    append StockValuationMovement

update linked PurchaseOrder receivedQuantity/status
post correction
append Audit
append Outbox

COMMIT
```

Toute erreur :

```text
ROLLBACK TOTAL
```

---
