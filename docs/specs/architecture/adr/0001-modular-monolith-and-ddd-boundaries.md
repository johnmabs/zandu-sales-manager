# ADR-0001 — Modular Monolith et frontières DDD

**Status:** ACCEPTED  
**Date:** 2026-08-19

## Context

Zandu Sales Manager couvre plusieurs capacités métier distinctes et la baseline impose des bounded contexts explicites. Un module ne doit pas accéder directement aux repositories ou aggregates d’un autre bounded context.

## Decision

Zandu est construit initialement sous forme de **modular monolith**.

```text
src/
├── Modules/
├── SharedKernel/
└── Platform/
```

Chaque module métier est organisé autour de `Domain`, `Application`, `Infrastructure` et `Presentation`.

Les communications synchrones inter-contextes passent par des `Application Contracts` explicitement exposés. Les communications asynchrones passent par des `Integration Events` versionnés.

## Rationale

Cette organisation conserve une transaction locale simple et une exploitation adaptée à une petite équipe, tout en maintenant des frontières métier strictes.

## Consequences

### Positive

- déploiement initial simple ;
- transactions locales possibles ;
- bounded contexts indépendamment compréhensibles ;
- extraction future d’un module possible si elle devient nécessaire.

### Negative / Trade-offs

- la proximité physique des modules peut encourager des dépendances interdites ;
- les frontières doivent être protégées par des tests d’architecture.

## Constraints

Un module ne contourne jamais un autre module par accès direct à ses tables, repositories ou aggregates.

## Related decisions

ADR-0002, ADR-0003, ADR-0004, ADR-0005.
