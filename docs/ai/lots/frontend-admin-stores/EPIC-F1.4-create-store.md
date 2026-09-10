# Epic F1.4 — Create Store

## Objective

Créer un Store via `POST /api/stores` à partir du schéma OpenAPI de création.

## Requirements

- N’ajouter que des validations UX reflétées par le contrat ; laisser les invariants métier au serveur.
- Après succès : invalider/refetch la collection, naviguer vers le détail et notifier le succès.
- Traiter validation, code dupliqué, permission, restriction opérationnelle, réseau et erreur inattendue.
- Si requis par l’endpoint, conserver la même `Idempotency-Key` pour le retry d’une même intention.
