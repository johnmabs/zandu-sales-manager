# 57. Premier point d’entrée d’implémentation

Ordre recommandé :

```text
1. Purchasing foundation
2. Supplier
3. PurchasingPolicy
4. PurchaseOrder
5. GoodsReceipt
6. Catalog purchasing contract
7. Inventory receive contract
8. Costing receipt integration
9. PostGoodsReceipt
10. partial receipts
11. over receipt
12. GoodsReceiptCorrection
13. PurchaseReturn
14. StoreClosure blockers
15. API
16. rollback/idempotence/concurrency
17. Gate
```

Premiers commits :

```text
refactor(purchasing): add bounded context structure
feat(purchasing): add supplier aggregate
feat(purchasing): add purchasing policy
feat(purchasing): add purchase order aggregate
feat(purchasing): add goods receipt aggregate
feat(catalog): expose purchasable product contract
feat(inventory): receive supplier goods
feat(costing): value purchase receipt
feat(purchasing): add post goods receipt workflow
test(purchasing): verify goods receipt transaction rollback
```

---
