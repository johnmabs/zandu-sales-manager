# 33. EffectiveAccess

Modèle proposé :

```text
EffectiveAccess
├── organizationId
├── authorizationVersion
├── permissions[]
├── scope
└── accessibleStoreIds[]
```

La source doit être le serveur.

Le frontend ne reconstruit pas lui-même les règles de `RoleAssignment`.

---
