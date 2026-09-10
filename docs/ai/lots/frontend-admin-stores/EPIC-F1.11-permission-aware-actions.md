# Epic F1.11 — Permission-aware actions

## Objective

Utiliser les primitives Foundation et les noms générés/partagés pour filtrer routes et actions Store.

## Cases

- Permission accordée, inconnue/non chargée ou refusée localement.
- Backend refusant une action néanmoins visible : traiter proprement `403` et restaurer un état UI cohérent.
- Store hors scope courant : aucune action ou donnée ne doit devenir accessible.

`PermissionGate` est une aide UX, jamais une frontière de sécurité.
