# 17. Epic 4.10 — Authorization & audit

Permissions :

```text
SALE_CREATE
SALE_READ
SALE_UPDATE_DRAFT
SALE_CANCEL_DRAFT
SALE_COMPLETE
SALE_PRICE_OVERRIDE
```

`SALE_PRICE_OVERRIDE` seulement si la capacité existe.

Rôles système :

```text
ORGANIZATION_OWNER
STORE_MANAGER
CASHIER
ACCOUNTANT
```

reçoivent les permissions selon la policy existante, jamais par checks de rôle dans Sales Domain.

Guards :

```text
OrganizationOperationalGuard
StoreOperationalGuard
```

Store suspendu :

```text
CreateSale
CompleteSale
→ refusés
```

Audit sensible :

```text
SALE_PRICE_OVERRIDE
approvals éventuelles
```

Les ledgers métier restent distincts du Security Audit.

Commits :

```text
feat(access): add cash sales permissions
feat(access): grant cash sales permissions
feat(sales): enforce sales authorization
feat(sales): publish sale completion events
```

---
