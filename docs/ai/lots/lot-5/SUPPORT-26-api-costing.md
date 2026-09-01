# 26. API Costing

```text
GET /api/stores/{storeId}/inventory-valuations
GET /api/stores/{storeId}/inventory-valuations/{productId}
GET /api/stores/{storeId}/inventory-valuations/{productId}/movements
```

Bootstrap si retenu :

```text
POST /api/stores/{storeId}/inventory-valuations/{productId}/initialize
```

Pas de PATCH générique de `totalValue`.

---
