# F4 — Support gate et livraison

## Prérequis

Les capacités backend Inventory/Costing et les fondations frontend sont
disponibles. Le Gate F3 doit être validé en CI avant de déclarer F4 terminé.

## Gate F4

1. navigation permission-aware ;
2. store actif et changement de store sûrs ;
3. positions et détails chargent ;
4. décimaux exacts préservés ;
5. initialisation et ajustement fonctionnent ;
6. mouvements append-only lisibles et filtrables ;
7. valorisation distincte du stock ;
8. confidentialité coûts/valeurs respectée ;
9. transfert DRAFT/SHIP/RECEIVE/CANCEL fonctionnel ;
10. écarts de réception visibles sans stock inventé ;
11. scope multi-store respecté ;
12. comptage FULL/PARTIAL et GUIDED/BLIND fonctionnel ;
13. zéro distinct de non saisi ;
14. finalisation/reprise/cancel cohérents ;
15. erreurs et correlationId visibles ;
16. caches tenant/store cohérents ;
17. accessibilité et responsive validés ;
18. tests unitaires/composants/intégration verts ;
19. trois parcours E2E verts ;
20. lint, format, typecheck et build Admin verts.

Source : spécification F4, section 42.

