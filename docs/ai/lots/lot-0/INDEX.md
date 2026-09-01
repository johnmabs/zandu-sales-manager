# lot-0 — Scoped Codex Index

Original source: `docs/planning/zandu-lot-0-architecture-executable.md`. Files below are **lossless top-level splits** of that source; use them to avoid loading the whole Lot.

## Default read set

1. `CONTEXT.md`
2. current Epic only
3. support sections only if the task requires them

## Sections

| Type | Original section | Scoped file | Size |
|---|---|---|---:|
| Epic | 4. Epic 0.1 — Initialisation du repository backend | `EPIC-0.1-initialisation-du-repository-backend.md` | 3286 B |
| Epic | 5. Epic 0.2 — Architecture fitness tests | `EPIC-0.2-architecture-fitness-tests.md` | 1445 B |
| Epic | 6. Epic 0.3 — Persistence foundation | `EPIC-0.3-persistence-foundation.md` | 2005 B |
| Epic | 7. Epic 0.4 — SharedKernel foundation | `EPIC-0.4-sharedkernel-foundation.md` | 2374 B |
| Epic | 8. Epic 0.5 — API foundation | `EPIC-0.5-api-foundation.md` | 1623 B |
| Epic | 9. Epic 0.6 — Authentication foundation | `EPIC-0.6-authentication-foundation.md` | 1517 B |
| Epic | 10. Epic 0.7 — Spikes architecturaux | `EPIC-0.7-spikes-architecturaux.md` | 2607 B |
| Epic | 11. Epic 0.8 — Operations & observability | `EPIC-0.8-operations-observability.md` | 1672 B |
| Support | 12. CI minimale du Lot 0 | `SUPPORT-12-ci-minimale-du-lot-0.md` | 603 B |
| Support | 13. Gate de sortie du Lot 0 | `SUPPORT-13-gate-de-sortie-du-lot-0.md` | 982 B |
| Support | 14. Principe de travail pour la suite | `SUPPORT-14-principe-de-travail-pour-la-suite.md` | 532 B |

## Token discipline

Do not open every file in this directory. Start with the current Epic and inspect existing code; add support sections only for unresolved requirements.

## Source specification

`docs/specs/planning/zandu-lot-0-architecture-executable.md`

Use only when the compact files are insufficient.
