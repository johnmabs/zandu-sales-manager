# 12. Epic 6.6 — GoodsReceipt

## Aggregate

```text
GoodsReceipt
├── GoodsReceiptId
├── OrganizationId
├── storeId
├── supplierId
├── purchaseOrderId?
├── number
├── status
├── lines[]
├── supplierDeliveryNote?
├── notes?
├── createdBy
├── createdAt
├── postedBy?
├── postedAt?
└── Version
```

## Line

```text
GoodsReceiptLine
├── GoodsReceiptLineId
├── ProductId
├── ProductPackagingId?
├── enteredReceivedQuantity
├── conversionFactorSnapshot
├── receivedBaseQuantity
├── actualUnitCost?
├── inventoryUnitCost
└── purchaseOrderLineId?
```

## Lifecycle

```text
DRAFT → POSTED
DRAFT → CANCELLED
```

`POSTED` est immutable.

## Linked receipt

Si `purchaseOrderId != null` :

- même organization ;
- même supplier ;
- même destination Store ;
- chaque produit appartient à la commande ;
- quantité rapprochée de la ligne correspondante.

Produit supplémentaire :

```text
→ réception directe séparée et justifiée
```

Commits :

```text
feat(purchasing): add goods receipt aggregate
feat(purchasing): persist goods receipts
```

---
