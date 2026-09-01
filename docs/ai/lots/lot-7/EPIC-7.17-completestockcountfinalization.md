# 30. Epic 7.17 — CompleteStockCountFinalization

Préconditions :

```text
status = FINALIZING
PENDING lines = 0
```

Transaction :

```text
BEGIN

StockCount → COMPLETED
delete/release OpenStockCountScope
append StockCountCompleted
append Outbox

COMMIT
```

Les lignes restent comme preuve du comptage.

Commit :

```text
feat(inventory): complete stock count finalization
```

---
