# 23. Démonstration consolidée — M2

```text
1. Organization A existe.
2. Store A ACTIVE.
3. Product Coca-Cola 33cl existe.
4. PHYSICAL, inventoryTracked=true.
5. Packaging CAN.
6. Prix CAN = 500 XAF.
7. Stock Store A = 20.
8. Cashier autorisé sur Store A.
9. Cashier ouvre CashSession à 50 000 XAF.
10. Il crée une Sale.
11. Il ajoute 3 CAN.
12. Total = 1 500 XAF selon pricing/tax policy.
13. CompleteSale avec Payment CASH 1 500 XAF.
14. tenderedAmount = 2 000 XAF.
15. changeAmount = 500 XAF.
16. Sale → COMPLETED.
17. Payment → CONFIRMED.
18. CashMovement SALE_PAYMENT → +1 500 XAF.
19. expected cash → 51 500 XAF.
20. Stock → 17.
21. StockMovement SALE → 3.
22. Receipt consultable.
23. Retry même Idempotency-Key → aucun doublon.
24. Vente concurrente → aucun stock négatif.
25. Tenant B → NOT_FOUND.
26. Erreur injectée avant commit → aucun effet partiel.
```

---
