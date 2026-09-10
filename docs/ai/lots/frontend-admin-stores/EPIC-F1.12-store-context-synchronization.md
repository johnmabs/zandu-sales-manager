# Epic F1.12 — Store context synchronization

## Objective

Éviter un `StoreContext` obsolète après suspension, réactivation, fermeture demandée ou indisponibilité du Store actif.

## Mutation lifecycle

```text
mutation
→ invalidate Store
→ invalidate Store list
→ refresh active StoreContext
→ reevaluate route/action access
```

Si le Store actif n’est plus opérationnel, afficher un état explicite et ne pas continuer comme s’il l’était.
