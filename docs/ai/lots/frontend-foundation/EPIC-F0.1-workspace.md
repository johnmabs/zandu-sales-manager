# 8. Epic F0.1 — Workspace

## Objectif

Créer un workspace capable de lancer séparément :

```text
Admin
POS Web dev
POS Tauri
```

## PROPOSÉ

Utiliser un gestionnaire de workspace Node moderne avec lockfile unique.

Le choix exact :

```text
pnpm workspace
```

est recommandé, mais doit être figé par ADR si nécessaire.

Arborescence initiale :

```text
frontend/
├── apps/
│   ├── admin/
│   └── pos/
├── packages/
│   ├── shared/
│   └── config/
└── package.json
```

Validation :

```text
admin starts
pos starts
typecheck works
workspace dependency resolution works
```

Commit proposé :

```text
build(frontend): initialize frontend workspace
```

---
