# ADR-0016 — Traitement de la transactional outbox

**Status:** ACCEPTED  
**Date:** 2026-08-21

## Decision

Les messages d’intégration sont insérés dans l’outbox dans la même transaction
locale que les effets métier. Les workers réclament des lots avec
`FOR UPDATE SKIP LOCKED` et une échéance de claim.

La livraison est **at-least-once**. Chaque consumer persiste une clé unique
`(consumer, message_id)` avant d’appliquer son effet. Les échecs incrémentent le
nombre de tentatives, utilisent un délai avant retry et passent en dead letter
au seuil configuré. Un claim abandonné redevient disponible après rollback ou
expiration.

## Consequences

Les handlers doivent être idempotents. L’outbox ne promet jamais exactly-once ;
elle rend les doublons sûrs et observables.
