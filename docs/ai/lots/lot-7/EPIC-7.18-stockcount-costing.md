# 32. Epic 7.18 — StockCount Costing

## Variance négative

```text
counted < expected
```

La sortie :

```text
STOCK_COUNT_CORRECTION_OUT
```

est valorisée au :

```text
currentAverageUnitCost
```

comme sortie économique.

## Variance positive

```text
counted > expected
```

La baseline décide :

```text
utiliser currentAverageUnitCost
```

s’il existe.

Si aucun coût moyen n’existe :

```text
manualUnitCost required
```

avec :

```text
INVENTORY_COST_ASSIGN
+
reason
```

Aucun coût arbitraire.

## Journal

Chaque correction physique valorisée produit :

```text
StockValuationMovement
```

lié à son `StockMovement`.

Commit :

```text
feat(costing): value stock count corrections
```

---
