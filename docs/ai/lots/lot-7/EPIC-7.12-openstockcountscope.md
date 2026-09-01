# 22. Epic 7.12 — OpenStockCountScope

Persistence lock :

```text
OpenStockCountScope
├── OrganizationId
├── StoreId
├── ProductId
└── StockCountId
```

Unicité :

```text
UNIQUE(
    organization_id,
    store_id,
    product_id
)
```

Cela empêche deux comptages ouverts de verrouiller le même produit.

La structure n’est pas un aggregate métier autonome.

Commit :

```text
feat(inventory): lock open stock count scope
```

---
