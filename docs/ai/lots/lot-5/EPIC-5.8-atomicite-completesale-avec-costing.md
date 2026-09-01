# 15. Epic 5.8 — Atomicité CompleteSale avec costing

```text
BEGIN

validate Sale
create/confirm Payment
consume Stock
append StockMovement SALE

update StockValuation
append StockValuationMovement
capture SaleLineCostSnapshot

append CashMovement SALE_PAYMENT
complete Sale
append Outbox

COMMIT
```

Échec Costing :

```text
rollback Sale
rollback Payment
rollback Cash
rollback Stock
rollback StockMovement
rollback Valuation
rollback Outbox
```

Commit :

```text
test(costing): verify CompleteSale costing atomicity
```

---
