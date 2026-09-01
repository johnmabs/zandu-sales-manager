# ADR-0013 — OpenTelemetry comme standard d’observabilité

**Status:** ACCEPTED  
**Date:** 2026-08-19

## Decision

**OpenTelemetry** constitue le standard d’instrumentation de Zandu pour les logs, métriques et traces.

`TraceId` / `SpanId`, `CorrelationId` et `CausationId` restent des concepts distincts.

Les logs sont structurés et n’incluent ni secrets ni données sensibles interdites.

## Rationale

L’instrumentation doit rester indépendante du fournisseur d’observabilité et couvrir HTTP, transactions, commandes applicatives, workers, outbox et synchronisation.

## Constraints

Les logs et traces techniques ne remplacent jamais `SecurityAuditEntry`, `StockMovement`, `CashMovement`, `OutboxMessage` ou les autres preuves métier.

## Open questions

Le backend d’observabilité reste `PROPOSED` : Grafana Cloud via OTLP.
