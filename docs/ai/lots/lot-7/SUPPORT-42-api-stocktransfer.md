# 42. API StockTransfer

```text
GET  /api/stock-transfers
POST /api/stock-transfers
GET  /api/stock-transfers/{id}

POST   /api/stock-transfers/{id}/lines
PATCH  /api/stock-transfers/{id}/lines/{lineId}
DELETE /api/stock-transfers/{id}/lines/{lineId}

POST /api/stock-transfers/{id}/ship
POST /api/stock-transfers/{id}/receive
POST /api/stock-transfers/{id}/cancel
```

## Create payload

```json
{
  "sourceStoreId": "...",
  "destinationStoreId": "..."
}
```

## Ship payload

```json
{
  "lines": [
    {
      "lineId": "...",
      "shippedQuantity": "10"
    }
  ]
}
```

## Receive payload

```json
{
  "lines": [
    {
      "lineId": "...",
      "receivedQuantity": "8"
    }
  ]
}
```

---
