# 71. Principe de travail

Pour chaque étape :

1. vérifier baseline et ADR ;
2. vérifier l’état réel des Lots 3, 5 et 6 ;
3. ne pas réinventer les Application Contracts ;
4. garder StockTransfer dans Inventory ;
5. utiliser baseQuantity ;
6. transporter la valeur avec le transfert ;
7. ne jamais créer automatiquement la quantité manquante ;
8. garder StockCountLine séparée ;
9. verrouiller seulement le périmètre compté ;
10. préserver zéro vs null ;
11. réconcilier en batches idempotents ;
12. tester crash recovery ;
13. tester concurrence réelle PostgreSQL ;
14. tester les matrices de rollback ;
15. tester RLS et tenant isolation ;
16. exécuter architecture tests, PHPStan, PHP-CS-Fixer, Deptrac et Composer audit ;
17. faire un commit atomique ;
18. mettre à jour `IMPLEMENTATION_STATUS.md` ;
19. valider formellement M3 avant de démarrer le frontend.
