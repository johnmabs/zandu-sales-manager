# 7. Epic 6.1 — Purchasing foundation

Structure :

```text
src/Modules/Purchasing/
├── Domain/
├── Application/
│   └── Contract/
├── Infrastructure/
└── Presentation/
    └── Api/
```

Créer ou vérifier le schéma PostgreSQL :

```text
purchasing
```

Tables prévues progressivement :

```text
supplier
purchase_order
purchase_order_line
goods_receipt
goods_receipt_line
goods_receipt_correction
goods_receipt_correction_line
purchase_return
purchase_return_line
```

Interdictions :

```text
Purchasing\Domain → Doctrine
Purchasing\Domain → Symfony
Purchasing\Domain → Inventory\Domain
Purchasing\Domain → Catalog\Domain
```

Commit :

```text
refactor(purchasing): add bounded context structure
```

---
