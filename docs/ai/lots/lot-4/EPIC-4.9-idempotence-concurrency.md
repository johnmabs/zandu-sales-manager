# 16. Epic 4.9 — Idempotence & concurrency

## Idempotency-Key

`CompleteSale` utilise la fondation du Lot 0.

```text
same key + same payload
→ same result
```

```text
same key + different payload
→ IDEMPOTENCY_CONFLICT
```

## Retry après commit inconnu

```text
server COMMIT
connection lost
client retry
```

Résultat :

```text
Sale COMPLETED
1 Payment
1 CashMovement
1 Stock effect par produit
```

## Double completion concurrente

Deux CompleteSale simultanés sur le même Sale :

```text
→ un seul effet métier
```

## Deux ventes concurrentes

```text
Stock = 5
Sale A = 4
Sale B = 3
```

Résultat :

```text
quantityOnHand jamais négatif
aucun lost update
```

Commits :

```text
feat(sales): enforce CompleteSale idempotency
test(sales): verify CompleteSale retry after commit
test(sales): verify concurrent sale completion
test(sales): verify concurrent CompleteSale stock safety
```

---
