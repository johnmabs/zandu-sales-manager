# 12. Epic 5.5 — Costing bootstrap

Conformément à l’ADR-0021 :

```text
InitializeStockValuation
├── storeId
├── productId
├── openingUnitCost
└── reason
```

Préconditions :

- Stock existe ;
- valuation non initialisée ;
- permission dédiée ;
- raison obligatoire ;
- même tenant/store/devise.

Résultat :

```text
quantity = Stock.quantityOnHand
totalValue = quantity × openingUnitCost
```

Permission :

```text
INVENTORY_COSTING_INITIALIZE
```

Commit :

```text
feat(costing): add valuation bootstrap
```

---
