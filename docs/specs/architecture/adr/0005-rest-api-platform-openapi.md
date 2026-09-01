# ADR-0005 — REST + API Platform + OpenAPI

**Status:** ACCEPTED  
**Date:** 2026-08-19

## Decision

L’API principale utilise **REST + API Platform + OpenAPI**.

```text
HTTP
 ↓
API Platform
 ↓
StateProcessor / StateProvider
 ↓
Application
 ↓
Domain
```

`ApiResource` n’est pas synonyme de `Domain Aggregate`.

Les opérations métier significatives sont intentionnelles, par exemple `CompleteSale`, et ne sont pas représentées comme de simples changements CRUD de statut.

## Rationale

API Platform fournit une couche HTTP productive, une documentation OpenAPI et des mécanismes de sérialisation sans imposer d’exposer directement le modèle métier.

## Constraints

La logique métier reste dans `Application` et `Domain`. Next.js ou API Platform ne deviennent pas des couches métier alternatives.
