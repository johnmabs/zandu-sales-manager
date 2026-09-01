# 22. Démonstration consolidée du Lot 3

Scénario Inventory :

```text
1. Owner login.

2. Store A existe.

3. Product A du Lot 2 :
   PHYSICAL
   inventoryTracked = true.

4. InitializeStock:
   quantity = 100

5. Résultat:
   Stock.quantityOnHand = 100

6. StockMovement:
   INITIAL_STOCK
   previous = 0
   resulting = 100

7. Second InitializeStock(50)
   → CONFLICT

8. AdjustStock(-5, "Damaged items")
   → quantityOnHand = 95
   → ADJUSTMENT_OUT = 5

9. AdjustStock(+2, "Correction")
   → quantityOnHand = 97
   → ADJUSTMENT_IN = 2

10. AdjustStock(-100)
    → INSUFFICIENT_STOCK
    → no partial effect

11. Tenant B tries to read Stock.
    → NOT_FOUND
```

Scénario Cash :

```text
12. Owner creates CashRegister:
    REGISTER-01
    Store A

13. Cashier authorized on Store A
    opens session:
    openingBalance = 50 000 XAF

14. Second concurrent OpenCashSession
    on REGISTER-01
    → rejected

15. RecordCashIn:
    +10 000 XAF
    reason = "Additional float"

16. RecordCashOut:
    -5 000 XAF
    reason = "Petty cash"

17. expected balance:
    55 000 XAF

18. CloseCashSession:
    countedClosingBalance = 54 500 XAF

19. discrepancy:
    -500 XAF

20. session = CLOSED

21. RecordCashIn after close
    → rejected
```

Scénario StoreClosure :

```text
22. StoreClosure requested.

23. Stock.quantityOnHand = 97
    → STOCK_REMAINING blocker

24. Si CashSession OPEN
    → OPEN_CASH_SESSION blocker
```

Scénario sécurité :

```text
25. User scoped only to Store B
    tries Inventory/Cash on Store A
    → NOT_FOUND/FORBIDDEN selon contrat public

26. Every sensitive mutation has:
    SecurityAuditEntry
    correlationId
    outbox message

27. No Sale exists.

28. No Payment exists.

29. No CompleteSale exists.
```

---
