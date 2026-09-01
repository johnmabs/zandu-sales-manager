# 19. Epic 5.11 — Return costing

Pour un restock :

```text
restoredUnitCost
=
SaleLineCostSnapshot.unitCost
```

Exemple :

```text
original unit cost = 4 000
current average cost = 4 700
return quantity = 2
restored value = 8 000
```

Puis recalcul du coût moyen.

Créer un `StockValuationMovement` lié au `StockMovement SALE_RETURN`.

Si `restock=false` :

```text
aucun StockValuationMovement
```

Commit :

```text
feat(costing): restore original sale cost on return
```

---
