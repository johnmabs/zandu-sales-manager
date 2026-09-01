# AGENTS.md — Zandu Sales Manager

## Purpose

This repository uses a compact AI-context layer so coding agents do **not** need to scan the full functional specification for every task.

The complete human-oriented specifications live under `docs/specs/`.
The compact routing/context layer lives under `docs/ai/`.

## Default reading policy

For any implementation task, use the **smallest sufficient context**.

Read in this order:

1. this `AGENTS.md`;
2. `docs/ai/PROJECT_CONTEXT.md`;
3. `docs/ai/ARCHITECTURE_RULES.md` when architecture boundaries matter;
4. the relevant Lot directory under `docs/ai/lots/`;
5. that Lot's `INDEX.md` and `CONTEXT.md`;
6. only the Epic(s) and Support file(s) relevant to the requested task;
7. only the ADR(s) explicitly referenced by those files or found through `docs/ai/ADR_INDEX.md`;
8. inspect the nearest analogous implementation already present in the codebase.

Do **not** recursively scan `docs/specs/`, all Lots, all ADRs, or unrelated Epics by default.

If a required business rule is still missing after the targeted reads, consult `docs/ai/SPEC_ROUTING.md` and then open only the exact source specification section needed.

## Lot discovery

Lot numbers are not architecture dependencies.

When a task belongs to a Lot:

- find the matching directory under `docs/ai/lots/`;
- read its `INDEX.md` first;
- follow its explicit `Dependencies` and `Relevant ADRs` references;
- never infer that Lot N depends on every Lot `< N`.

Future Lots may be added without changing this file.

## Architecture invariants

- Modular monolith with DDD boundaries.
- Domain code must not depend on Symfony, Doctrine, API Platform, or Infrastructure.
- Application orchestrates use cases and depends on ports/contracts, not concrete infrastructure.
- Infrastructure implements ports and external integrations.
- Presentation/UI calls Application; it must not contain domain rules.
- Cross-context collaboration must use explicit application contracts, ports, or events; never another context's repository directly.
- UUID v7 goes through the project's SharedKernel identifier abstraction.
- Monetary/quantity arithmetic uses exact decimal semantics; never business-critical binary floating point.
- PostgreSQL is the transactional source of truth; tenant isolation/RLS rules must be preserved where applicable.
- Existing architecture fitness tests and Deptrac constraints are authoritative.

## Implementation workflow

1. Read the targeted task/Epic context only.
2. Inspect the nearest existing pattern in code.
3. State assumptions only when unavoidable.
4. Implement the smallest coherent vertical slice.
5. Add/update tests required by the Epic and architecture rules.
6. Run the narrowest relevant tests first, then architecture checks.
7. Do not introduce new patterns or dependencies when an established project pattern already exists.
8. Do not broaden scope to unrelated bounded contexts.

## Documentation changes

When implementation changes an accepted business or architectural decision:

- update the relevant source specification in `docs/specs/` when appropriate;
- update the corresponding compact AI file only if the routing/context itself changed;
- create/update an ADR for a new architecture decision;
- update `docs/ai/ADR_INDEX.md` when a new ADR is added.

Adding a new Lot does **not** require changing this file. Follow `docs/ai/HOW_TO_ADD_A_LOT.md`.
