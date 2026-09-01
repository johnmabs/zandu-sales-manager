# 67. Ce que M3 permet côté produit

Après Lot 7, Zandu dispose du noyau opérationnel nécessaire à une première vraie interface :

```text
ADMIN
├── Organization
├── Stores
├── Users / permissions
├── Catalog
├── Pricing
├── Suppliers
├── Purchasing
├── Inventory
├── Transfers
├── Stock Counts
├── Cash registers
└── Monitoring opérationnel

POS
├── CashSession
├── Catalog search
├── Cart / Sale
├── CompleteSale cash
├── Receipt
└── Return / Refund essentiel
```

Il devient alors raisonnable de basculer l’effort vers :

```text
Frontend Foundation
→ Admin interface
→ POS interface
```

sans attendre Customers, providers, Reporting avancé ou Offline.

---
