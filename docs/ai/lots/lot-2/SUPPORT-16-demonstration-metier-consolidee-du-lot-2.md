# 16. Démonstration métier consolidée du Lot 2

La gate doit contenir une démonstration exécutable de bout en bout.

Scénario recommandé :

```text
1. Owner se connecte.

2. Owner crée Category :
   "Boissons"

3. Owner crée Product :
   code = COCA-33
   name = Coca-Cola 33cl
   type = PHYSICAL
   inventoryTracked = true
   status initial = DRAFT

4. Owner crée le packaging de base :
   CAN
   conversionFactor = 1

5. Owner ajoute :
   PACK_6
   conversionFactor = 6

6. Owner ajoute :
   CARTON_24
   conversionFactor = 24

7. Owner ajoute le barcode :
   "00012345678905"

8. Owner active le Product.

9. Owner crée PriceList :
   RETAIL_XAF
   currency = XAF

10. Owner ajoute :
    CAN → 500 XAF
    PACK_6 → 2 750 XAF
    CARTON_24 → 10 000 XAF

11. Résolution barcode :
    "00012345678905"

    → ProductId COCA-33
    → ProductPackagingId attendu

12. Résolution prix :
    CAN
    → 500 XAF

13. Tenant B tente d'accéder au produit Tenant A.
    → NOT_FOUND

14. Un utilisateur sans permission tente de modifier le prix.
    → FORBIDDEN

15. ProductCode est modifié après activation.
    → DOMAIN_RULE_VIOLATION

16. SERVICE avec inventoryTracked=true.
    → DOMAIN_RULE_VIOLATION

17. Toutes les mutations sensibles possèdent :
    audit
    correlationId
    outbox event

18. Aucun Stock n'a été créé.

19. Aucune Sale n'a été créée.

20. Aucun CashMovement n'a été créé.
```

---
