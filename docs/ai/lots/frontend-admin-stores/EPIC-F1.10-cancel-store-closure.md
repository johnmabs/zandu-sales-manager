# Epic F1.10 — Cancel Store Closure

## Objective

Inclure l’annulation de fermeture uniquement si l’OpenAPI courant expose une opération HTTP explicite.

## Decision rule

- Endpoint et schéma présents : implémenter avec les mêmes règles de permission, mutation et refresh que les autres transitions.
- Endpoint absent : différer cet Epic ; ne jamais inventer une URL ou détourner un PATCH Store.
