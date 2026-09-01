# 53. Tests reconciliation

Exemple :

```text
expected = 10
counted = 12
variance = +2
```

Résultat :

```text
STOCK_COUNT_CORRECTION_IN = 2
Stock = 12
```

Exemple :

```text
expected = 10
counted = 7
variance = -3
```

Résultat :

```text
STOCK_COUNT_CORRECTION_OUT = 3
Stock = 7
```

Variance zéro :

```text
no StockMovement
line RECONCILED
```

Commit :

```text
test(inventory): verify stock count reconciliation
```

---
