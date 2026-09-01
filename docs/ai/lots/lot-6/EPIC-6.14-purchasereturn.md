# 22. Epic 6.14 — PurchaseReturn

```text
PurchaseReturn
├── PurchaseReturnId
├── OrganizationId
├── sourceStoreId
├── supplierId
├── goodsReceiptId?
├── purchaseOrderId?
├── status
├── reason
├── lines[]
├── createdBy
├── createdAt
├── shippedBy?
├── shippedAt?
└── Version
```

Cycle :

```text
DRAFT → SHIPPED
DRAFT → CANCELLED
```

## PurchaseReturnLine

```text
PurchaseReturnLine
├── ProductId
├── quantity
├── goodsReceiptLineId?
└── reference snapshots
```

## Invariants

- source Store même tenant ;
- Supplier cohérent ;
- retour lié à une seule `GoodsReceipt` pour le MVP ;
- quantité <= stock disponible ;
- quantité <= reliquat retournable de la réception ;
- reason obligatoire ;
- `SHIPPED` immutable.

Commit :

```text
feat(purchasing): add purchase return
```

---
