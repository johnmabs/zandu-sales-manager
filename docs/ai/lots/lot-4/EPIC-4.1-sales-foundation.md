# 8. Epic 4.1 — Sales foundation

## Étape 4.1.1 — Préparer le module Sales

```text
src/Modules/Sales/
├── Domain/
├── Application/
│   └── Contract/
├── Infrastructure/
└── Presentation/
    └── Api/
```

Interdit :

```text
Sales\Domain → Inventory\Domain
Sales\Domain → CashManagement\Domain
Sales\Domain → Catalog\Domain
```

Autorisé :

```text
Sales\Application → */Application/Contract
```

Commit :

```text
refactor(sales): prepare sales bounded context
```

## Étape 4.1.2 — Persistence foundation

Créer dans `sales` :

```text
sale
sale_line
```

Ne pas introduire Returns/Refunds/Reservations.

Commit :

```text
feat(database): add sale persistence tables
```

### DoD Epic 4.1

- module prêt ;
- schema prêt ;
- architecture tests verts.

---
