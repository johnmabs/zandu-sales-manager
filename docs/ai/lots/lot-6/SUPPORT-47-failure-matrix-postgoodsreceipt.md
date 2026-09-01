# 47. Failure matrix PostGoodsReceipt

Injecter erreur :

```text
after GoodsReceipt validation
after Stock update line 1
after StockMovement line 1
after StockValuation line 1
after StockValuationMovement line 1
after PurchaseOrder receivedQuantity
after PurchaseOrder status
after GoodsReceipt POSTED
after Audit
after Outbox
before COMMIT
```

Résultat :

```text
ROLLBACK TOTAL
```

Commit :

```text
test(purchasing): verify goods receipt transaction rollback
```

---
