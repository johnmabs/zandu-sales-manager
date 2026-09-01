# 62. Observabilité

Métriques :

```text
stock_transfer_created_count
stock_transfer_shipped_count
stock_transfer_received_count
stock_transfer_discrepancy_count
stock_transfer_concurrency_conflict_count

stock_count_open_count
stock_count_finalizing_count
stock_count_reconciliation_pending_count
stock_count_variance_count
stock_count_lock_conflict_count
stock_count_reconciliation_failure_count
```

Éviter les ProductId comme labels de métriques.

Utiliser logs/traces pour forte cardinalité.

---
