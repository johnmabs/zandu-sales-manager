# 26. Transition vers le Lot 4

Après le Gate Lot 3 :

```text
Catalog
├── Product
├── ProductPackaging
└── ProductPrice

Inventory
├── Stock
├── StockMovement
└── consume contract

Cash Management
├── CashRegister
├── CashSession
├── CashMovement
└── sale cash contract
```

Le Lot 4 pourra enfin construire :

```text
Sales
├── Sale
├── SaleLine
├── Payment cash minimal
└── CompleteSale
```

Workflow cible :

```text
Create Sale
    ↓
Add lines
    ↓
Resolve product/packaging/price
    ↓
CompleteSale
    │
    ├── validate sale
    ├── consume Inventory
    ├── register cash payment
    ├── create CashMovement
    ├── complete Sale
    └── Outbox
            ↓
          COMMIT
```

Invariant Lot 4 :

```text
si Inventory échoue
→ Sale non completed

si Cash échoue
→ Sale non completed

si Outbox transactionnelle échoue avant commit
→ aucun effet partiel
```

Le Lot 4 atteindra :

```text
M2
Première vente cash
```

---
