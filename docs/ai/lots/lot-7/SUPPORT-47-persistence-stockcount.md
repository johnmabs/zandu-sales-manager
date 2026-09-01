# 47. Persistence StockCount

Tables :

```text
inventory.stock_count
inventory.stock_count_line
inventory.open_stock_count_scope
```

Contraintes :

```text
UNIQUE(stock_count_id, product_id)
```

et :

```text
UNIQUE(
 organization_id,
 store_id,
 product_id
)
```

sur `open_stock_count_scope`.

Index :

```text
status
store
reconciliationStatus
```

RLS sur toutes les données tenant-owned.

---
