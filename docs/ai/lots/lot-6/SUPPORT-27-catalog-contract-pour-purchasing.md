# 27. Catalog contract pour Purchasing

Créer/réutiliser :

```text
PurchasableProductProvider
```

Retour :

```text
PurchasableProductSnapshot
├── ProductId
├── type
├── status
├── inventoryTracked
├── baseUnitId
├── packaging
│   ├── ProductPackagingId
│   ├── conversionFactor
│   ├── allowedForPurchase
│   └── precision rules
└── version
```

Un service ne produit pas une réception Inventory physique.

Commit :

```text
feat(catalog): expose purchasable product contract
```

---
