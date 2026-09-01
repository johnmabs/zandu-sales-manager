# 58. Epic F0.24 — Feature organization

## Admin

Proposition :

```text
apps/admin/src/
├── app/
├── features/
│   ├── organization/
│   ├── stores/
│   ├── access/
│   ├── catalog/
│   ├── pricing/
│   ├── inventory/
│   ├── purchasing/
│   ├── cash/
│   └── sales/
└── components/
```

Chaque feature regroupe :

```text
components
queries
mutations
schemas
mappers
route-specific logic
```

sans copier l’architecture DDD backend dossier par dossier.

---
