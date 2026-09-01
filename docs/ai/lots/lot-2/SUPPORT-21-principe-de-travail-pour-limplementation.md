# 21. Principe de travail pour l’implémentation

Pour chaque étape :

1. vérifier la baseline DDD concernée ;
2. vérifier les ADR techniques et métier applicables ;
3. identifier le bounded context propriétaire ;
4. définir les invariants avant le code ;
5. implémenter la plus petite tranche cohérente ;
6. ajouter les tests directement liés dans le même commit ;
7. exécuter les tests domaine et architecture ;
8. exécuter les tests PostgreSQL lorsque persistence concernée ;
9. exécuter tenant isolation / RLS lorsque données tenant-owned ;
10. exécuter PHPStan, PHP-CS-Fixer et Deptrac ;
11. proposer un commit atomique ;
12. mettre à jour `IMPLEMENTATION_STATUS.md` avec l’état réel ;
13. ne passer à l’étape suivante qu’après validation ;
14. créer ou mettre à jour un ADR lorsqu’une décision structurante change ;
15. ne jamais transformer silencieusement une question ouverte en décision.

Le repository réel reste la source de vérité sur l’avancement d’implémentation.

---
