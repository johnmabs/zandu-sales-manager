# Zandu — Architecture Rules for Implementation Agents

Source of truth: `docs/architecture/fitness-tests.md` and accepted ADRs. This file is a compact routing summary, not a replacement for them.

## Technical layers

### Domain
Allowed dependency:

```text
Domain -> SharedKernel
```

Forbidden notably:

```text
Domain -> Application / Infrastructure / Presentation / Platform
Domain -> Symfony / Doctrine / ApiPlatform
```

### Application
Allowed:

```text
Application -> Domain
Application -> SharedKernel
```

Forbidden notably:

```text
Application -> Infrastructure / Presentation / Platform
```

### Infrastructure
May implement technical details using Domain, Application, SharedKernel, Platform, Symfony, Doctrine and API Platform as permitted by the executable architecture.

### Presentation
Allowed notably:

```text
Presentation -> Application
Presentation -> SharedKernel
Presentation -> Symfony / ApiPlatform
```

Presentation must not directly access Domain or Doctrine.

### SharedKernel
Only genuinely shared abstractions belong here. Never move code to SharedKernel just to bypass dependency rules.

### Platform
Contains explicitly transversal technical mechanisms. Framework/provider dependencies allowed there do not make them legal in Domain/Application.

## Bounded-context dependencies

A module may expose its public application API through:

```text
Application/Contract/
```

Do not import another bounded context's aggregate, repository or internal application classes to implement a cross-context workflow. Use its public application contract or an explicitly designed event/integration mechanism.

## Validation

Architecture dependencies are executable with Deptrac. Run:

```bash
make architecture
```

The repository uses separate Deptrac views for technical layers and bounded contexts. Passing one view does not replace the other.

## Tenant isolation

Preserve `organizationId` ownership and PostgreSQL RLS rules wherever the schema is tenant-owned. Never weaken RLS merely to make a test or query pass.

## Identifiers

Use UUID v7 through the Zandu abstraction defined by the architecture. Business modules must not bind themselves directly to Symfony UID or another concrete provider.

## Exact arithmetic

Money, quantity, cost and other exact business decimals must use the project's exact-decimal abstractions and PostgreSQL precision rules. Do not introduce float/double arithmetic for business values.

## Transactions / idempotence / concurrency / outbox

These rules are workflow-specific. Before modifying stock, cash, sales, returns, receipts, transfers or counts, read the current Epic plus any support section that explicitly covers transactionality, idempotence or concurrency, and route to ADR-0015/0016 or later specific ADRs when applicable.
