# 38. Tenant cache isolation

Invariant frontend :

> une entrée de cache dépendante d’une organization doit inclure `organizationId`.

Une entrée dépendante d’un store doit inclure :

```text
organizationId
+
storeId
```

À changement d’organization :

```text
invalidate / isolate relevant cache
```

pour éviter toute fuite visuelle cross-tenant.

---
