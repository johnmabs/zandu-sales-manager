# ADR-0003 — PostgreSQL comme base transactionnelle

**Status:** ACCEPTED  
**Date:** 2026-08-19

## Decision

**PostgreSQL** constitue la source de vérité transactionnelle centrale de Zandu.

Un schéma logique par bounded context est privilégié. Toutes les tables tenant-owned portent explicitement `OrganizationId`.

## Rationale

PostgreSQL fournit les garanties transactionnelles, les contraintes, le type `uuid`, les types numériques exacts et les mécanismes de concurrence nécessaires au modèle.

## Consequences

Les transactions locales cross-context nécessaires au modular monolith restent possibles. Le partage d’une base ne donne toutefois jamais le droit à un module de contourner les frontières applicatives d’un autre.

## Constraints

Les règles tenant et les contraintes d’intégrité doivent être appliquées explicitement et testées.
