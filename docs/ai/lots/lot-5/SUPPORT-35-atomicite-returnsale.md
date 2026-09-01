# 35. Atomicité ReturnSale

Injecter des erreurs :

```text
after ReturnSale creation
after Stock restock
after StockMovement SALE_RETURN
after StockValuation update
after StockValuationMovement
after PaymentRefund
after CashMovement REFUND
after ReturnSale completion
after Audit
after Outbox
before COMMIT
```

Tout échec pré-commit :

```text
rollback total
```

Commit :

```text
test(returns): verify return transaction failure matrix
```

---
