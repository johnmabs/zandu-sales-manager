# ADR-0015 — Stratégie de concurrence pour le stock

**Status:** ACCEPTED  
**Date:** 2026-08-21

## Decision

Les consommations atomiques de stock sur les chemins chauds utilisent un UPDATE
DBAL conditionnel :

```sql
UPDATE stock
SET quantity = quantity - :consumed, version = version + 1
WHERE id = :id AND quantity >= :consumed
```

Un nombre de lignes modifiées égal à zéro représente un stock insuffisant ou un
conflit. Les workflows plus riches d’aggregate conservent l’optimistic locking
par version. PostgreSQL porte en plus une contrainte `CHECK (quantity >= 0)`.

## Rationale

Le Spike E prouve que les deux stratégies protègent l’invariant, mais l’UPDATE
conditionnel évite le read-before-write pour la consommation simple et réduit
la fenêtre de concurrence.
