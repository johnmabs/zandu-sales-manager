# 49. Idempotence tests

Tester :

```text
PostGoodsReceipt same command twice
PostGoodsReceipt network retry after commit
same commandId different payload
PostGoodsReceiptCorrection retry
ShipPurchaseReturn retry
```

Aucun doublon physique ou économique.

Commit :

```text
test(purchasing): verify purchasing command idempotence
```

---
