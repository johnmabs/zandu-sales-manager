# Epic F1.7 — Reactivate Store

## Objective

Exposer la transition dédiée de réactivation pour un Store suspendu.

## Requirements

- Montrer l’action seulement si l’état client et la permission connue la permettent ; accepter le refus final du serveur.
- Après succès, rafraîchir le statut et la liste, puis resynchroniser le `StoreContext` s’il est affecté.
