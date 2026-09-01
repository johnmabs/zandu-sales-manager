# lot-5 — Scoped Codex Index

Original source: `docs/planning/zandu-lot-5-inventory-costing-returns.md`. Files below are **lossless top-level splits** of that source; use them to avoid loading the whole Lot.

## Default read set

1. `CONTEXT.md`
2. current Epic only
3. support sections only if the task requires them

## Sections

| Type | Original section | Scoped file | Size |
|---|---|---|---:|
| Epic | 8. Epic 5.1 — Inventory Costing foundation | `EPIC-5.1-inventory-costing-foundation.md` | 455 B |
| Epic | 9. Epic 5.2 — StockValuation | `EPIC-5.2-stockvaluation.md` | 524 B |
| Epic | 10. Epic 5.3 — StockValuationMovement | `EPIC-5.3-stockvaluationmovement.md` | 529 B |
| Epic | 11. Epic 5.4 — Moving weighted average | `EPIC-5.4-moving-weighted-average.md` | 558 B |
| Epic | 12. Epic 5.5 — Costing bootstrap | `EPIC-5.5-costing-bootstrap.md` | 517 B |
| Epic | 13. Epic 5.6 — Intégration vente / costing | `EPIC-5.6-integration-vente-costing.md` | 464 B |
| Epic | 14. Epic 5.7 — SaleLineCostSnapshot | `EPIC-5.7-salelinecostsnapshot.md` | 443 B |
| Epic | 15. Epic 5.8 — Atomicité CompleteSale avec costing | `EPIC-5.8-atomicite-completesale-avec-costing.md` | 531 B |
| Epic | 16. Epic 5.9 — ReturnSale foundation | `EPIC-5.9-returnsale-foundation.md` | 647 B |
| Support | 17. Invariants ReturnSale | `SUPPORT-17-invariants-returnsale.md` | 402 B |
| Epic | 18. Epic 5.10 — Inventory restock | `EPIC-5.10-inventory-restock.md` | 475 B |
| Epic | 19. Epic 5.11 — Return costing | `EPIC-5.11-return-costing.md` | 482 B |
| Epic | 20. Epic 5.12 — Montants de retour | `EPIC-5.12-montants-de-retour.md` | 466 B |
| Epic | 21. Epic 5.13 — Refund cash essentiel | `EPIC-5.13-refund-cash-essentiel.md` | 785 B |
| Support | 22. Coordination Return + Refund | `SUPPORT-22-coordination-return-refund.md` | 739 B |
| Support | 23. Cas sans restock | `SUPPORT-23-cas-sans-restock.md` | 242 B |
| Support | 24. Permissions Lot 5 | `SUPPORT-24-permissions-lot-5.md` | 281 B |
| Support | 25. Audit & events | `SUPPORT-25-audit-events.md` | 323 B |
| Support | 26. API Costing | `SUPPORT-26-api-costing.md` | 359 B |
| Support | 27. API Returns | `SUPPORT-27-api-returns.md` | 368 B |
| Support | 28. API Cash Refund | `SUPPORT-28-api-cash-refund.md` | 368 B |
| Support | 29. Error contract | `SUPPORT-29-error-contract.md` | 422 B |
| Support | 30. Persistence & RLS | `SUPPORT-30-persistence-rls.md` | 356 B |
| Support | 31. Tests Costing | `SUPPORT-31-tests-costing.md` | 352 B |
| Support | 32. Tests ReturnSale | `SUPPORT-32-tests-returnsale.md` | 333 B |
| Support | 33. Test coût original restauré | `SUPPORT-33-test-cout-original-restaure.md` | 401 B |
| Support | 34. Tests Refund | `SUPPORT-34-tests-refund.md` | 290 B |
| Support | 35. Atomicité ReturnSale | `SUPPORT-35-atomicite-returnsale.md` | 449 B |
| Support | 36. Idempotence & concurrence | `SUPPORT-36-idempotence-concurrence.md` | 405 B |
| Support | 37. Tenant isolation | `SUPPORT-37-tenant-isolation.md` | 226 B |
| Support | 38. Démonstration consolidée | `SUPPORT-38-demonstration-consolidee.md` | 696 B |
| Support | 39. Gate de sortie du Lot 5 | `SUPPORT-39-gate-de-sortie-du-lot-5.md` | 2628 B |
| Support | 40. Hors périmètre | `SUPPORT-40-hors-perimetre.md` | 328 B |
| Support | 41. Transition vers le Lot 6 | `SUPPORT-41-transition-vers-le-lot-6.md` | 542 B |
| Support | 42. Premier point d’entrée | `SUPPORT-42-premier-point-dentree.md` | 1343 B |
| Support | 43. Principe de travail | `SUPPORT-43-principe-de-travail.md` | 701 B |

## Token discipline

Do not open every file in this directory. Start with the current Epic and inspect existing code; add support sections only for unresolved requirements.

## Source specification

`docs/specs/planning/zandu-lot-5-inventory-costing-returns.md`

Use only when the compact files are insufficient.
