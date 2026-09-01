# 14. Epic 6.8 — PostGoodsReceipt

Command :

```text
PostGoodsReceipt
├── goodsReceiptId
└── commandId / idempotency context
```

`organizationId` et `actorId` viennent de `ActorContext`.

## Transaction nominale

```text
BEGIN

validate GoodsReceipt
validate optional PurchaseOrder
validate Supplier / Store / Catalog snapshots

for each line:
    Inventory.receive(...)
    append StockMovement PURCHASE_RECEIPT

    InventoryCosting.valuePurchaseReceipt(...)
    update StockValuation
    append StockValuationMovement

if linked:
    update PurchaseOrderLine.receivedQuantity
    recalculate PurchaseOrder.status

GoodsReceipt.post()

append Audit if required
append Outbox

COMMIT
```

Avec le Lot 5 présent, `StockValuation` et `StockValuationMovement` font partie de la même transaction pour une réception valorisée.

Commit :

```text
feat(purchasing): add post goods receipt workflow
```

---
