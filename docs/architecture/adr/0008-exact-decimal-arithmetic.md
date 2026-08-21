# ADR-0008 — Arithmétique décimale exacte

**Status:** ACCEPTED  
**Date:** 2026-08-19

## Decision

`float` n’est pas utilisé pour les valeurs métier nécessitant une représentation exacte, notamment `Money`, `Quantity`, `TaxRate`, `ConversionFactor` et `InventoryCost`.

Le `SharedKernel` expose :

```text
Decimal
DecimalFactory
RoundingMode
```

L’implémentation MVP utilise `brick/math` sans exposer `Brick\Math` au `Domain`.

`Money` encapsule un `Decimal amount` et une `Currency`. Les décimaux métier transitent dans les contrats JSON sous forme de chaînes.

## Rationale

Les calculs monétaires, quantités, conversions, taxes et coûts doivent être déterministes et exempts des erreurs binaires propres aux floating points.

## Constraints

Toute opération pouvant perdre de la précision exige une politique d’arrondi explicite.

## Resolution des questions de persistence

Les précisions initiales de persistence et de calcul intermédiaire ont été
validées par le Spike C puis fixées dans
[ADR-0014](0014-postgresql-numeric-precision.md).

Les règles fiscales propres à la juridiction restent une décision métier à
documenter avant leur implémentation ; l’exigence d’arrondi explicite demeure.
