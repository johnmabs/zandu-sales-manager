# 25. Audit & events

Audit sensible :

```text
VALUATION_BOOTSTRAP
RETURN_OVERRIDE
REFUND_OVERRIDE
```

Events possibles :

```text
StockValuationInitialized
StockValuationChanged
SaleReturned
SaleItemReturned
PaymentRefundCreated
PaymentRefundConfirmed
```

Les events passent via l’envelope versionnée et l’outbox.

---
