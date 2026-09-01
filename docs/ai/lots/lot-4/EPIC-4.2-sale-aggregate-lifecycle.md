# 9. Epic 4.2 — Sale aggregate & lifecycle

## Aggregate `Sale`

```text
Sale
├── SaleId
├── OrganizationId
├── StoreId
├── status
├── currency
├── customerId?
├── lines
├── subtotal
├── discountTotal
├── taxTotal
├── total
├── createdBy
├── createdAt
├── completedBy?
├── completedAt?
├── cancelledBy?
├── cancelledAt?
├── BusinessDate?
└── Version
```

Statuts :

```text
DRAFT
AWAITING_PAYMENT
COMPLETED
CANCELLED
```

Le vertical slice cash peut utiliser directement :

```text
DRAFT → COMPLETED
```

si le paiement complet est atomique.

### Invariants

- Organization et Store immuables ;
- vente vide non finalisable ;
- currency unique ;
- `COMPLETED` non éditable ;
- `CANCELLED` non finalisable ;
- aucune suppression d’une vente finalisée.

### Events

```text
SaleCreated
SaleUpdated
SaleCancelled
SaleCompleted
```

Commit :

```text
feat(sales): add sale aggregate lifecycle
```

## CreateSale

```text
CreateSale
├── storeId
└── customerId?
```

`organizationId`, `actorId`, `currency` sont résolus côté serveur.

Commit :

```text
feat(sales): add create sale use case
```

## CancelSale

Uniquement avant finalisation.

Commit :

```text
feat(sales): add cancel draft sale
```

### DoD Epic 4.2

- lifecycle complet ;
- persistence PostgreSQL ;
- RLS ;
- aucune suppression métier.

---
