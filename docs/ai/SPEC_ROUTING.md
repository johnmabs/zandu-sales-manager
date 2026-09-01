# Specification routing

This file explains where to look when the compact AI context is insufficient.

## Default rule

Do not browse all source specifications. Start from the current Lot's `INDEX.md`, `CONTEXT.md`, and Epic.

## Source layers

### Full human specifications

`docs/specs/`

These files preserve the complete source documents supplied for the project. They are authoritative for detailed functional intent but expensive as agent context.

### Compact AI routing/context

`docs/ai/`

Use this layer by default for implementation work.

## Routing by need

- Project-wide architecture decision → `docs/ai/ADR_INDEX.md`, then the exact ADR under `docs/specs/architecture/adr/`.
- Architecture boundary/test rule → `docs/ai/ARCHITECTURE_RULES.md`, then `docs/specs/architecture/fitness-tests.md` only if needed.
- Lot business rule → `docs/ai/lots/lot-N/INDEX.md` → relevant Epic/Support file.
- Missing detail from a Lot → the `Source of truth` path declared in that Lot's `CONTEXT.md` or Epic.
- API contract detail → exact file under `docs/specs/api/` when the compact Epic does not contain enough detail.
- Frontend foundation → corresponding compact frontend directory and `docs/specs/planning/zandu-frontend-foundation.md` only when needed.

## Anti-patterns

Do not:

- recursively read `docs/specs/`;
- read every ADR before coding;
- read every previous Lot because its number is lower;
- use the complete DDD document when a targeted context/Epic already answers the question.
