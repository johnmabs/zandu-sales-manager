# 17. Invariants ReturnSale

Un retour cible :

```text
Sale.status = COMPLETED
```

Pour chaque ligne :

```text
cumulativeReturnedQuantity
<= originalSoldQuantity
```

Le retour utilise les snapshots originaux :

```text
packaging
conversion
price
discount
tax
cost
```

Il ne résout pas silencieusement le catalogue courant.

Commit :

```text
feat(sales): enforce cumulative return limits
```

---
