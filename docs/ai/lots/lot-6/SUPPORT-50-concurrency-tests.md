# 50. Concurrency tests

PostgreSQL réel.

## Concurrent receipts

```text
ordered = 100
received = 70
A = +20
B = +20
```

Sans over receipt :

```text
final cumulative <= 100
```

## Receipt and sale

Une vente et une réception simultanées sur le même Stock doivent conserver :

```text
quantity correct
valuation coherent
no lost update
```

## Correction concurrente

Deux corrections sur la même effective quantity doivent détecter un conflit.

Commit :

```text
test(purchasing): verify concurrent purchasing operations
```

---
