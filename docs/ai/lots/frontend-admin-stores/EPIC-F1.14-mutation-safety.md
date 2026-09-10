# Epic F1.14 — Mutation safety

## Objective

Sécuriser Create, Suspend, Reactivate et Request Closure contre les doubles soumissions et résultats ambigus.

## Requirements

- Pendant la mutation : désactiver l’action, montrer le pending et préserver l’intention.
- Un timeout ne signifie pas automatiquement échec.
- Utiliser l’idempotence Foundation lorsque le contrat de l’endpoint la prévoit ; un retry de la même intention conserve sa clé.
