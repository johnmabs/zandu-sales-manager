# Zandu Codex Context Pack v2

This pack separates **complete specifications** from **minimal implementation context** so Codex can work with fewer tokens while preserving the original source of truth.

## Structure

```text
AGENTS.md

docs/
├── specs/                    # complete source documents
│   ├── architecture/
│   ├── api/
│   ├── planning/
│   └── spikes/
│
└── ai/                       # compact agent context/routing
    ├── PROJECT_CONTEXT.md
    ├── ARCHITECTURE_RULES.md
    ├── ADR_INDEX.md
    ├── SPEC_ROUTING.md
    ├── LOT_REGISTRY.md
    ├── HOW_TO_ADD_A_LOT.md
    └── lots/
        ├── _TEMPLATE/
        ├── lot-0/
        ├── ...
        └── future lots/

tasks/
└── TASK_TEMPLATE.md

tools/
└── add_lot.py
```

## Core principle

For an implementation request, Codex should normally consume:

```text
AGENTS.md
+ PROJECT_CONTEXT.md
+ ARCHITECTURE_RULES.md (when needed)
+ current Lot INDEX.md
+ current Lot CONTEXT.md
+ current Epic
+ only the relevant Support/ADR files
+ analogous code
```

It should **not** read the whole project specification.

## Future Lots

Future Lots do not require changes to `AGENTS.md`.
See `docs/ai/HOW_TO_ADD_A_LOT.md` or run:

```bash
python tools/add_lot.py 8 "Reporting"
```

Then populate the generated Lot context and Epics from the new source specification.

## Important

The current AI Lot files were derived from the supplied specifications. Keep `docs/specs/` as the full reference and evolve `docs/ai/` as a routing/implementation layer, not as a replacement for product documentation.
