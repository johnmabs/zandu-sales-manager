# lot-3 — Scoped Codex Index

Original source: `docs/planning/zandu-lot-3-inventory-cash-foundations.md`. Files below are **lossless top-level splits** of that source; use them to avoid loading the whole Lot.

## Default read set

1. `CONTEXT.md`
2. current Epic only
3. support sections only if the task requires them

## Sections

| Type | Original section | Scoped file | Size |
|---|---|---|---:|
| Epic | 7. Epic 3.1 — Inventory foundation | `EPIC-3.1-inventory-foundation.md` | 2136 B |
| Epic | 8. Epic 3.2 — Stock aggregate & persistence | `EPIC-3.2-stock-aggregate-persistence.md` | 3101 B |
| Epic | 9. Epic 3.3 — StockMovement ledger | `EPIC-3.3-stockmovement-ledger.md` | 2132 B |
| Epic | 10. Epic 3.4 — Stock initialization & adjustment | `EPIC-3.4-stock-initialization-adjustment.md` | 2942 B |
| Epic | 11. Epic 3.5 — Stock concurrency & idempotence | `EPIC-3.5-stock-concurrency-idempotence.md` | 2024 B |
| Epic | 12. Epic 3.6 — Cash Management foundation | `EPIC-3.6-cash-management-foundation.md` | 905 B |
| Epic | 13. Epic 3.7 — CashRegister | `EPIC-3.7-cashregister.md` | 1616 B |
| Epic | 14. Epic 3.8 — CashSession lifecycle | `EPIC-3.8-cashsession-lifecycle.md` | 4143 B |
| Epic | 15. Epic 3.9 — CashMovement ledger | `EPIC-3.9-cashmovement-ledger.md` | 2815 B |
| Epic | 16. Epic 3.10 — StoreClosure integration | `EPIC-3.10-storeclosure-integration.md` | 1595 B |
| Epic | 17. Epic 3.11 — Authorization, audit & permissions | `EPIC-3.11-authorization-audit-permissions.md` | 2081 B |
| Epic | 18. Epic 3.12 — Application Contracts pour Lot 4 | `EPIC-3.12-application-contracts-pour-lot-4.md` | 1606 B |
| Epic | 19. Epic 3.13 — Inventory & Cash API | `EPIC-3.13-inventory-cash-api.md` | 3611 B |
| Epic | 20. Epic 3.14 — Integration, PostgreSQL & RLS tests | `EPIC-3.14-integration-postgresql-rls-tests.md` | 4395 B |
| Support | 21. Observabilité Lot 3 | `SUPPORT-21-observabilite-lot-3.md` | 597 B |
| Support | 22. Démonstration consolidée du Lot 3 | `SUPPORT-22-demonstration-consolidee-du-lot-3.md` | 1748 B |
| Support | 23. CI minimale du Lot 3 | `SUPPORT-23-ci-minimale-du-lot-3.md` | 720 B |
| Support | 24. Gate de sortie du Lot 3 | `SUPPORT-24-gate-de-sortie-du-lot-3.md` | 3469 B |
| Support | 25. Hors périmètre du Lot 3 | `SUPPORT-25-hors-perimetre-du-lot-3.md` | 665 B |
| Support | 26. Transition vers le Lot 4 | `SUPPORT-26-transition-vers-le-lot-4.md` | 933 B |
| Support | 27. Principe de travail pour l’implémentation | `SUPPORT-27-principe-de-travail-pour-limplementation.md` | 809 B |
| Support | 28. Premier point d’entrée d’implémentation | `SUPPORT-28-premier-point-dentree-dimplementation.md` | 744 B |

## Token discipline

Do not open every file in this directory. Start with the current Epic and inspect existing code; add support sections only for unresolved requirements.

## Source specification

`docs/specs/planning/zandu-lot-3-inventory-cash-foundations.md`

Use only when the compact files are insufficient.
