# 58. Principe de travail

Pour chaque étape :

1. vérifier baseline et ADR ;
2. vérifier les APIs réellement livrées par Lots 3 et 5 ;
3. conserver PurchaseOrder et GoodsReceipt séparés ;
4. conserver PurchaseReturn et Correction séparés ;
5. utiliser les Application Contracts cross-context ;
6. travailler en base quantity pour Inventory ;
7. conserver les snapshots packaging/coût nécessaires ;
8. ne jamais inventer de coût fournisseur ;
9. rendre PostGoodsReceipt idempotent ;
10. protéger receivedQuantity contre la concurrence ;
11. intégrer Stock et Costing dans la transaction critique ;
12. injecter des erreurs aux frontières ;
13. tester RLS et tenant isolation ;
14. exécuter architecture tests / PHPStan / PHP-CS-Fixer / Deptrac ;
15. faire des commits atomiques ;
16. mettre à jour `IMPLEMENTATION_STATUS.md`.
