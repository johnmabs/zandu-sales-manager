# 25. Idempotence Purchasing

Les opérations suivantes sont idempotentes :

```text
PostGoodsReceipt
PostGoodsReceiptCorrection
ShipPurchaseReturn
```

Un retry réseau ne double jamais :

```text
stock
valuation
receivedQuantity
purchase return
outbox effect
```

Un même `commandId` avec contenu différent produit :

```text
IdempotencyConflict
```

Contrainte logique :

```text
tenant + store + product + movementType + source
```

---
