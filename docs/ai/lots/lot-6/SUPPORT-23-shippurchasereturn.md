# 23. ShipPurchaseReturn

Transaction :

```text
BEGIN

validate PurchaseReturn

for each line:
    Inventory.shipPurchaseReturn(...)
    append StockMovement PURCHASE_RETURN

    InventoryCosting.valuePurchaseReturn(...)
    update StockValuation
    append StockValuationMovement

PurchaseReturn.ship()
append Outbox

COMMIT
```

Le mouvement :

```text
PURCHASE_RETURN
 direction = OUT
```

Le `PurchaseReturn` ne décrémente pas :

```text
PurchaseOrderLine.receivedQuantity
```

Commit :

```text
feat(purchasing): ship purchase return
```

---
