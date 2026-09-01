# 29. Epic F0.13 — StoreContext

Certaines fonctionnalités nécessitent :

```text
activeStoreId
```

Mais toutes ne sont pas store-scoped.

Exemples organization-scoped :

```text
organization settings
memberships
some roles
global supplier views
```

Exemples store-scoped :

```text
cash session
stock
stock count
sale
purchase receipt
```

Le frontend doit donc distinguer :

```text
OrganizationContext
```

de :

```text
StoreContext
```

---
