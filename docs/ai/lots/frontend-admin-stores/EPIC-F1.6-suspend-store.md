# Epic F1.6 — Suspend Store

## Objective

Exposer la transition de suspension dédiée ; ne jamais l’émuler par `PATCH status=SUSPENDED`.

## UX and consistency

- Demander confirmation et expliquer que les nouvelles opérations seront bloquées.
- Désactiver l’action pendant la mutation.
- Après succès, rafraîchir détail et liste, resynchroniser le `StoreContext` concerné et notifier le succès.
- Le serveur et son `StoreOperationalGuard` restent l’autorité.
