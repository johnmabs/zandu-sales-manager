# 11. Epic 7.5 — ReceiveStockTransfer

Command :

```text
ReceiveStockTransfer
├── stockTransferId
├── receivedQuantities[]
└── commandId
```

Préconditions :

```text
status = SHIPPED
```

Pour chaque ligne :

```text
0 <= receivedQuantity <= shippedQuantity
```

Une réception est finale.

## Transaction

```text
BEGIN

load StockTransfer SHIPPED

for each line with receivedQuantity > 0:
    create/load destination Stock
    Stock.receiveTransfer(...)
    append StockMovement TRANSFER_IN

    receive transported value
    update destination StockValuation
    append valuation movement

freeze received quantities
StockTransfer → RECEIVED

record transit discrepancy if any
append outbox

COMMIT
```

Un échec sur une ligne annule toute la réception.

## Mouvement

```text
TRANSFER_IN
```

seulement si :

```text
receivedQuantity > 0
```

Commit :

```text
feat(inventory): receive stock transfer
```

---
