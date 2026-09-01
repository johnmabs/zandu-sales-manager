# 43. Test réception + Inventory + Costing

Exemple :

```text
Stock quantity = 10
Stock value = 40 000
average = 4 000

GoodsReceipt quantity = 10
inventoryUnitCost = 6 000
```

Après post :

```text
Stock = 20
StockMovement PURCHASE_RECEIPT = +10
StockValuation totalValue = 100 000
averageUnitCost = 5 000
StockValuationMovement lié au StockMovement
GoodsReceipt = POSTED
```

---
