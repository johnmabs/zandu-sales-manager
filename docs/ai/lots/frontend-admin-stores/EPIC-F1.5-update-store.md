# Epic F1.5 — Update Store

## Objective

Modifier uniquement les propriétés éditables via `PATCH /api/stores/{id}` et le contrat de modification dédié.

## Invariants

- `StoreCode` est read-only ou absent du formulaire.
- Initialiser depuis l’état serveur puis invalider/refetch après succès.
- Si une version ou un `409 CONFLICT` signale une concurrence, ne jamais écraser silencieusement ; inviter à recharger les données.
