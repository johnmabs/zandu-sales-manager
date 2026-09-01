# lot-7 — Scoped Codex Index

Original source: `docs/planning/zandu-lot-7-stock-transfer-stock-count.md`. Files below are **lossless top-level splits** of that source; use them to avoid loading the whole Lot.

## Default read set

1. `CONTEXT.md`
2. current Epic only
3. support sections only if the task requires them

## Sections

| Type | Original section | Scoped file | Size |
|---|---|---|---:|
| Epic | 7. Epic 7.1 — StockTransfer aggregate | `EPIC-7.1-stocktransfer-aggregate.md` | 785 B |
| Epic | 8. Epic 7.2 — Create / edit transfer | `EPIC-7.2-create-edit-transfer.md` | 669 B |
| Epic | 9. Epic 7.3 — ShipStockTransfer | `EPIC-7.3-shipstocktransfer.md` | 924 B |
| Epic | 10. Epic 7.4 — Stock en transit | `EPIC-7.4-stock-en-transit.md` | 532 B |
| Epic | 11. Epic 7.5 — ReceiveStockTransfer | `EPIC-7.5-receivestocktransfer.md` | 915 B |
| Epic | 12. Epic 7.6 — Transfer discrepancy | `EPIC-7.6-transfer-discrepancy.md` | 527 B |
| Epic | 13. Epic 7.7 — Transfer costing | `EPIC-7.7-transfer-costing.md` | 1185 B |
| Support | 14. Sortie finale source | `SUPPORT-14-sortie-finale-source.md` | 198 B |
| Support | 15. Transfer idempotence | `SUPPORT-15-transfer-idempotence.md` | 382 B |
| Support | 16. Transfer concurrency | `SUPPORT-16-transfer-concurrency.md` | 431 B |
| Support | 17. Store suspension & transfer | `SUPPORT-17-store-suspension-transfer.md` | 483 B |
| Epic | 18. Epic 7.8 — StockCount aggregate | `EPIC-7.8-stockcount-aggregate.md` | 622 B |
| Epic | 19. Epic 7.9 — StockCountLine | `EPIC-7.9-stockcountline.md` | 549 B |
| Epic | 20. Epic 7.10 — CreateStockCount | `EPIC-7.10-createstockcount.md` | 546 B |
| Epic | 21. Epic 7.11 — StartStockCount | `EPIC-7.11-startstockcount.md` | 480 B |
| Epic | 22. Epic 7.12 — OpenStockCountScope | `EPIC-7.12-openstockcountscope.md` | 433 B |
| Support | 23. Mouvement interdit pendant comptage | `SUPPORT-23-mouvement-interdit-pendant-comptage.md` | 546 B |
| Epic | 24. Epic 7.13 — RecordStockCount | `EPIC-7.13-recordstockcount.md` | 550 B |
| Support | 25. Blind vs Guided | `SUPPORT-25-blind-vs-guided.md` | 349 B |
| Epic | 26. Epic 7.14 — BeginStockCountFinalization | `EPIC-7.14-beginstockcountfinalization.md` | 495 B |
| Epic | 27. Epic 7.15 — ReconcileStockCountBatch | `EPIC-7.15-reconcilestockcountbatch.md` | 650 B |
| Support | 28. Conflit de snapshot | `SUPPORT-28-conflit-de-snapshot.md` | 376 B |
| Epic | 29. Epic 7.16 — Crash recovery | `EPIC-7.16-crash-recovery.md` | 553 B |
| Epic | 30. Epic 7.17 — CompleteStockCountFinalization | `EPIC-7.17-completestockcountfinalization.md` | 384 B |
| Support | 31. CancelStockCount | `SUPPORT-31-cancelstockcount.md` | 376 B |
| Epic | 32. Epic 7.18 — StockCount Costing | `EPIC-7.18-stockcount-costing.md` | 703 B |
| Support | 33. Confidentialité des coûts | `SUPPORT-33-confidentialite-des-couts.md` | 428 B |
| Support | 34. Permissions StockTransfer | `SUPPORT-34-permissions-stocktransfer.md` | 257 B |
| Support | 35. Permissions StockCount | `SUPPORT-35-permissions-stockcount.md` | 242 B |
| Support | 36. Rôles système | `SUPPORT-36-roles-systeme.md` | 393 B |
| Support | 37. Scope multi-store | `SUPPORT-37-scope-multi-store.md` | 447 B |
| Support | 38. Audit | `SUPPORT-38-audit.md` | 373 B |
| Support | 39. Events StockTransfer | `SUPPORT-39-events-stocktransfer.md` | 283 B |
| Support | 40. Events StockCount | `SUPPORT-40-events-stockcount.md` | 277 B |
| Support | 41. StoreClosure integration | `SUPPORT-41-storeclosure-integration.md` | 704 B |
| Support | 42. API StockTransfer | `SUPPORT-42-api-stocktransfer.md` | 702 B |
| Support | 43. API StockCount | `SUPPORT-43-api-stockcount.md` | 417 B |
| Support | 44. API lecture BLIND | `SUPPORT-44-api-lecture-blind.md` | 252 B |
| Support | 45. Error contract | `SUPPORT-45-error-contract.md` | 620 B |
| Support | 46. Persistence StockTransfer | `SUPPORT-46-persistence-stocktransfer.md` | 328 B |
| Support | 47. Persistence StockCount | `SUPPORT-47-persistence-stockcount.md` | 401 B |
| Support | 48. Tests Domain StockTransfer | `SUPPORT-48-tests-domain-stocktransfer.md` | 448 B |
| Support | 49. Tests Transfer Inventory | `SUPPORT-49-tests-transfer-inventory.md` | 342 B |
| Support | 50. Tests Transfer Costing | `SUPPORT-50-tests-transfer-costing.md` | 534 B |
| Support | 51. Tests StockCount lifecycle | `SUPPORT-51-tests-stockcount-lifecycle.md` | 439 B |
| Support | 52. Tests lock | `SUPPORT-52-tests-lock.md` | 395 B |
| Support | 53. Tests reconciliation | `SUPPORT-53-tests-reconciliation.md` | 430 B |
| Support | 54. Tests Costing StockCount | `SUPPORT-54-tests-costing-stockcount.md` | 410 B |
| Support | 55. Failure matrix ShipStockTransfer | `SUPPORT-55-failure-matrix-shipstocktransfer.md` | 301 B |
| Support | 56. Failure matrix ReceiveStockTransfer | `SUPPORT-56-failure-matrix-receivestocktransfer.md` | 181 B |
| Support | 57. Failure matrix StockCount start | `SUPPORT-57-failure-matrix-stockcount-start.md` | 216 B |
| Support | 58. Failure matrix reconciliation batch | `SUPPORT-58-failure-matrix-reconciliation-batch.md` | 316 B |
| Support | 59. Crash recovery test | `SUPPORT-59-crash-recovery-test.md` | 282 B |
| Support | 60. Concurrency StockCountLine | `SUPPORT-60-concurrency-stockcountline.md` | 262 B |
| Support | 61. Tenant & scope tests | `SUPPORT-61-tenant-scope-tests.md` | 402 B |
| Support | 62. Observabilité | `SUPPORT-62-observabilite.md` | 503 B |
| Support | 63. Démonstration StockTransfer | `SUPPORT-63-demonstration-stocktransfer.md` | 686 B |
| Support | 64. Démonstration StockCount | `SUPPORT-64-demonstration-stockcount.md` | 943 B |
| Support | 65. Gate de sortie Lot 7 | `SUPPORT-65-gate-de-sortie-lot-7.md` | 3625 B |
| Support | 66. Gate M3 — Gestion complète du stock | `SUPPORT-66-gate-m3-gestion-complete-du-stock.md` | 676 B |
| Support | 67. Ce que M3 permet côté produit | `SUPPORT-67-ce-que-m3-permet-cote-produit.md` | 662 B |
| Support | 68. Hors périmètre Lot 7 | `SUPPORT-68-hors-perimetre-lot-7.md` | 350 B |
| Support | 69. Transition après Lot 7 | `SUPPORT-69-transition-apres-lot-7.md` | 330 B |
| Support | 70. Premier point d’entrée d’implémentation | `SUPPORT-70-premier-point-dentree-dimplementation.md` | 1237 B |
| Support | 71. Principe de travail | `SUPPORT-71-principe-de-travail.md` | 863 B |

## Token discipline

Do not open every file in this directory. Start with the current Epic and inspect existing code; add support sections only for unresolved requirements.

## Source specification

`docs/specs/planning/zandu-lot-7-stock-transfer-stock-count.md`

Use only when the compact files are insufficient.
