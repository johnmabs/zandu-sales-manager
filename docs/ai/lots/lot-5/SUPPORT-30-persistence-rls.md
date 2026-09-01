# 30. Persistence & RLS

Tables possibles :

```text
inventory_costing.stock_valuation
inventory_costing.stock_valuation_movement
sales.return_sale
sales.return_sale_line
payments.payment_refund
```

Contraintes :

```text
UNIQUE StockValuation.StockId
UNIQUE StockValuationMovement.StockMovementId
```

Toutes les données tenant-owned utilisent RLS.

---
