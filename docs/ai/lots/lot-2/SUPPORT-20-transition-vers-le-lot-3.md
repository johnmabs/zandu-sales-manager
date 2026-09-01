# 20. Transition vers le Lot 3

Après validation du Lot 2 :

```text
Organization
      ↓
Catalog
      ↓
Product + Packaging + Price
      ↓
Lot 3
Inventory & Cash foundations
```

Le Lot 3 pourra introduire :

```text
Inventory
├── Stock
├── StockMovement
├── InitializeStock
├── stock availability
└── stock consumption foundations
```

et :

```text
Cash Management
├── CashRegister
├── CashSession
├── OpenCashSession
├── CloseCashSession
└── CashMovement
```

sans encore finaliser la transaction commerciale complète.

La séquence cible :

```text
Lot 1
Identity / Organization / Store
        ↓
Lot 2
Catalog / basic Pricing
        ↓
Lot 3
Inventory / Cash foundations
        ↓
Lot 4
Sales / CompleteSale cash
        ↓
M2
Première vente cash
```

---
