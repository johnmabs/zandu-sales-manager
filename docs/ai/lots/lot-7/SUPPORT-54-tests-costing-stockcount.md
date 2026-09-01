# 54. Tests Costing StockCount

Correction OUT :

```text
value out at current average
```

Correction IN avec avg existant :

```text
value in at current average
```

Correction IN sans avg :

```text
INVENTORY_COST_REQUIRED
```

Puis avec :

```text
INVENTORY_COST_ASSIGN
manualUnitCost
reason
```

réconciliation autorisée.

Commit :

```text
test(costing): verify stock count correction valuation
```

---
