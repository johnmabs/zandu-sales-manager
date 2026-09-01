# 36. Epic F0.15 — Server state

## PROPOSÉ

Séparer :

```text
server state
```

de :

```text
UI state
```

Le cache des données backend doit utiliser une solution spécialisée de query/cache.

Exemples de responsabilités :

```text
fetch
cache
invalidate
retry policy
loading
mutation
optimistic update when safe
```

Ne pas copier toutes les entités serveur dans un store global maison.

---
