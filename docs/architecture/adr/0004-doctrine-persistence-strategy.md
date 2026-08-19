# ADR-0004 — Doctrine ORM + DBAL pour la persistence

**Status:** ACCEPTED  
**Date:** 2026-08-19

## Decision

La stratégie de persistence est **ORM first, not ORM only**.

`Doctrine ORM` est utilisé pour la persistence ordinaire des aggregates. `Doctrine DBAL` ou du SQL explicite peuvent être utilisés pour les `conditional updates`, chemins à forte contention, batchs, requêtes spécialisées et mécanismes de locking.

Les migrations utilisent `Doctrine Migrations`.

## Rationale

Cette approche conserve la productivité de l’ORM sans obliger les hotspots transactionnels à entrer artificiellement dans son modèle.

## Consequences

Les repositories sont définis selon les besoins du modèle, et non comme des repositories CRUD génériques.

## Validation

Le Spike E doit déterminer si certains chemins critiques de `Stock` nécessitent un update conditionnel DBAL.
