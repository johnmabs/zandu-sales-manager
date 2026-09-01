# 28. API Cash Refund

```text
POST /api/payments/{paymentId}/refunds
```

Payload :

```json
{
  "returnSaleId": "...",
  "cashSessionId": "...",
  "amount": {
    "amount": "5000",
    "currency": "XAF"
  },
  "reason": "Returned goods"
}
```

Le header `Idempotency-Key` est obligatoire. Aucune route concurrente sous
`/api/returns` n’est exposée dans le MVP.

---
