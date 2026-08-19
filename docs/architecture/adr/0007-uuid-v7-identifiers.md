# ADR-0007 — UUID v7 et abstraction d’identité

**Status:** ACCEPTED  
**Date:** 2026-08-19

## Decision

Les identifiants internes utilisent **UUID v7**. La génération est principalement applicative et PostgreSQL les stocke avec son type natif `uuid`.

Le `SharedKernel` expose ses propres abstractions :

```text
SharedKernel/Identity/
├── Uuid
├── UuidFactory
├── IdGenerator
├── ProductId
├── StoreId
├── SaleId
└── ...
```

L’implémentation MVP basée sur Symfony UID réside dans `Platform`.

## Rationale

UUID v7 permet la génération distribuée, y compris offline, tout en offrant un ordre temporel utile. L’abstraction évite de propager `Symfony\Component\Uid` dans le cœur.

## Consequences

Les IDs métier restent typés. Un remplacement futur de Symfony UID doit être localisé à l’implémentation d’infrastructure.

## Constraints

Un UUID n’est jamais un secret ni un mécanisme d’autorisation. Les références humaines telles que `SaleNumber` restent distinctes de `SaleId`.
