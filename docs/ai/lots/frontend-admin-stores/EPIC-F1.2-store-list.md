# Epic F1.2 — Store list

## Objective

Afficher la collection tenant-scoped de `GET /api/stores` sur la route Stores définie par le routeur Admin.

## Requirements

- Afficher au minimum `name`, `code`, `status` lorsqu’ils existent dans le contrat ; ne pas inventer de champs.
- Couvrir loading, données, liste vide, permission refusée, erreur réseau et erreur API/métier.
- L’état vide explique l’utilité et propose la création uniquement si la permission client est connue.
- Une ressource hors scope reste inaccessible sans révélation cross-tenant.

## Validation

Liste autorisée, vide, refusée, erreur réseau et scope incorrect.
