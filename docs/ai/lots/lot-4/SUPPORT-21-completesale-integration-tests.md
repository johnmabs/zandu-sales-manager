# 21. CompleteSale integration tests

## Cas nominal tracked

```text
Stock = 10
Sale quantity = 2
Total = 5 000 XAF
CashSession OPEN
```

Après :

```text
Sale COMPLETED
Payment CONFIRMED
CashMovement +5 000
Stock = 8
StockMovement SALE = 2
Outbox présents
```

## Produit non suivi

```text
Sale COMPLETED
Payment CONFIRMED
CashMovement created
no StockMovement
```

## Service

```text
no Inventory effect
```

## Insufficient stock

```text
Stock = 1
required = 2

→ INSUFFICIENT_STOCK
→ Sale not completed
→ no Payment confirmed
→ no CashMovement
→ Stock unchanged
```

## Failure matrix

Injecter des erreurs :

```text
after Payment creation
after Stock update
after StockMovement
after Payment confirmation
after CashMovement
after Sale.complete
after Audit
after Outbox
before COMMIT
```

Tout échec pré-commit :

```text
ROLLBACK TOTAL
```

Commits :

```text
test(sales): complete tracked cash sale
test(sales): complete untracked product sale
test(sales): complete service cash sale
test(sales): rollback on insufficient stock
test(sales): rollback on cash failure
test(sales): rollback on outbox failure
test(sales): verify CompleteSale failure matrix
```

---
