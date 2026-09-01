# 28. Conflit de snapshot

Lors de finalisation :

```text
Stock.quantityOnHand
must equal
StockCountLine.expectedQuantity
```

Normalement le lock empêche la divergence.

Si elle existe quand même :

```text
STOCK_COUNT_SNAPSHOT_CONFLICT
```

ou erreur équivalente.

Ne jamais écraser silencieusement un état inattendu.

Cela doit déclencher diagnostic/audit technique.

---
