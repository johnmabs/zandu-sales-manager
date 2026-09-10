# Epic F1.3 — Store details

## Objective

Charger le détail par `GET /api/stores/{id}` et présenter identité, profil, statut opérationnel, actions disponibles et état de fermeture.

## Requirements

- Les champs rendus proviennent du contrat réel.
- Afficher Edit, Suspend, Reactivate et Request Closure seulement selon l’état courant et les permissions connues.
- Traiter not found, identifiant obsolète, permission refusée et ressource cross-tenant masquée.
