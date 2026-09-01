# 13. Epic 6.7 — Direct GoodsReceipt

Si :

```text
purchaseOrderRequiredForReceipt = false
```

autoriser :

```text
CreateDirectGoodsReceipt
```

La réception directe doit tout de même contenir :

```text
supplierId
storeId
products
quantities
inventoryUnitCost
```

Elle ne simule pas un PurchaseOrder fictif.

Commit :

```text
feat(purchasing): add direct goods receipt
```

---
