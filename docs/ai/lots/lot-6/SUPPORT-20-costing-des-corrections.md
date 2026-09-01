# 20. Costing des corrections

Les corrections sont prospectives : elles ajustent quantité et valeur sans rejouer rétroactivement toutes les sorties historiques.

## Correction IN

Une sous-déclaration corrigée positivement utilise une source de coût explicite et traçable.

## Correction OUT

Une correction négative ajuste quantité et valeur prospectivement et ne peut rendre Stock négatif.

Créer un `StockValuationMovement` lorsque le mouvement physique est valorisé.

Commit :

```text
feat(costing): value goods receipt corrections
```

---
