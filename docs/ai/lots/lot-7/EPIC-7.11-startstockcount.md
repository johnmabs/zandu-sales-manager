# 21. Epic 7.11 — StartStockCount

Passage :

```text
DRAFT → OPEN
```

Lors de cette transaction :

1. résoudre le périmètre ;
2. créer toutes les `StockCountLine` ;
3. capturer `expectedQuantity` ;
4. créer les `OpenStockCountScope` ;
5. publier event/outbox.

Pour un produit sans position Stock :

```text
expectedQuantity = 0
```

mais :

```text
ne pas créer automatiquement Stock(quantity=0)
```

Commit :

```text
feat(inventory): start stock count with snapshot
```

---
