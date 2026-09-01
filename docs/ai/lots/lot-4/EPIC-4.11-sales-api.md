# 18. Epic 4.11 — Sales API

## Create

```text
POST /api/stores/{storeId}/sales
```

## Read

```text
GET /api/sales/{id}
```

## Lines

```text
POST   /api/sales/{id}/lines
PATCH  /api/sales/{id}/lines/{lineId}
DELETE /api/sales/{id}/lines/{lineId}
```

Le DELETE concerne uniquement une ligne DRAFT.

## Cancel

```text
POST /api/sales/{id}/cancel
```

Pas de :

```text
DELETE /api/sales/{id}
```

## Complete cash sale

```text
POST /api/sales/{id}/complete
```

Headers :

```text
Authorization
X-Correlation-ID
Idempotency-Key
```

Payload :

```json
{
  "cashSessionId": "...",
  "payment": {
    "method": "CASH",
    "amount": {
      "amount": "12500",
      "currency": "XAF"
    }
  },
  "tenderedAmount": {
    "amount": "15000",
    "currency": "XAF"
  }
}
```

Réponse :

```json
{
  "saleId": "...",
  "status": "COMPLETED",
  "total": {
    "amount": "12500",
    "currency": "XAF"
  },
  "paymentId": "...",
  "changeAmount": {
    "amount": "2500",
    "currency": "XAF"
  }
}
```

## Receipt

```text
GET /api/sales/{id}/receipt
```

Le reçu expose les snapshots historiques nécessaires.

## Error codes

```text
SALE_NOT_EDITABLE
SALE_EMPTY
SALE_ALREADY_COMPLETED
SALE_CANCELLED

PRODUCT_NOT_SELLABLE
PACKAGING_NOT_SELLABLE
PRODUCT_PRICE_NOT_FOUND
SALE_PRICING_CHANGED

STOCK_NOT_INITIALIZED
INSUFFICIENT_STOCK

CASH_SESSION_NOT_OPEN
CASH_SESSION_STORE_MISMATCH

PAYMENT_AMOUNT_MISMATCH
PAYMENT_CURRENCY_MISMATCH

IDEMPOTENCY_CONFLICT
```

Commits :

```text
feat(api): expose sale creation
feat(api): expose sale details
feat(api): expose draft sale line editing
feat(api): expose CompleteSale cash
feat(api): expose sale receipt
docs(api): document cash sales workflow
```

---
