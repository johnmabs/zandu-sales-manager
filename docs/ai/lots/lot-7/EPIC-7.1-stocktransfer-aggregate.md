# 7. Epic 7.1 — StockTransfer aggregate

Modèle :

```text
StockTransfer
├── StockTransferId
├── OrganizationId
├── sourceStoreId
├── destinationStoreId
├── status
├── lines[]
├── createdBy
├── createdAt
├── shippedBy?
├── shippedAt?
├── receivedBy?
├── receivedAt?
├── cancellationReason?
├── cancelledBy?
├── cancelledAt?
└── Version
```

## StockTransferLine

```text
StockTransferLine
├── StockTransferLineId
├── ProductId
├── requestedQuantity
├── shippedQuantity?
├── receivedQuantity?
├── shippedValueSnapshot?
├── shippedUnitCostSnapshot?
└── Version?
```

Les quantités sont en base unit.

Un produit apparaît au maximum une fois par transfert.

## Status

```text
DRAFT
SHIPPED
RECEIVED
CANCELLED
```

Commit :

```text
feat(inventory): add stock transfer aggregate
```

---
