# 41. StoreClosure integration

Le Lot 7 branche les vrais blockers finaux.

## Transfer blocker

Un Store est bloqué si lié à un transfert :

```text
SHIPPED
```

non reçu, côté source ou destination selon policy.

Code possible :

```text
STOCK_TRANSFER_IN_TRANSIT
```

## StockCount blocker

Bloquer si :

```text
OPEN
FINALIZING
```

Code :

```text
OPEN_STOCK_COUNT
```

ou codes distincts.

Avec les blockers Lot 6 + Lot 7, `StoreClosure` peut maintenant revérifier :

```text
CashSession OPEN
Purchasing documents open
StockTransfer in transit
StockCount OPEN / FINALIZING
Stock quantity remaining
```

Commit :

```text
feat(inventory): provide transfer and stock count closure blockers
```

---
