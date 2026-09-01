# 41. Transition vers le Lot 6

Après Lot 5, Zandu sait :

```text
vendre
valoriser la sortie vendue
conserver le coût historique
retourner partiellement ou totalement
remettre explicitement en stock
restaurer le coût original
rembourser en cash
```

Le Lot 6 introduira :

```text
Purchasing
├── Supplier
├── PurchaseOrder
├── GoodsReceipt
├── direct receipt selon policy
├── inventoryUnitCost
└── GoodsReceiptCorrection
```

et alimentera :

```text
Stock
+
StockMovement PURCHASE_RECEIPT
+
StockValuation
+
StockValuationMovement
```

---
