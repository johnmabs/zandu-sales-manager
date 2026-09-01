# Zandu Sales Manager — Compact Project Context

## Product

Zandu Sales Manager is a multi-tenant sales and stock-management system organized around an `Organization` containing multiple stores. The operational source of truth for stock is store-level stock, not an organization-wide quantity.

## Architecture

- DDD modular monolith.
- Backend: PHP / Symfony.
- Transactional database: PostgreSQL.
- Persistence: Doctrine ORM + DBAL according to use case.
- API: REST, API Platform, OpenAPI.
- Authentication: internal JWT for the MVP.
- Identifiers: UUID v7 behind Zandu abstractions.
- Web frontend: Next.js + React + TypeScript.
- POS runtime: React/Vite/Tauri, with SQLite planned for local POS storage.
- Deployment artifact: Docker.
- Observability standard: OpenTelemetry.

## Core bounded contexts already materialized in architecture fitness tests

- Sales
- Inventory
- CashManagement
- Catalog
- Pricing
- IdentityAccess
- Organization
- Operations

Additional contexts are introduced by later planning/specification where applicable (for example Payments and Purchasing).

## Cross-cutting invariants

- Tenant boundaries are mandatory.
- PostgreSQL Row Level Security is part of tenant isolation.
- Business quantities and money use exact decimal arithmetic; no binary floating-point for business calculations.
- Stock concurrency is explicitly controlled.
- Significant workflows must be idempotent where retries can occur.
- Coordinated same-database workflows use local transactions.
- Domain/integration events use transactional outbox where required.
- Audit and event history are separate concerns and must remain traceable.

## Documentation hierarchy

Use this precedence when implementing:

1. accepted ADR governing the exact technical decision;
2. current Lot/Epic and its explicit invariants;
3. DDD baseline specification;
4. older planning text or historical rationale.

If two sources conflict, do not silently choose. Identify the conflict and prefer an explicitly accepted/newer decision when the documentation itself establishes that precedence.

## Current planning material in supplied package

- Lot 0 — Architecture exécutable
- Lot 1 — Administration opérationnelle
- Lot 2 — Catalog & basic Pricing
- Lot 3 — Inventory & Cash foundations
- Lot 4 — Sales & CompleteSale cash
- Lot 5 — Inventory Costing & Returns
- Lot 6 — Purchasing & Goods Receipts
- Lot 7 — StockTransfer & StockCount
- Frontend foundation
