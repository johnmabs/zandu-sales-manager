# 43. API StockCount

```text
GET  /api/stock-counts
POST /api/stores/{storeId}/stock-counts
GET  /api/stock-counts/{id}

POST /api/stock-counts/{id}/start
POST /api/stock-counts/{id}/counts
POST /api/stock-counts/{id}/counts/batch

POST /api/stock-counts/{id}/finalization
POST /api/stock-counts/{id}/cancel
```

Le traitement interne de reconciliation batch n’a pas besoin d’être une API publique utilisateur.

---
