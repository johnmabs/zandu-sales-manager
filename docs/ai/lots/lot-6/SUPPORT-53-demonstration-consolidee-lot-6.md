# 53. Démonstration consolidée Lot 6

```text
1. Owner crée Supplier "Congo Distribution".

2. Manager crée PurchaseOrder Store A :
   Product A = 100 unités
   unitCost = 4 000 XAF.

3. PO → CONFIRMED.

4. Première livraison : 40 unités.

5. GoodsReceipt #1 POSTED.

6. Stock augmente de 40.

7. StockMovement PURCHASE_RECEIPT créé.

8. StockValuation est mise à jour avec inventoryUnitCost.

9. PurchaseOrder.receivedQuantity = 40.
   status = PARTIALLY_RECEIVED.

10. Deuxième réception : 60 unités.

11. PO receivedQuantity = 100.
    status = FULLY_RECEIVED.

12. Retry du receipt : aucun doublon.

13. Tentative receipt +10 : refus sans permission.

14. Over-receipt autorisé avec raison :
    quantité réelle conservée + audit.

15. Erreur documentaire :
    GoodsReceiptCorrection créée.

16. Correction OUT appliquée sans modifier le GoodsReceipt original.

17. Supplier reprend une partie physique :
    PurchaseReturn SHIPPED.

18. StockMovement PURCHASE_RETURN créé.

19. Costing sort la valeur au coût moyen courant.

20. Tenant B → NOT_FOUND.

21. Erreur injectée avant commit → aucun effet partiel.

22. StoreClosure voit les documents Purchasing ouverts comme blockers.
```

---
