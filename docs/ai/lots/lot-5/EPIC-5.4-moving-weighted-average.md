# 11. Epic 5.4 — Moving weighted average

Créer :

```text
MovingWeightedAverageCalculator
```

Entrée :

```text
previousQuantity
previousTotalValue
movementQuantity
movementUnitCost
movementDirection
```

Sortie :

```text
resultingQuantity
resultingTotalValue
resultingAverageUnitCost
movementValue
```

Tester :

- entrées successives à coûts différents ;
- sorties partielles ;
- sortie finale ;
- quantités décimales ;
- coûts décimaux ;
- résidus ;
- absence de float.

Commit :

```text
feat(costing): add moving weighted average calculator
```

---
