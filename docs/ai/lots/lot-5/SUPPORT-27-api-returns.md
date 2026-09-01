# 27. API Returns

```text
POST /api/sales/{saleId}/returns
POST /api/returns/{returnId}/lines
POST /api/returns/{returnId}/complete
POST /api/returns/{returnId}/cancel
GET  /api/returns/{returnId}
GET  /api/sales/{saleId}/returns
```

Payload de ligne :

```json
{
  "saleLineId": "...",
  "quantity": "2",
  "restock": true,
  "reason": "Customer return"
}
```

---
