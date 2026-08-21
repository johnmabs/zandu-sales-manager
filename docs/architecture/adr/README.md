# Zandu Sales Manager — Architecture Decision Records

Ce dossier contient les décisions d’architecture significatives de Zandu Sales Manager.

Les ADR complètent la **Spécification d’architecture DDD** : la baseline décrit les invariants et décisions architecturales à respecter ; les ADR documentent les choix techniques significatifs, leurs raisons et leurs compromis.

## Statuts

- `PROPOSED` — décision proposée, pas encore acceptée.
- `ACCEPTED` — décision adoptée.
- `SUPERSEDED` — remplacée par un ADR ultérieur.
- `DEPRECATED` — décision encore documentée mais déconseillée.
- `REJECTED` — proposition explicitement rejetée.

Un ADR accepté n’est pas réécrit pour masquer l’historique d’une nouvelle décision : une modification architecturale significative produit un nouvel ADR qui référence et, si nécessaire, remplace l’ancien.

## Registre

| ADR | Décision | Statut |
|---|---|---|
| [ADR-0001](0001-modular-monolith-and-ddd-boundaries.md) | Modular Monolith et frontières DDD | ACCEPTED |
| [ADR-0002](0002-php-symfony-backend.md) | PHP et Symfony pour le backend | ACCEPTED |
| [ADR-0003](0003-postgresql-transactional-database.md) | PostgreSQL comme base transactionnelle | ACCEPTED |
| [ADR-0004](0004-doctrine-persistence-strategy.md) | Doctrine ORM + DBAL | ACCEPTED |
| [ADR-0005](0005-rest-api-platform-openapi.md) | REST + API Platform + OpenAPI | ACCEPTED |
| [ADR-0006](0006-jwt-authentication.md) | Authentication JWT interne pour le MVP | ACCEPTED |
| [ADR-0007](0007-uuid-v7-identifiers.md) | UUID v7 et abstraction Zandu | ACCEPTED |
| [ADR-0008](0008-exact-decimal-arithmetic.md) | Arithmétique décimale exacte | ACCEPTED |
| [ADR-0009](0009-web-frontend.md) | Next.js + React + TypeScript | ACCEPTED |
| [ADR-0010](0010-tauri-pos-runtime.md) | React + Vite + Tauri pour le POS | ACCEPTED |
| [ADR-0011](0011-sqlite-pos-local-storage.md) | SQLite pour le stockage local POS | ACCEPTED |
| [ADR-0012](0012-docker-deployment-artifact.md) | Docker comme artifact de déploiement | ACCEPTED |
| [ADR-0013](0013-opentelemetry-observability.md) | OpenTelemetry comme standard d’observabilité | ACCEPTED |
| [ADR-0014](0014-postgresql-numeric-precision.md) | Précision PostgreSQL des décimaux métier | ACCEPTED |
| [ADR-0015](0015-stock-concurrency-strategy.md) | Stratégie de concurrence pour le stock | ACCEPTED |
| [ADR-0016](0016-transactional-outbox-processing.md) | Traitement de la transactional outbox | ACCEPTED |

## Décisions encore ouvertes ou proposées

Ces éléments ne disposent pas encore d’un ADR `ACCEPTED` :

| Sujet | Statut | Validation attendue |
|---|---|---|
| Plateforme de déploiement : Render / Frankfurt | PROPOSED | Spike infrastructure + test de latence |
| Backend d’observabilité : Grafana Cloud | PROPOSED | Spike infrastructure |
| Chiffrement SQLite | OPEN | Spike sécurité/offline |
| Rotation des clés POS | OPEN | Spike sécurité/offline |
| Survente offline | OPEN | Décision métier |
