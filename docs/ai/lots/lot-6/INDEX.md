# lot-6 — Scoped Codex Index

Original source: `docs/specs/planning/zandu-lot-6-purchasing-goods-receipts.md`. Files below are **lossless top-level splits** of that source; use them to avoid loading the whole Lot.

## Default read set

1. `CONTEXT.md`
2. current Epic only
3. support sections only if the task requires them

## Sections

| Type | Original section | Scoped file | Size |
|---|---|---|---:|
| Epic | 7. Epic 6.1 — Purchasing foundation | `EPIC-6.1-purchasing-foundation.md` | 707 B |
| Epic | 8. Epic 6.2 — Supplier | `EPIC-6.2-supplier.md` | 727 B |
| Epic | 9. Epic 6.3 — PurchasingPolicy | `EPIC-6.3-purchasingpolicy.md` | 578 B |
| Epic | 10. Epic 6.4 — PurchaseOrder | `EPIC-6.4-purchaseorder.md` | 1234 B |
| Epic | 11. Epic 6.5 — Use cases PurchaseOrder | `EPIC-6.5-use-cases-purchaseorder.md` | 571 B |
| Epic | 12. Epic 6.6 — GoodsReceipt | `EPIC-6.6-goodsreceipt.md` | 1038 B |
| Epic | 13. Epic 6.7 — Direct GoodsReceipt | `EPIC-6.7-direct-goodsreceipt.md` | 384 B |
| Epic | 14. Epic 6.8 — PostGoodsReceipt | `EPIC-6.8-postgoodsreceipt.md` | 903 B |
| Epic | 15. Epic 6.9 — Inventory integration | `EPIC-6.9-inventory-integration.md` | 502 B |
| Epic | 16. Epic 6.10 — Costing integration | `EPIC-6.10-costing-integration.md` | 751 B |
| Epic | 17. Epic 6.11 — Partial receipts | `EPIC-6.11-partial-receipts.md` | 368 B |
| Epic | 18. Epic 6.12 — Over receipt | `EPIC-6.12-over-receipt.md` | 504 B |
| Epic | 19. Epic 6.13 — GoodsReceiptCorrection | `EPIC-6.13-goodsreceiptcorrection.md` | 878 B |
| Support | 20. Costing des corrections | `SUPPORT-20-costing-des-corrections.md` | 547 B |
| Support | 21. Atomicité GoodsReceiptCorrection | `SUPPORT-21-atomicite-goodsreceiptcorrection.md` | 428 B |
| Epic | 22. Epic 6.14 — PurchaseReturn | `EPIC-6.14-purchasereturn.md` | 784 B |
| Support | 23. ShipPurchaseReturn | `SUPPORT-23-shippurchasereturn.md` | 549 B |
| Support | 24. Costing PurchaseReturn | `SUPPORT-24-costing-purchasereturn.md` | 318 B |
| Support | 25. Idempotence Purchasing | `SUPPORT-25-idempotence-purchasing.md` | 439 B |
| Support | 26. Concurrence | `SUPPORT-26-concurrence.md` | 521 B |
| Support | 27. Catalog contract pour Purchasing | `SUPPORT-27-catalog-contract-pour-purchasing.md` | 488 B |
| Support | 28. Permissions | `SUPPORT-28-permissions.md` | 533 B |
| Support | 29. Rôles système | `SUPPORT-29-roles-systeme.md` | 253 B |
| Support | 30. Guards opérationnels | `SUPPORT-30-guards-operationnels.md` | 212 B |
| Support | 31. StoreClosure integration | `SUPPORT-31-storeclosure-integration.md` | 375 B |
| Support | 32. Audit | `SUPPORT-32-audit.md` | 212 B |
| Support | 33. Events | `SUPPORT-33-events.md` | 408 B |
| Support | 34. API Supplier | `SUPPORT-34-api-supplier.md` | 231 B |
| Support | 35. API PurchaseOrder | `SUPPORT-35-api-purchaseorder.md` | 391 B |
| Support | 36. API GoodsReceipt | `SUPPORT-36-api-goodsreceipt.md` | 342 B |
| Support | 37. API GoodsReceiptCorrection | `SUPPORT-37-api-goodsreceiptcorrection.md` | 180 B |
| Support | 38. API PurchaseReturn | `SUPPORT-38-api-purchasereturn.md` | 233 B |
| Support | 39. Error contract | `SUPPORT-39-error-contract.md` | 626 B |
| Support | 40. Persistence | `SUPPORT-40-persistence.md` | 521 B |
| Support | 41. Tests PurchaseOrder | `SUPPORT-41-tests-purchaseorder.md` | 347 B |
| Support | 42. Tests GoodsReceipt | `SUPPORT-42-tests-goodsreceipt.md` | 299 B |
| Support | 43. Test réception + Inventory + Costing | `SUPPORT-43-test-reception-inventory-costing.md` | 384 B |
| Support | 44. Tests over receipt | `SUPPORT-44-tests-over-receipt.md` | 295 B |
| Support | 45. Tests GoodsReceiptCorrection | `SUPPORT-45-tests-goodsreceiptcorrection.md` | 298 B |
| Support | 46. Tests PurchaseReturn | `SUPPORT-46-tests-purchasereturn.md` | 313 B |
| Support | 47. Failure matrix PostGoodsReceipt | `SUPPORT-47-failure-matrix-postgoodsreceipt.md` | 474 B |
| Support | 48. Failure matrix Correction / Return | `SUPPORT-48-failure-matrix-correction-return.md` | 265 B |
| Support | 49. Idempotence tests | `SUPPORT-49-idempotence-tests.md` | 341 B |
| Support | 50. Concurrency tests | `SUPPORT-50-concurrency-tests.md` | 535 B |
| Support | 51. Tenant / RLS tests | `SUPPORT-51-tenant-rls-tests.md` | 262 B |
| Support | 52. Observabilité | `SUPPORT-52-observabilite.md` | 310 B |
| Support | 53. Démonstration consolidée Lot 6 | `SUPPORT-53-demonstration-consolidee-lot-6.md` | 1195 B |
| Support | 54. Gate de sortie Lot 6 | `SUPPORT-54-gate-de-sortie-lot-6.md` | 3339 B |
| Support | 55. Hors périmètre | `SUPPORT-55-hors-perimetre.md` | 446 B |
| Support | 56. Transition vers Lot 7 | `SUPPORT-56-transition-vers-lot-7.md` | 438 B |
| Support | 57. Premier point d’entrée d’implémentation | `SUPPORT-57-premier-point-dentree-dimplementation.md` | 948 B |
| Support | 58. Principe de travail | `SUPPORT-58-principe-de-travail.md` | 826 B |

## Token discipline

Do not open every file in this directory. Start with the current Epic and inspect existing code; add support sections only for unresolved requirements.

## Source specification

`docs/specs/planning/zandu-lot-6-purchasing-goods-receipts.md`

Use only when the compact files are insufficient.
