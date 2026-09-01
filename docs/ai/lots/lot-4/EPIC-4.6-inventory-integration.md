# 13. Epic 4.6 — Inventory integration

Utiliser le contrat Lot 3 :

```text
InventoryStockConsumer
```

Commande :

```text
ConsumeStockForSale
├── organizationId
├── storeId
├── saleId
└── items[]
    ├── productId
    └── baseQuantity
```

Pour chaque produit suivi :

```text
Stock.consumeForSale(...)
StockMovement
  type = SALE
  source = SALE / SaleId
```

Pour produit non suivi/service :

```text
aucun effet Inventory
```

Idempotence logique :

```text
SALE + saleId + productId
```

Commits :

```text
feat(inventory): consume stock for cash sale
feat(inventory): make sale stock consumption idempotent
test(inventory): verify concurrent sale consumption
```

### DoD Epic 4.6

- stock suivi décrémenté ;
- untracked/service ignorés ;
- mouvement SALE ;
- aucun stock négatif ;
- concurrence sûre.

---
