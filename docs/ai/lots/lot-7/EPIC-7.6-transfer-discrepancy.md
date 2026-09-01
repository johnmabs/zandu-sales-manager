# 12. Epic 7.6 — Transfer discrepancy

Pour chaque ligne :

```text
transitDiscrepancy
=
shippedQuantity - receivedQuantity
```

Invariant :

```text
transitDiscrepancy >= 0
```

Cas :

```text
0
→ réception exacte
```

```text
> 0
→ perte / écart potentiel en transit
```

Le système ne crée pas automatiquement un `AdjustStock` au destination pour compenser.

La divergence reste attachée au transfert.

Une résolution métier future peut utiliser :

```text
reason
investigation
manual adjustment
```

selon permission.

---
