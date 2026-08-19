# ADR-0002 — PHP et Symfony pour le backend

**Status:** ACCEPTED  
**Date:** 2026-08-19

## Context

Le backend doit supporter DDD, transactions complexes, PostgreSQL, API HTTP, workers, sécurité et traitement asynchrone. L’équipe actuelle maîtrise fortement Symfony. L’adoption d’une autre stack, notamment ASP.NET, introduirait un coût d’apprentissage sans bénéfice architectural actuellement démontré.

## Decision

Le backend utilise **PHP + Symfony**.

Symfony constitue le framework applicatif et d’infrastructure. Le `Domain` reste indépendant du framework lorsque cette indépendance possède une valeur architecturale réelle.

## Rationale

Le choix maximise la capacité d’exécution de l’équipe actuelle et bénéficie d’un écosystème mature pour Doctrine, API Platform, Security et Messenger.

## Consequences

### Positive

- expertise existante ;
- faible coût cognitif initial ;
- intégration naturelle avec les composants retenus.

### Negative / Trade-offs

- discipline nécessaire pour empêcher Symfony de contaminer le modèle métier ;
- dépendance à l’écosystème PHP/Symfony.

## Constraints

Nous abstrayons les dépendances architecturalement significatives, pas chaque API du framework.
