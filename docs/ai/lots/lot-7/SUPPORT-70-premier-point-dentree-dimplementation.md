# 70. Premier point d’entrée d’implémentation

Ordre recommandé :

```text
1. StockTransfer aggregate
2. draft lifecycle
3. ShipStockTransfer
4. Transfer costing
5. ReceiveStockTransfer
6. discrepancy
7. transfer concurrency/idempotence

8. StockCount aggregate
9. StockCountLine
10. Create/Start snapshot
11. OpenStockCountScope
12. record counts
13. Begin FINALIZING
14. reconcile batches
15. costing corrections
16. crash recovery
17. Complete finalization
18. StoreClosure blockers
19. APIs
20. Gate M3
```

Premiers commits :

```text
feat(inventory): add stock transfer aggregate
feat(inventory): add stock transfer draft lifecycle
feat(inventory): ship stock transfer
feat(costing): transfer stock value between stores
feat(inventory): receive stock transfer
test(inventory): verify stock transfer idempotence

feat(inventory): add stock count aggregate
feat(inventory): add stock count line aggregate
feat(inventory): start stock count with snapshot
feat(inventory): lock open stock count scope
feat(inventory): record stock count
feat(inventory): begin stock count finalization
feat(inventory): reconcile stock count batch
feat(costing): value stock count corrections
test(inventory): verify stock count crash recovery
```

---
