# 37. Query keys

Convention centralisée :

```text
organizations
stores
products
stock
suppliers
purchaseOrders
sales
```

Toujours inclure les scopes nécessaires.

Exemple :

```text
['stock', organizationId, storeId, filters]
```

Jamais :

```text
['stock']
```

si cela peut mélanger les tenants/stores dans le cache.

---
