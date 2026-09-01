# 46. Persistence StockTransfer

Tables :

```text
inventory.stock_transfer
inventory.stock_transfer_line
```

Contraintes :

```text
source_store_id != destination_store_id
unique transfer + product
```

Versions pour concurrency.

Indexes :

```text
organization
source store
destination store
status
createdAt
```

RLS.

---
