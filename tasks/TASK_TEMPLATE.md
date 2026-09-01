# TASK-XXX — <short title>

## Target
- Lot: `<lot>`
- Epic: `<epic>`
- Bounded context: `<context>`

## Goal
<one implementation outcome>

## Required context
Read only:
- `docs/ai/PROJECT_CONTEXT.md`
- `docs/ai/ARCHITECTURE_RULES.md`
- `docs/ai/lots/<lot>/INDEX.md`
- `docs/ai/lots/<lot>/CONTEXT.md`
- `docs/ai/lots/<lot>/<epic-file>.md`
- `<support section if needed>`
- `<specific ADR if needed>`

## Existing-code reference
- `<nearest analogous implementation>`

## Allowed scope
- `<paths>`

## Do not modify
- `<unrelated paths/bounded contexts>`

## Acceptance criteria
- [ ] Epic invariants respected
- [ ] relevant unit/integration tests pass
- [ ] tenant/RLS behavior preserved when applicable
- [ ] idempotence/concurrency behavior covered when applicable
- [ ] `make architecture` passes when PHP architecture is affected

## Suggested commit
`type(scope): description`
