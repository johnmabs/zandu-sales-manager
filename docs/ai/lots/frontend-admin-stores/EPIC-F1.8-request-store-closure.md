# Epic F1.8 — Request Store Closure

## Objective

Permettre à l’utilisateur autorisé de demander une fermeture via l’opération HTTP dédiée.

## Invariants

- `StoreClosure` est un workflow distinct du statut Store ; ne jamais envoyer directement `status=CLOSED`.
- Flux : confirmation → API → réponse de fermeture → statut et blockers.
- Présenter les statuts réellement exposés, dont `REQUESTED`, `IN_PROGRESS`, `READY`, `COMPLETED`, `CANCELLED` lorsqu’applicables.
