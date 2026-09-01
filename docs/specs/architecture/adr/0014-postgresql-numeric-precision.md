# ADR-0014 — Précision PostgreSQL des décimaux métier

**Status:** ACCEPTED  
**Date:** 2026-08-21

## Context

L’ADR-0008 impose les décimaux exacts mais laissait les précisions de persistence
à valider par le Spike C.

## Decision

- `Quantity`, `ConversionFactor`, `TaxRate` et les calculs intermédiaires de
  costing utilisent `NUMERIC(30,12)` ;
- les montants monétaires persistés utilisent `NUMERIC(30,6)` afin de conserver
  les sous-unités nécessaires aux calculs et allocations ;
- les montants comptabilisés ou présentés sont arrondis explicitement selon la
  monnaie et la règle métier au point de décision, jamais implicitement par la
  base ;
- les contrats JSON continuent de transporter les décimaux sous forme de
  chaînes.

## Consequences

La précision intermédiaire est homogène et déterministe. Une juridiction ou un
produit exigeant plus de 12 décimales nécessitera une nouvelle ADR et une
migration expand-and-contract.
