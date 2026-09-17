# 44. Server-driven pagination

Pour les grandes ressources :

```text
Products
StockMovements
Sales
PurchaseOrders
Audit
```

préférer pagination serveur.

Deux conventions REST sont utilisées selon le volume :

- les référentiels courts utilisent `page` (indexée à partir de 1) et `limit` ;
- les journaux et collections volumineuses utilisent `cursor` (opaque) et
  `limit`. Le curseur de la page suivante est renvoyé dans `X-Next-Cursor` et
  son absence indique la dernière page.

`limit` vaut 30 par défaut et ne peut pas dépasser 100. Products, ProductPrices,
StockMovements, CashMovements, InventoryValuationMovements, PurchaseOrders,
GoodsReceipts, StockTransfers et StockCounts utilisent le curseur. Les futures
collections Sales et Audit devront suivre la même convention.

Ne pas charger 50 000 lignes pour filtrer dans le navigateur.

---
