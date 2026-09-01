# 56. Transition vers Lot 7

Après le Lot 6, Zandu sait expliquer les principaux mouvements :

```text
INITIAL_STOCK
PURCHASE_RECEIPT
SALE
SALE_RETURN
PURCHASE_RETURN
ADJUSTMENT_IN
ADJUSTMENT_OUT
GOODS_RECEIPT_CORRECTION_IN
GOODS_RECEIPT_CORRECTION_OUT
```

Le Lot 7 terminera M3 avec :

```text
StockTransfer
+
StockCount
```

et ajoutera :

```text
TRANSFER_OUT
TRANSFER_IN
STOCK_COUNT_CORRECTION_IN
STOCK_COUNT_CORRECTION_OUT
```

---
