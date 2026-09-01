# 9. Epic 7.3 — ShipStockTransfer

Command :

```text
ShipStockTransfer
├── stockTransferId
├── shippedQuantities[]
└── commandId
```

Les quantités expédiées peuvent être :

```text
0 <= shipped <= requested
```

Une ligne expédiée à zéro reste dans le document mais ne produit aucun mouvement.

## Transaction

```text
BEGIN

load StockTransfer DRAFT
validate source/destination

for each line with shippedQuantity > 0:
    load Stock source
    validate available quantity
    Stock.shipTransfer(...)
    append StockMovement TRANSFER_OUT

    transfer economic value from source valuation
    append valuation movement

freeze shipped quantities
StockTransfer → SHIPPED

append outbox

COMMIT
```

Un échec sur une seule ligne :

```text
ROLLBACK COMPLET
```

## Movement

```text
StockMovement
type = TRANSFER_OUT
source = TRANSFER / StockTransferId
```

Commit :

```text
feat(inventory): ship stock transfer
```

---
