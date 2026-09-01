# 16. Epic 5.9 — ReturnSale foundation

Aggregate proposé :

```text
ReturnSale
├── ReturnSaleId
├── OrganizationId
├── StoreId
├── SaleId
├── status
├── lines
├── reason?
├── createdBy
├── createdAt
├── completedBy?
├── completedAt?
├── BusinessDate
└── Version
```

Statuts :

```text
DRAFT
COMPLETED
CANCELLED
```

Ligne :

```text
ReturnSaleLine
├── ReturnSaleLineId
├── SaleLineId
├── ProductId
├── returnedQuantity
├── baseReturnedQuantity
├── restock
├── reason?
└── originalSnapshots
```

MVP :

```text
restock = true | false
```

Commits :

```text
feat(sales): add return sale aggregate
feat(sales): add return sale line model
```

---
