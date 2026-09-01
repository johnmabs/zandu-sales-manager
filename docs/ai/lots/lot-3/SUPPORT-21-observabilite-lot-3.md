# 21. Observabilité Lot 3

Ajouter des métriques seulement si utiles opérationnellement.

Exemples :

```text
inventory_adjustment_count
inventory_concurrency_conflict_count
inventory_insufficient_stock_count

cash_open_session_count
cash_session_close_count
cash_session_discrepancy_count
cash_movement_count
```

Ne pas exposer :

- amounts sensibles dans labels ;
- product IDs haute cardinalité sans justification ;
- tenant IDs bruts en métriques publiques.

Les logs structurés portent :

```text
correlationId
operation
result
duration
```

sans transformer les logs en ledger métier.

---
