# 18. Epic 7.8 — StockCount aggregate

Modèle :

```text
StockCount
├── StockCountId
├── OrganizationId
├── StoreId
├── status
├── mode
├── scopeType
├── progress counters
├── createdBy
├── createdAt
├── startedBy?
├── startedAt?
├── finalizationStartedBy?
├── finalizationStartedAt?
├── completedBy?
├── completedAt?
├── cancelledBy?
├── cancelledAt?
└── Version
```

## Status

```text
DRAFT
OPEN
FINALIZING
COMPLETED
CANCELLED
```

## Mode

```text
BLIND
GUIDED
```

La baseline propose `BLIND` par défaut.

## Scope type

```text
FULL
PARTIAL
```

Commit :

```text
feat(inventory): add stock count aggregate
```

---
