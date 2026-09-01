# How to add a new Lot

Adding a Lot must be a local operation. It must not require rewriting `AGENTS.md`, `PROJECT_CONTEXT.md`, or all previous Lot indexes.

## 1. Add the complete source specification

Store the human-oriented specification under:

```text
docs/specs/planning/zandu-lot-<N>-<slug>.md
```

(or the appropriate source directory if the document is not a planning file).

The source specification remains the detailed source of truth.

## 2. Create the compact Lot directory

Copy `docs/ai/lots/_TEMPLATE/` to:

```text
docs/ai/lots/lot-<N>/
```

You may use:

```bash
python tools/add_lot.py <N> "Lot title"
```

## 3. Fill `CONTEXT.md`

Keep it compact. Record only information required across several Epics of this Lot:

- goal;
- bounded context(s);
- aggregates/capabilities introduced;
- dependencies on existing capabilities;
- relevant ADRs;
- global invariants;
- source-spec path.

Do **not** copy the whole source specification.

## 4. Split implementation units

Create one file per implementation Epic:

```text
EPIC-N.1-....md
EPIC-N.2-....md
```

Move cross-cutting sections that are needed only sometimes into targeted support files:

```text
SUPPORT-concurrency.md
SUPPORT-api-contract.md
SUPPORT-permissions.md
SUPPORT-gate.md
```

`INDEX.md` routes Codex to the right files.

## 5. Declare dependencies explicitly

Never encode dependency through Lot ordering.

Good:

```md
## Dependencies
- Inventory: StockMutationPort
- Organization: active Store capability
- ADR-0015: stock concurrency strategy
```

Bad:

```md
Read Lots 0 through 7 first.
```

## What normally does NOT change

Do not update these simply because a Lot was added:

- `AGENTS.md`
- `PROJECT_CONTEXT.md`
- `ARCHITECTURE_RULES.md`
- prior Lot files

Update `PROJECT_CONTEXT.md` only for a project-wide capability/bounded-context change.
Update `ARCHITECTURE_RULES.md` only for a project-wide architecture rule.
Update `ADR_INDEX.md` only when ADR inventory/routing changes.

## Verification checklist

- source spec exists under `docs/specs/`;
- `docs/ai/lots/lot-N/INDEX.md` exists;
- `CONTEXT.md` has explicit dependencies and source path;
- each Epic is independently addressable;
- support files are referenced from the index;
- no instruction says to read every previous Lot;
- no global file was changed without a global reason.
