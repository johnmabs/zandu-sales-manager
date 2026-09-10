# Epic F1.1 — Stores feature foundation

## Objective

Créer dans `apps/admin` une frontière `stores` regroupant API, composants, hooks, routes, schémas, state local éventuel et tests.

## Constraints

- Suivre F0.24 et les conventions réellement établies dans le workspace.
- La feature dépend des packages Foundation ; aucun package bas niveau ne dépend de la feature.
- Interdire notamment `packages/ui → stores` et `packages/api-client → apps/admin`.
- Ne pas reproduire l’architecture DDD ni l’aggregate backend dans le frontend.

## Validation

Feature importable, routing fonctionnel et contraintes d’architecture au vert.
