# 19. Principe de travail pour la suite

Pour chaque étape :

1. vérifier la baseline DDD concernée ;
2. identifier le bounded context propriétaire ;
3. définir l’invariant avant l’implémentation ;
4. implémenter la plus petite tranche cohérente ;
5. ajouter les tests dans le même commit lorsque directement liés ;
6. exécuter architecture tests et tests métier ;
7. proposer un commit atomique ;
8. ne passer à l’étape suivante qu’après validation ;
9. mettre à jour la documentation seulement si l’état réel ou une décision structurante évolue.

Le repository réel reste la source de vérité sur l’avancement d’implémentation.
