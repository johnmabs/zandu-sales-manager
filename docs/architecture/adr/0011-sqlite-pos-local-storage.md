# ADR-0011 — SQLite comme stockage opérationnel du POS

**Status:** ACCEPTED  
**Date:** 2026-08-19

## Decision

**SQLite** constitue le stockage opérationnel local du POS.

SQLite n’est **pas** une réplique de PostgreSQL.

PostgreSQL reste l’état métier autoritatif. Le stockage local contient notamment `LocalReadModel`, `OfflineCommand`, `LocalOperationLedger`, `SyncState` et des estimations opérationnelles locales.

La synchronisation est un protocole applicatif :

```text
commands ↑
changes  ↓
```

et non une réplication générique de tables.

## Consequences

Les opérations locales métier doivent être atomiques. Les commandes non acquittées sont superposées aux projections serveur et ne sont jamais silencieusement perdues lors d’un download.

## Open questions

Restent notamment ouverts :

- implémentation du chiffrement SQLite ;
- stockage et rotation des clés ;
- durée offline maximale ;
- allowances terminal ;
- résolution de la survente offline ;
- rétention locale.
