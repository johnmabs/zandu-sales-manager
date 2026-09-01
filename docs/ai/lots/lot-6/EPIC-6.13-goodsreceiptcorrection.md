# 19. Epic 6.13 — GoodsReceiptCorrection

Aggregate :

```text
GoodsReceiptCorrection
├── GoodsReceiptCorrectionId
├── OrganizationId
├── GoodsReceiptId
├── reason
├── status
├── lines[]
├── createdBy
├── createdAt
├── postedBy?
├── postedAt?
└── Version
```

Ligne :

```text
GoodsReceiptCorrectionLine
├── ProductId
├── originalReceivedQuantity
├── currentEffectiveQuantity
├── correctedReceivedQuantity
└── difference
```

Calcul :

```text
difference = correctedReceivedQuantity - currentEffectiveQuantity
```

Types :

```text
difference > 0
→ GOODS_RECEIPT_CORRECTION_IN
```

```text
difference < 0
→ GOODS_RECEIPT_CORRECTION_OUT
```

```text
difference = 0
→ aucun StockMovement
```

Permission :

```text
PURCHASING_RECEIPT_CORRECT
```

Raison obligatoire.

Une correction publiée est immuable.

Commit :

```text
feat(purchasing): add goods receipt correction
```

---
