# 10. Epic 0.7 — Spikes architecturaux

Les spikes ne sont pas des prétextes pour produire du code métier final. Ils doivent être reproductibles, testés et documentés.

---

## Spike A — `CompleteSale`

### Objectif

Prouver une transaction locale atomique impliquant :

```text
Sale
Stock
StockMovement
CashSession
CashMovement
OutboxMessage
```

### Étapes / commits proposés

```text
test(spike-a): add CompleteSale transaction fixture
test(spike-a): inject inventory failure before commit
test(spike-a): inject cash failure before commit
test(spike-a): inject outbox failure before commit
docs(spike-a): document CompleteSale transaction results
```

Si une modification architecturale est nécessaire :

```text
docs(adr): update transaction coordination decision
```

### Succès

```text
failure before commit
→ zero partial business effect
```

---

## Spike B — Outbox

### Objectif

Prouver :

```text
at-least-once
multi-worker safety
retry
crash recovery
crash entre claim/publication et publication/acknowledgement
consumer idempotence
retry borné avec backoff, puis dead-letter `FAILED`
```

### Commits proposés

```text
feat(outbox): add transactional outbox prototype
test(outbox): verify multi-worker message claiming
test(outbox): verify redelivery after worker crash
test(outbox): verify idempotent consumer processing
docs(spike-b): document outbox validation results
```

---

## Spike C — Decimal / Money / Quantity

### Objectif

Tester :

```text
JSON string
→ Decimal
→ Value Object
→ Doctrine
→ PostgreSQL NUMERIC
→ Value Object
```

Corpus :

```text
unit
kg
g
liter
meter
carton
fractional packaging
```

Inclure :

```text
taxes
discounts
conversion factors
allocation residues
costing
refunds
```

### Commits proposés

```text
test(decimal): add persistence round-trip cases
test(quantity): add real product precision corpus
test(money): add tax and discount rounding cases
test(costing): add intermediate precision cases
docs(spike-c): record numeric precision conclusions
```

Puis, après décision :

```text
docs(adr): record Money and Quantity persistence precision
```

---

## Spike E — Stock concurrency

### Scénario minimal

```text
Stock = 5

Transaction A consumes 4
Transaction B consumes 3
```

Comparer :

```text
optimistic locking
conditional DBAL update
```

### Commits proposés

```text
test(stock): add concurrent stock consumption scenario
test(stock): benchmark optimistic locking strategy
test(stock): benchmark conditional update strategy
docs(spike-e): record stock concurrency decision
```

Si une stratégie est retenue :

```text
docs(adr): record stock concurrency persistence strategy
```

### Invariant absolu

```text
quantityOnHand >= 0
```

---
