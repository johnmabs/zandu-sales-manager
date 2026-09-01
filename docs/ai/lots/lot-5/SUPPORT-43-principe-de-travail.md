# 43. Principe de travail

Pour chaque étape :

1. vérifier baseline et ADR ;
2. vérifier l’état réel du repository ;
3. ne pas inventer un coût historique ;
4. conserver séparation Stock / Valuation ;
5. conserver séparation Return / Refund ;
6. préserver tous les snapshots historiques ;
7. utiliser les Application Contracts entre contexts ;
8. garantir idempotence ;
9. tester les cumuls de quantités et montants ;
10. tester concurrence sur PostgreSQL réel ;
11. injecter les échecs des transactions critiques ;
12. vérifier RLS et tenant isolation ;
13. exécuter PHPStan, PHP-CS-Fixer, Deptrac et Composer audit ;
14. effectuer un commit atomique ;
15. mettre à jour `IMPLEMENTATION_STATUS.md`.
