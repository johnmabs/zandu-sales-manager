# 25. Blind vs Guided

## BLIND

Le compteur ne voit pas :

```text
expectedQuantity
variance
```

avant validation de sa saisie selon UX/policy.

## GUIDED

Le compteur peut voir la quantité théorique.

La distinction relève de la lecture/API et de la policy, mais ne change pas l’invariant.

Le mode doit être snapshoté dans le `StockCount`.

---
