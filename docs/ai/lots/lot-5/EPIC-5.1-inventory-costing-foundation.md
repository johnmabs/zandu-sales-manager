# 8. Epic 5.1 — Inventory Costing foundation

Structure recommandée :

```text
src/Modules/InventoryCosting/
├── Domain/
├── Application/
│   └── Contract/
├── Infrastructure/
└── Presentation/
    └── Api/
```

Interdit :

```text
InventoryCosting\Domain → Inventory\Infrastructure
InventoryCosting\Domain → Sales\Domain
InventoryCosting\Domain → Purchasing\Domain
```

Commit :

```text
refactor(costing): add inventory costing bounded context
```

---
