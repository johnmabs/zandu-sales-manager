# ADR-0016 — Traitement de la transactional outbox

**Status:** ACCEPTED  
**Date:** 2026-08-21

## Decision

Les messages d’intégration sont insérés dans l’outbox dans la même transaction
locale que les effets métier. Les workers réclament des lots avec
`FOR UPDATE SKIP LOCKED` et une échéance de claim.

Chaque claim reçoit également un jeton unique. L'acquittement et la mise en
échec vérifient ce jeton : après expiration et reprise d'un message, un worker
retardataire ne peut donc plus modifier le claim du nouveau worker.

La livraison est **at-least-once**. Chaque consumer persiste une clé unique
`(consumer, message_id)` avant d’appliquer son effet. Les échecs incrémentent le
nombre de tentatives et utilisent un backoff exponentiel plafonné avant retry.
Le nombre de tentatives est borné ; au seuil configuré, le message passe à
l'état terminal `FAILED`, qui constitue la dead-letter persistée. Les quatre
états persistés sont `PENDING`, `PROCESSING`, `PUBLISHED` et `FAILED`. Un claim
abandonné redevient disponible après rollback ou expiration.

## Consequences

Les handlers doivent être idempotents. L’outbox ne promet jamais exactly-once ;
elle rend les doublons sûrs et observables.
