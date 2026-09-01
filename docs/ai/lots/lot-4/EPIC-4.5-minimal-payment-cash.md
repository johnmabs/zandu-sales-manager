# 12. Epic 4.5 — Minimal Payment CASH

## Aggregate Payment

```text
Payment
├── PaymentId
├── OrganizationId
├── purpose = SALE
├── targetReference = SaleId
├── method = CASH
├── status
├── amount
├── currency
├── createdAt
├── confirmedAt?
├── createdBy
└── Version
```

Statuts nécessaires :

```text
CREATED
CONFIRMED
CANCELLED
```

### Invariants

- amount > 0 ;
- devise = Sale.currency ;
- cible même tenant ;
- confirmation unique ;
- paiement confirmé non réécrit rétroactivement.

Events :

```text
PaymentCreated
PaymentConfirmed
```

Commits :

```text
feat(payments): add cash sale payment
feat(payments): persist cash payments
```

### Hors scope Payment

```text
CARD
MOBILE_MONEY
BANK_TRANSFER
PaymentAttempt
PaymentRefund
provider callback
```

---
