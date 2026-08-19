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

## Open questions

Cet ADR ne fixe pas encore :

- le `NUMERIC(p,s)` final ;
- la scale technique de `Quantity` ;
- la précision intermédiaire du costing ;
- les règles fiscales d’arrondi.

Ces éléments sont conditionnés par le Spike C.
