# 19. Epic 4.12 — Persistence & RLS

Tables tenant-owned :

```text
sales.sale
sales.sale_line
payment tables utilisées par Lot 4
```

Persistence :

- mappings Doctrine ;
- UUID v7 ;
- optimistic version ;
- indexes tenant/store/status/business date ;
- snapshots historiques ;
- RLS ;
- contraintes idempotence.

Une archive ou modification du catalogue ne doit pas casser la lecture d’une vente finalisée.

Commits :

```text
feat(sales): persist sale aggregate
feat(sales): persist sale line snapshots
test(tenant): verify sales PostgreSQL RLS
```

---
