# 15. Epic 6.9 — Inventory integration

Contrat :

```text
InventoryGoodsReceiver
```

Entrée :

```text
organizationId
storeId
receiptId
supplierId?
items[]
    productId
    baseQuantity
receivedBy
receivedAt
```

Pour chaque ligne :

```text
Stock.receive(...)
```

Créer :

```text
StockMovement
 type = PURCHASE_RECEIPT
 source = GOODS_RECEIPT / receiptId
```

Idempotence :

```text
PURCHASE_RECEIPT + receiptId + productId
```

Commit :

```text
feat(inventory): receive supplier goods
```

---
