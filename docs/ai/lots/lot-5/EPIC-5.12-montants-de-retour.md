# 20. Epic 5.12 — Montants de retour

Les montants sont déterminés à partir des snapshots originaux.

Ne pas recalculer avec :

```text
prix courant
taxe courante
promotion courante
```

Créer :

```text
ReturnAmountCalculator
```

Garanties :

```text
cumulativeReturnedAmount <= original amount
cumulativeReturnedTax <= original tax
full return = exact refundable total
```

Commit :

```text
feat(sales): calculate return amounts from original snapshots
```

---
