# 26. Epic 7.14 — BeginStockCountFinalization

Préconditions :

```text
status = OPEN
```

et :

```text
toutes les StockCountLine
ont countedQuantity != null
```

Transition :

```text
OPEN → FINALIZING
```

Effet :

- saisies figées ;
- aucune nouvelle correction de countedQuantity ;
- scopes restent verrouillés ;
- lancement de réconciliation par batch.

Annulation interdite à partir de :

```text
FINALIZING
```

Commit :

```text
feat(inventory): begin stock count finalization
```

---
