# 10. Epic 6.4 — PurchaseOrder

## Aggregate

```text
PurchaseOrder
├── PurchaseOrderId
├── OrganizationId
├── destinationStoreId
├── supplierId
├── number
├── status
├── currency
├── lines[]
├── expectedTotal
├── createdBy
├── createdAt
├── confirmedBy?
├── confirmedAt?
├── closedBy?
├── closedAt?
├── closedReason?
└── Version
```

## PurchaseOrderLine

```text
PurchaseOrderLine
├── PurchaseOrderLineId
├── ProductId
├── ProductPackagingId?
├── enteredOrderedQuantity
├── conversionFactorSnapshot
├── orderedBaseQuantity
├── unitCost
├── inventoryUnitCost
└── receivedQuantity
```

## Lifecycle

```text
DRAFT
→ CONFIRMED
→ PARTIALLY_RECEIVED
→ FULLY_RECEIVED
→ CLOSED
```

Avant toute réception :

```text
DRAFT → CANCELLED
CONFIRMED → CANCELLED
```

## Invariants

- une seule devise ;
- supplier figé après confirmation ;
- destination Store figé après confirmation ;
- lignes et coûts figés après confirmation ;
- produit au maximum une fois par commande ;
- `receivedQuantity` est un cumul transactionnel ;
- une commande ayant reçu ne peut plus être annulée.

Commits :

```text
feat(purchasing): add purchase order aggregate
feat(purchasing): persist purchase orders
feat(purchasing): add purchase order lifecycle
```

---
