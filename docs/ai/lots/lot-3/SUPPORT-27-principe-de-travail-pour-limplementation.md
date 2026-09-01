# 27. Principe de travail pour l’implémentation

Pour chaque étape :

1. vérifier baseline et ADR ;
2. vérifier l’état réel du repository ;
3. identifier le bounded context propriétaire ;
4. formaliser les invariants ;
5. écrire la plus petite tranche cohérente ;
6. ajouter les tests dans le même commit ;
7. utiliser PostgreSQL réel pour persistence/concurrence/RLS ;
8. tester échecs et rollback ;
9. exécuter architecture tests ;
10. exécuter PHPStan, PHP-CS-Fixer et Composer audit ;
11. proposer un commit atomique ;
12. mettre à jour `IMPLEMENTATION_STATUS.md` ;
13. ne passer à l’étape suivante qu’après validation ;
14. mettre à jour l’ADR si une décision DÉCIDÉ change ;
15. ne jamais transformer silencieusement une question ouverte en décision.

Le repository réel reste la source de vérité.

---
