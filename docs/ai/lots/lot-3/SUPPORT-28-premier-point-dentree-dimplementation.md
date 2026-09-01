# 28. Premier point d’entrée d’implémentation

Commencer par :

```text
Epic 3.1 — Inventory foundation
```

puis :

```text
Stock
→ StockMovement
→ InitializeStock
→ AdjustStock
→ concurrency/idempotence
```

avant :

```text
CashRegister
→ CashSession
→ CashMovement
```

Ordre recommandé des premiers commits :

```text
refactor(inventory): prepare inventory bounded context
feat(database): add inventory stock tables
feat(catalog): expose inventory product contract
feat(inventory): add stock aggregate
feat(inventory): add immutable stock movement ledger
feat(inventory): add initialize stock use case
feat(inventory): add stock adjustment use case
test(inventory): verify stock concurrency
```

Puis seulement démarrer la verticale Cash.
