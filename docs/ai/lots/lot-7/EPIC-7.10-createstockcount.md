# 20. Epic 7.10 — CreateStockCount

Command :

```text
CreateStockCount
├── storeId
├── scopeType
├── productIds[]?
└── mode?
```

## FULL

Le périmètre couvre tous les produits stockés/éligibles du store selon la règle de résolution décidée.

## PARTIAL

Le périmètre est une liste explicite de :

```text
ProductId
```

Inventory ne dépend jamais de :

```text
Catalog.Category
```

Une UI pourra résoudre une catégorie en liste de ProductId avant l’appel Inventory.

Commit :

```text
feat(inventory): add create stock count use case
```

---
