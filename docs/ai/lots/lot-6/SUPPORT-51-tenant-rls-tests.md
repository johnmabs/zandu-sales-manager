# 51. Tenant / RLS tests

```text
Tenant B reads Supplier A
→ NOT_FOUND

Tenant B reads PurchaseOrder A
→ NOT_FOUND

Tenant B posts GoodsReceipt A
→ NOT_FOUND

Store-scoped manager A posts receipt Store B
→ denied
```

RLS sur toutes les tables Purchasing.

---
