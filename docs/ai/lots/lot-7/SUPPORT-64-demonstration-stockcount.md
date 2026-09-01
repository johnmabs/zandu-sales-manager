# 64. Démonstration StockCount

```text
1. Store A contient :
   Product A = 12
   Product B = 5
   Product C = 0/non créé selon état.

2. Manager crée StockCount PARTIAL :
   A, B, C.

3. StartStockCount.

4. Snapshots :
   A expected = 12
   B expected = 5
   C expected = 0

5. Scope locks créés.

6. Tentative Sale Product A :
   blocked.

7. Tentative receipt Product B :
   blocked.

8. Mouvement sur Product D hors scope :
   allowed.

9. Compteur saisit :
   A = 11
   B = 5
   C = 2

10. Begin finalization.

11. A :
    variance -1
    STOCK_COUNT_CORRECTION_OUT.

12. B :
    variance 0
    aucun movement.

13. C :
    variance +2
    STOCK_COUNT_CORRECTION_IN.

14. Costing est ajusté.

15. Crash simulé après un batch.

16. Reprise :
    seulement PENDING.

17. Toutes lignes RECONCILED.

18. StockCount → COMPLETED.

19. Locks supprimés.

20. Sale Product A de nouveau autorisable.

21. Historique des lignes conservé.
```

---
