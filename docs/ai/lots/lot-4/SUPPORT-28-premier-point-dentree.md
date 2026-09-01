# 28. Premier point d’entrée

Ordre recommandé :

```text
Sales foundation
→ Sale
→ SaleLine snapshots
→ Pricing calculator
→ Payment CASH
→ Inventory integration
→ Cash integration
→ CompleteSale
→ rollback tests
→ idempotence
→ concurrency
→ API
→ Gate M2
```

Premiers commits :

```text
refactor(sales): prepare sales bounded context
feat(database): add sale persistence tables
feat(sales): add sale aggregate lifecycle
feat(catalog): expose sellable product contract
feat(sales): add sale line snapshots
feat(sales): add deterministic sale pricing
feat(payments): add cash sale payment
feat(inventory): consume stock for cash sale
feat(cash): record cash sale payment movement
feat(sales): add CompleteSale cash workflow
test(sales): verify CompleteSale failure matrix
test(sales): verify CompleteSale idempotence
test(sales): verify concurrent CompleteSale stock safety
```
