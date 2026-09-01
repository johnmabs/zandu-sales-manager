# 48. Failure matrix Correction / Return

Même discipline pour :

```text
PostGoodsReceiptCorrection
ShipPurchaseReturn
```

Tout échec pré-commit annule :

```text
Purchasing document
Stock
StockMovement
StockValuation
StockValuationMovement
Audit
Outbox
```

---
