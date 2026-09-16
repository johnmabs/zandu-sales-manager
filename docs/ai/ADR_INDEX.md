# ADR Routing Index

Read **only** the ADR that governs the decision touched by the current task. All listed ADRs are marked `ACCEPTED` in the supplied ADR register.

| File | Decision |
|---|---|
| `docs/architecture/adr/0001-modular-monolith-and-ddd-boundaries.md` | ADR-0001 — Modular Monolith et frontières DDD |
| `docs/architecture/adr/0002-php-symfony-backend.md` | ADR-0002 — PHP et Symfony pour le backend |
| `docs/architecture/adr/0003-postgresql-transactional-database.md` | ADR-0003 — PostgreSQL comme base transactionnelle |
| `docs/architecture/adr/0004-doctrine-persistence-strategy.md` | ADR-0004 — Doctrine ORM + DBAL pour la persistence |
| `docs/architecture/adr/0005-rest-api-platform-openapi.md` | ADR-0005 — REST + API Platform + OpenAPI |
| `docs/architecture/adr/0006-jwt-authentication.md` | ADR-0006 — Authentication JWT interne pour le MVP |
| `docs/architecture/adr/0007-uuid-v7-identifiers.md` | ADR-0007 — UUID v7 et abstraction d’identité |
| `docs/architecture/adr/0008-exact-decimal-arithmetic.md` | ADR-0008 — Arithmétique décimale exacte |
| `docs/architecture/adr/0009-web-frontend.md` | ADR-0009 — Frontend Web |
| `docs/architecture/adr/0010-tauri-pos-runtime.md` | ADR-0010 — Tauri comme runtime POS |
| `docs/architecture/adr/0011-sqlite-pos-local-storage.md` | ADR-0011 — SQLite comme stockage opérationnel du POS |
| `docs/architecture/adr/0012-docker-deployment-artifact.md` | ADR-0012 — Docker comme artifact de déploiement |
| `docs/architecture/adr/0013-opentelemetry-observability.md` | ADR-0013 — OpenTelemetry comme standard d’observabilité |
| `docs/architecture/adr/0014-postgresql-numeric-precision.md` | ADR-0014 — Précision PostgreSQL des décimaux métier |
| `docs/architecture/adr/0015-stock-concurrency-strategy.md` | ADR-0015 — Stratégie de concurrence pour le stock |
| `docs/architecture/adr/0016-transactional-outbox-processing.md` | ADR-0016 — Traitement de la transactional outbox |
| `docs/architecture/adr/0017-postgresql-row-level-security.md` | ADR-0017 — PostgreSQL Row Level Security pour l'isolation tenant |
| `docs/architecture/adr/0018-global-user-and-active-organization.md` | ADR-0018 — Identité globale et organisation active |
| `docs/architecture/adr/0019-tenant-owned-units-of-measure.md` | ADR-0019 — Unités de mesure propres au tenant |
| `docs/architecture/adr/0020-pilot-sales-tax-policy.md` | ADR-0020 — Politique fiscale du pilote Sales |
| `docs/architecture/adr/0021-inventory-costing-activation-policy.md` | ADR-0021 — Politique d’activation de la valorisation Inventory |
| `docs/architecture/adr/0022-cash-refund-ownership-and-workflow.md` | ADR-0022 — Ownership et workflow du remboursement cash |
| `docs/architecture/adr/0023-purchasing-receipt-policy.md` | ADR-0023 — Politique de réception fournisseur du MVP |
| `docs/specs/architecture/adr/0024-pnpm-frontend-workspace.md` | ADR-0024 — pnpm et lockfile unique pour le workspace frontend |
| `docs/specs/architecture/adr/0025-shared-aggregate-versioning.md` | ADR-0025 — Versionnement commun des agrégats mutables |
| `docs/specs/architecture/adr/0026-migration-owned-database-schema.md` | ADR-0026 — Schéma PostgreSQL gouverné par les migrations |

## Open/proposed decisions in supplied register

- deployment platform Render/Frankfurt — proposed;
- observability backend Grafana Cloud — proposed;
- SQLite encryption — open;
- POS key rotation — open;
- offline overselling — open.
