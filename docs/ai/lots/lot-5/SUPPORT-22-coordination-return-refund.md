# 22. Coordination Return + Refund

Le Lot 5 conserve deux commandes transactionnelles distinctes.

Finalisation du retour :

```text
BEGIN

validate original Sale
validate return quantities
create ReturnSale

if restock:
    Stock.restockFromSaleReturn
    StockMovement SALE_RETURN
    restore original cost
    StockValuationMovement

complete ReturnSale
Outbox

COMMIT
```

Remboursement ultérieur :

```text
BEGIN

validate completed ReturnSale through Sales contract
lock original Payment and cumulative refunds
create PaymentRefund
append CashMovement REFUND
append Audit and Outbox

COMMIT
```

Un échec financier ne réouvre pas le retour. Le client peut rejouer
explicitement le remboursement avec la même clé d’idempotence.

---
