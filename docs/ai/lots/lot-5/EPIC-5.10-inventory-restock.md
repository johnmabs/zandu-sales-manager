# 18. Epic 5.10 — Inventory restock

Si :

```text
restock = true
```

Inventory exécute :

```text
Stock.restockFromSaleReturn(...)
```

et crée :

```text
StockMovement
type = SALE_RETURN
source = RETURN / ReturnSaleId
```

Si :

```text
restock = false
```

aucun changement Stock.

Idempotence :

```text
SALE_RETURN + returnId + productId
```

Commits :

```text
feat(inventory): restock returned sale items
feat(inventory): make sale return restock idempotent
```

---
