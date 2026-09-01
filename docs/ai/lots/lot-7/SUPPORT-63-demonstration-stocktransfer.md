# 63. Démonstration StockTransfer

```text
1. Organization A possède Store A et Store B.

2. Product P suivi.

3. Store A :
   Stock = 20
   avg cost = 5 000
   value = 100 000.

4. Manager crée Transfer A → B.

5. requested = 10.

6. Ship quantity = 8.

7. Store A :
   Stock 20 → 12.
   TRANSFER_OUT = 8.
   value transported = 40 000.

8. Transfer → SHIPPED.

9. Retry Ship :
   aucun nouvel effet.

10. Destination reçoit 7.

11. Store B :
    Stock +7.
    TRANSFER_IN = 7.
    valeur reçue transportée.

12. Transfer → RECEIVED.

13. discrepancy = 1.

14. La quantité manquante n’est pas créée.

15. Retry Receive :
    aucun nouvel effet.

16. Tenant B :
    NOT_FOUND.
```

---
