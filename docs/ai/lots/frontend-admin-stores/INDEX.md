# Frontend Lot F1 — Admin Stores — AI routing index

> Router compact : lire uniquement le contexte, l’Epic courant et les supports requis.

## Source specification

`docs/specs/planning/zandu-frontend-lot-f1-admin-stores.md`

## Read first

- `CONTEXT.md`

## Epics

- `EPIC-F1.1-stores-feature-foundation.md` — frontière et architecture de la feature.
- `EPIC-F1.2-store-list.md` — liste tenant-scoped et états UX.
- `EPIC-F1.3-store-details.md` — détail, statut, fermeture et actions disponibles.
- `EPIC-F1.4-create-store.md` — création, validation, erreurs et idempotence.
- `EPIC-F1.5-update-store.md` — édition, immutabilité du code et conflits.
- `EPIC-F1.6-suspend-store.md` — transition explicite de suspension.
- `EPIC-F1.7-reactivate-store.md` — transition explicite de réactivation.
- `EPIC-F1.8-request-store-closure.md` — demande de fermeture et réponse workflow.
- `EPIC-F1.9-store-closure-blockers.md` — rendu des blockers fournis par l’API.
- `EPIC-F1.10-cancel-store-closure.md` — annulation conditionnée au contrat HTTP.
- `EPIC-F1.11-permission-aware-actions.md` — protection UX et refus serveur.
- `EPIC-F1.12-store-context-synchronization.md` — cohérence du Store actif après mutation.
- `EPIC-F1.13-loading-empty-error-states.md` — états explicites et erreurs sûres.
- `EPIC-F1.14-mutation-safety.md` — double soumission, timeout et idempotence.
- `EPIC-F1.15-responsive-admin-ux.md` — desktop-first, tablette utilisable.
- `EPIC-F1.16-accessibility.md` — clavier, focus, formulaires et dialogues.
- `EPIC-F1.17-unit-component-tests.md` — tests unitaires et composants ciblés.
- `EPIC-F1.18-integration-tests.md` — parcours d’intégration Stores.
- `EPIC-F1.19-e2e-vertical-slice.md` — preuve bout en bout du slice.
- `EPIC-F1.20-observability.md` — contexte diagnostic et données interdites.

## Support files

- `SUPPORT-api-and-lifecycle.md` — ouvrir pour les contrats Store/StoreClosure et leurs invariants UX.
- `SUPPORT-routing-and-screens.md` — ouvrir lors d’un travail sur les routes ou la composition des écrans.
- `SUPPORT-gate-and-delivery.md` — ouvrir pour validation du Lot, ordre ou stratégie de commits.
- `SUPPORT-out-of-scope-and-next.md` — ouvrir pour arbitrer le périmètre ou préparer F2.

## Dependencies

Utiliser seulement les dépendances par capacités déclarées dans `CONTEXT.md` ou l’Epic courant. Ne pas lire les Lots précédents par séquence.

## Relevant ADRs

- ADR-0005 — REST + API Platform + OpenAPI.
- ADR-0006 — Authentication JWT MVP.
- ADR-0009 — Frontend Web.
- ADR-0013 — OpenTelemetry.
- ADR-0017 — isolation tenant PostgreSQL RLS.
- ADR-0018 — utilisateur global et organisation active.
- ADR-0024 — workspace frontend pnpm.
