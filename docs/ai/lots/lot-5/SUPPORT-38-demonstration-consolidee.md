# 38. Démonstration consolidée

## Costing

```text
Stock = 10
totalValue = 40 000 XAF
averageUnitCost = 4 000 XAF
```

Vente de 2 :

```text
Stock = 8
SaleLineCostSnapshot = 8 000
StockValuation = 32 000
```

## Return

Retour de 1 avec restock :

```text
Stock = 9
StockMovement SALE_RETURN = +1
original cost restored = 4 000
StockValuation = 36 000
```

Retour avec `restock=false` :

```text
no Inventory effect
no Costing effect
```

Refund cash :

```text
PaymentRefund created
CashMovement REFUND created
expected cash decreases
```

Protections :

```text
return > sold → reject
refund > paid → reject
retry → no duplicate
Tenant B → NOT_FOUND
injected failure → rollback total
```

---
