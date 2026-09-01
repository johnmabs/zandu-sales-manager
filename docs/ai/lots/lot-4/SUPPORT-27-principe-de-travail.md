# 27. Principe de travail

Pour chaque étape :

1. vérifier baseline et ADR ;
2. vérifier l’état réel des Lots 2 et 3 ;
3. réutiliser les Application Contracts existants ;
4. définir les invariants avant le code ;
5. préserver les snapshots ;
6. rendre les commandes retry-safe ;
7. tester PostgreSQL réel ;
8. tester les échecs à chaque point critique ;
9. tester concurrence et idempotence ;
10. exécuter architecture tests ;
11. exécuter PHPStan / PHP-CS-Fixer / Composer audit ;
12. faire un commit atomique ;
13. mettre à jour `IMPLEMENTATION_STATUS.md` ;
14. documenter toute décision structurante.

---
